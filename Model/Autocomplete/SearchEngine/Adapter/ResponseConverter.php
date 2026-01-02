<?php

namespace Perspective\MultisearchIo\Model\Autocomplete\SearchEngine\Adapter;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Api\AttributeInterfaceFactory;
use Magento\Framework\Api\Search\AggregationInterfaceFactory;
use Magento\Framework\Api\Search\DocumentFactory;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Search\Response\QueryResponseFactory;
use Perspective\MultisearchIo\Model\Config;
use Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter\Request\Client;
use Psr\Http\Message\ResponseInterface;

class ResponseConverter extends \Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter\ResponseConverter
{
    /**
     * @param Client $client
     * @param Config $config
     * @param ProductResource $productResource
     * @param AggregationInterfaceFactory $aggregationFactory
     * @param DocumentFactory $documentFactory
     * @param AttributeInterfaceFactory $attributeFactory
     * @param QueryResponseFactory $queryResponseFactory
     * @param FilterManager $filterManager
     * @SuppressWarnings("php:S1185", "php:S107")
     */
    public function __construct(
        Client $client,
        Config $config,
        ProductResource $productResource,
        AggregationInterfaceFactory $aggregationFactory,
        DocumentFactory $documentFactory,
        AttributeInterfaceFactory $attributeFactory,
        QueryResponseFactory $queryResponseFactory,
        FilterManager $filterManager,
    )
    {
        parent::__construct($client, $config, $productResource, $aggregationFactory, $documentFactory, $attributeFactory, $queryResponseFactory, $filterManager);
    }

    use \Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter\ResponseConverter\DocumentProcessorTrait;
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
            'total' => $responseData['total'] ?? 0,
            'suggestions' => $responseData['results']['suggest'] ?? [],
            'history' => $responseData['history'] ?? []
        ]);
    }
}
