<?php

namespace Perspective\MultisearchIo\Model;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Search\Model\QueryFactory;
use Perspective\MultisearchIo\Service\Search;

class Multisearch
{
    private ?array $searchResults = null;

    private mixed $aggregations = null;

    public function __construct(
        private readonly RequestInterface $request,
        private readonly Search $searchService,
        private readonly QueryFactory $queryFactory,
        private readonly \Perspective\MultisearchIo\Model\ResourceModel\MultisearchFulltext\CollectionFactory $productCollectionFactory,

    )
    {
    }

    /**
     * Override to use Multisearch.io instead of default search
     *
     * @return Collection
     */
    public function getProductCollection()
    {
        $query = $this->queryFactory->get();
        $queryText = $query->getQueryText();

        if (!$queryText) {
            return $this->getEmptyCollection();
        }

        // Get pagination parameters
        $currentPage = (int)$this->request->getParam('p', 1);
        $pageSize = (int)$this->request->getParam('product_list_limit', 20);
        $from = ($currentPage - 1) * $pageSize;

        // Perform search using Multisearch.io
        $this->searchResults = $this->searchService->search($queryText, $from, $pageSize);

        if (empty($this->searchResults['documents'])) {
            return $this->getEmptyCollection();
        }

        // Extract product IDs from search results
        $productIds = array_map(function ($doc) {
            return $doc['entity_id'] ?? $doc['id'];
        }, $this->searchResults['documents']);
        $this->aggregations = $this->searchResults['aggregations'] ?? [];
        // Create product collection with search results
        return $this->getProductCollectionFromIds($productIds);
    }

    /**
     * Create product collection from product IDs
     *
     * @param array $productIds
     * @return Collection
     */
    private function getProductCollectionFromIds(array $productIds): \Perspective\MultisearchIo\Model\ResourceModel\MultisearchFulltext\Collection
    {
        /** @var CollectionFactory $collectionFactory */
        $collectionFactory = $this->productCollectionFactory;
        $collection = $collectionFactory->create();

        $collection->addAttributeToSelect('*')
            ->addFieldToFilter('entity_id', ['in' => $productIds])
            ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['in' => [
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG,
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH,
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH
            ]]);

        // Preserve search result order
        if (!empty($productIds)) {
            $orderExpression = 'FIELD(e.entity_id,' . implode(',', $productIds) . ')';
            $collection->getSelect()->order(new \Zend_Db_Expr($orderExpression));
        }

        return $collection;
    }

    /**
     * Get empty collection for when no search results
     *
     * @return Collection
     */
    public function getEmptyCollection(): \Perspective\MultisearchIo\Model\ResourceModel\MultisearchFulltext\Collection
    {
        /** @var CollectionFactory $collectionFactory */
        $collectionFactory = $this->productCollectionFactory;
        $collection = $collectionFactory->create();
        $collection->addFieldToFilter('entity_id', ['in' => []]);
        return $collection;
    }
    public function getAggregations(): mixed
    {
        return $this->aggregations;
    }

}
