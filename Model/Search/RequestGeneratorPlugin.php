<?php

namespace Perspective\MultisearchIo\Model\Search;

use Magento\Catalog\Api\Data\EavAttributeInterface;
use Magento\Catalog\Model\Entity\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\CatalogSearch\Model\Search\RequestGenerator;
use Magento\CatalogSearch\Model\Search\RequestGenerator\GeneratorResolver;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Search\Request\FilterInterface;
use Magento\Framework\Search\Request\QueryInterface;
use Magento\Store\Model\StoreManagerInterface;

class RequestGeneratorPlugin
{
    /** Filter name suffix */
    const FILTER_SUFFIX = '_filter';

    /** Bucket name suffix */
    const BUCKET_SUFFIX = '_bucket';


    public function __construct(
        private readonly CollectionFactory $productAttributeCollectionFactory,
        private readonly GeneratorResolver $generatorResolver,
    )
    {
    }

    /**
     * @param RequestGenerator $subject
     * @param array $result
     * @return array
     * @SuppressWarnings("php:S1172")
     */
    public function afterGenerate(RequestGenerator $subject, array $result): array
    {
        $requests['multisearch_io_search'] =
            $this->generateRequest(EavAttributeInterface::IS_FILTERABLE, 'multisearch_io_search');
        $combinedDataRequests = array_merge_recursive($result, $requests);
        return $combinedDataRequests;
    }

    /**
     * Generate search request
     *
     * @param string $attributeType
     * @param string $container
     * @param bool $useFulltext
     * @return array
     */
    private function generateRequest($attributeType, $container)
    {
        $request = [];
        foreach ($this->getSearchableAttributes() as $attribute) {
            /** @var $attribute Attribute */
            if ($attribute->getData($attributeType)) {
                if (!in_array($attribute->getAttributeCode(), ['price', 'category_ids'], true)) {
                    // Декларуємо фільтри у М2 форматі
                    $request = $this->declareInM2Format($attribute, $request, $container);
                }
            }
        }

        return $request;
    }

    /**
     * Retrieve searchable attributes
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected function getSearchableAttributes()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $productAttributes */
        $productAttributes = $this->productAttributeCollectionFactory->create();
        $productAttributes->addFieldToFilter(
            ['is_searchable', 'is_visible_in_advanced_search', 'is_filterable', 'is_filterable_in_search'],
            [1, 1, [1, 2], 1]
        );

        return $productAttributes;
    }

    /**
     * @param Attribute $attribute
     * @param array $request
     * @param string $container
     * @return array
     */
    private function declareInM2Format($attribute, array $request, string $container): array
    {
        $queryName = $attribute->getAttributeCode() . '_query';
        $request['queries'][$container]['queryReference'][] = [
            'clause' => 'must',
            'ref' => $queryName,
        ];
        $filterName = $attribute->getAttributeCode() . self::FILTER_SUFFIX;
        $request['queries'][$queryName] = [
            'name' => $queryName,
            'type' => QueryInterface::TYPE_FILTER,
            'filterReference' => [
                [
                    'clause' => 'must',
                    'ref' => $filterName,
                ]
            ],
        ];
        $bucketName = $attribute->getAttributeCode() . self::BUCKET_SUFFIX;
        $generator = $this->generatorResolver->getGeneratorForType($attribute->getBackendType());
        $request['filters'][$filterName] = $generator->getFilterData($attribute, $filterName);
        $request['aggregations'][$bucketName] = $generator->getAggregationData($attribute, $bucketName);
        return $request;
    }
}
