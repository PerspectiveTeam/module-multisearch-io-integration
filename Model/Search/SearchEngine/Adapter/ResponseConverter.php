<?php

namespace Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\CatalogSearch\Model\Search\RequestGenerator;
use Magento\Framework\Api\AttributeInterfaceFactory;
use Magento\Framework\Api\Search\AggregationInterfaceFactory;
use Magento\Framework\Api\Search\DocumentFactory;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Search\Response\Aggregation\Value;
use Magento\Framework\Search\Response\Bucket;
use Magento\Framework\Search\Response\QueryResponseFactory;
use Perspective\MultisearchIo\Api\ResponseConverterInterface;
use Perspective\MultisearchIo\Model\Config;
use Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter\Request\Client;
use Psr\Http\Message\ResponseInterface;

class ResponseConverter implements ResponseConverterInterface
{
    use \Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter\ResponseConverter\DocumentProcessorTrait;

    public function __construct(
        protected readonly Client $client,
        protected readonly Config $config,
        protected readonly ProductResource $productResource,
        protected readonly AggregationInterfaceFactory $aggregationFactory,
        protected readonly DocumentFactory $documentFactory,
        protected readonly AttributeInterfaceFactory $attributeFactory,
        protected readonly QueryResponseFactory $queryResponseFactory,
        protected readonly FilterManager $filterManager,
    )
    {
    }

    public function convert(ResponseInterface $response): \Magento\Framework\Search\ResponseInterface
    {
        $responseData = $this->client->decodeResponseContent($response);

        $productIds = $this->getProductIds($responseData);

        $documents = $this->getDocumentsByProductIds($productIds);

        /**
         * Увага! Multisearch.io не повертає більше 50 бакетів.
         */
        $buckets = [];
        list($responseData, $buckets) = $this->prepareFiltersBucket($responseData, $buckets);
        list($responseData, $buckets) = $this->prepareCategoryBucket($responseData, $buckets);
        list($responseData, $buckets) = $this->preparePriceBucket($responseData, $buckets);
        return $this->queryResponseFactory->create([
            'documents' => $documents,
            'aggregations' => $this->aggregationFactory->create(['buckets' => $buckets]),
            'total' => isset($responseData['total']) ? (int)$responseData['total'] : 0,
        ]);
    }

    /**
     * @param $values
     * @param array $bucketValues
     * @return array
     */
    protected function getTermBucketValues($values, array $bucketValues): array
    {
        foreach ($values as $keyFilterValue => $filterValue) {
            // Ця конструкція для того, щоб підтримувати два варіанти відповіді від multisearch.io
            // 1. 'values' => [ 'value1' => count1, 'value2' => count2, ... ]
            // 2. 'values' => [ ['id' => 'value1', 'count' => count1], ['id' => 'value2', 'count' => count2], ... ]
            // Перемикання між варіантами відбувається на стороні multisearch.io через саппорт
            if (is_array($filterValue)) {
                // Рекомендований варіант
                $termMetric = [
                    'value' => $filterValue['id'],
                    'count' => (int)$filterValue['count']
                ];
                $bucketValues[] = new Value(
                    $filterValue['id'],
                    $termMetric
                );
            } else {
                // Варіант "не дуже" - Може бути кирилиця, пробіли, спецсимволи і т.д. Немає коректного ресолву зі сторони колекції(та і в цілому М2)
                $termMetric = [
                    'value' => $keyFilterValue,
                    'count' => $filterValue
                ];
                $bucketValues[] = new Value(
                    $keyFilterValue,
                    $termMetric
                );
            }
        }
        return $bucketValues;
    }

    /**
     * @param $rangeData
     * @param array $bucketValues
     * @param bool $isPrice
     * @return array
     */
    protected function getRangeBucketValues($rangeData, array $bucketValues): array
    {
        $step = 1;
        $ranges = range($rangeData['min'], $rangeData['max']);
        foreach ($ranges as $range) {
            if ($range + $step > $rangeData['max']) {
                break;
            }
            $rangeMetric = [
                'from' => $range,
                'to' => $range + $step,
                'min' => $rangeData['min'],
                'max' => $rangeData['max'],
                'count' => 1 // нема інфи для ренжу - дефолт 1
            ];
            $bucketValues[] = new Value(
                $range . '_' . $range + $step,
                $rangeMetric
            );
        }
        return $bucketValues;
    }


    protected function addPriceData($value, array $bucketValues, string $type): array
    {
        $rangeMetric = [
            'value' => $type, // min or max
            'price' => $value,
            'count' => 1
        ];

        $bucketValues[] = new Value(
            $value,
            $rangeMetric

        );
        return $bucketValues;
    }

    /**
     * @param array $responseData
     * @param array $buckets
     * @return array
     */
    protected function prepareCategoryBucket(array $responseData, array $buckets): array
    {
        if (isset($responseData['results']['categories'])) {
            $categoryValues = [];
            foreach ($responseData['results']['categories'] as $category) {
                $termMetric = [
                    'value' => $category['id'],
                    'count' => (int)$category['count']
                ];
                $categoryValues[] = new Value(
                    $category['id'],
                    $termMetric
                );
            }
            $buckets['category' . RequestGenerator::BUCKET_SUFFIX] = new Bucket('category', $categoryValues);
        }
        return array($responseData, $buckets);
    }

    /**
     * @param array $responseData
     * @param array $buckets
     * @return array
     * @SuppressWarnings("php:S3776")
     */
    protected function preparePriceBucket(array $responseData, array $buckets): array
    {
        $automatedPriceFilter = null;
        $aggregatedPriceFilter = null;
        if (isset($responseData['results']['filters'])) {
            $bucketValues = [];
            foreach ($responseData['results']['filters'] as $filterData) {

                if (isset($filterData['id']) && $filterData['id'] === 'price') {
                    $automatedPriceFilter = $filterData;
                    continue;
                }
                if (isset($filterData['name']) && in_array(
                        $filterData['name'],
                        $this->getPriceNameVariations()
                    )) {
                    $aggregatedPriceFilter = $filterData;
                }

            }
            if (!isset($automatedPriceFilter['range']['min'], $automatedPriceFilter['range']['max'])) {
                // Нема даних для побудови фільтру ціни взагалі
                return array($responseData, $buckets);
            }
            $bucketValues = $this->addPriceData($automatedPriceFilter['range']['min'], $bucketValues, 'min');
            $bucketValues = $this->addPriceData($automatedPriceFilter['range']['max'], $bucketValues, 'max');

            if (is_array($automatedPriceFilter) && !is_array($aggregatedPriceFilter)) {
                // Даємо шанс побудувати бакет на основі автоматичного фільтра цін
                $buckets['price' . RequestGenerator::BUCKET_SUFFIX] = new Bucket('price', $bucketValues);
                return array($responseData, $buckets);
            }
            if (!is_array($automatedPriceFilter) || !is_array($aggregatedPriceFilter)) {
                // Нема даних для побудови фільтру ціни взагалі
                return array($responseData, $buckets);
            }
            // Якщо сюди зайшли - значить є й автоматичний фільтр цін і агрегований.
            // В теорії, сюди не має заходить якщо Multisearch.io налаштований коректно
            usort($aggregatedPriceFilter['values'], function ($a, $b) {
                return floatval($a['name']) <=> floatval($b['name']);
            });
            foreach ($aggregatedPriceFilter['values'] as $key => $aggregatedPrice) {
                if (!isset($aggregatedPriceFilter['values'][$key + 1])) {
                    break;
                }
                $rangeMetric = [
                    'from' => $aggregatedPriceFilter['values'][$key]['name'],
                    'to' => $aggregatedPriceFilter['values'][$key + 1]['name'],
                    'min' => $automatedPriceFilter['range']['min'],
                    'max' => $automatedPriceFilter['range']['max'],
                    'count' => $aggregatedPriceFilter['values'][$key]['count'] + $aggregatedPriceFilter['values'][$key + 1]['count']
                ];
                $bucketValues[] = new Value(
                    $aggregatedPriceFilter['values'][$key]['name'] . '_' . $aggregatedPriceFilter['values'][$key + 1]['name'],
                    $rangeMetric
                );
            }
            // видаляємо всі можливі варіації назви фільтру ціни, щоб не було дублювання
            foreach ($this->getPriceNameVariations() as $variation) {
                unset($buckets[strtolower($variation) . RequestGenerator::BUCKET_SUFFIX]);
            }
            $buckets['price' . RequestGenerator::BUCKET_SUFFIX] = new Bucket('price', $bucketValues);
        }
        return array($responseData, $buckets);
    }

    /**
     * @param array $responseData
     * @param array $buckets
     * @return array
     */
    protected function prepareFiltersBucket(array $responseData, array $buckets): array
    {
        if (isset($responseData['results']['filters'])) {
            foreach ($responseData['results']['filters'] as $filterData) {
                if (isset($filterData['id']) && ($filterData['id'] === 'price' || $filterData['id'] === 'brand')) {
                    // фільтр цін обробляємо окремо
                    // бренд поки що ігноруємо - необхідно імплементувати окремо
                    continue;
                }
                $bucketValues = [];
                // звичайний фільтр
                if (isset($filterData['values'])) {
                    $bucketValues = $this->getTermBucketValues($filterData['values'], $bucketValues);

                } elseif (isset($filterData['range'])) {
                    // фільтр цін і т.д.
                    $bucketValues = $this->getRangeBucketValues($filterData['range'], $bucketValues);
                }

                $filterSubname = $filterData['id'];

                $buckets[$filterSubname . RequestGenerator::BUCKET_SUFFIX] = new Bucket($filterSubname, $bucketValues);
            }
        }
        return array($responseData, $buckets);
    }

    /**
     * @return array
     */
    protected function getPriceNameVariations(): array
    {
        return [
            $this->filterManager->translit('Ціна'),
            $this->filterManager->translit('Цена'),
            $this->filterManager->translit('Price'),
            'Ціна',
            'Цена',
            'Price'
        ];
    }

}
