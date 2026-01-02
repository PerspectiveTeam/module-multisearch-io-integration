<?php

namespace Perspective\MultisearchIo\Model\Layer\Search;

use Magento\Catalog\Model\Layer\ItemCollectionProviderInterface;

class ItemCollectionProvider implements ItemCollectionProviderInterface
{
    public function __construct(
        private readonly \Perspective\MultisearchIo\Model\ResourceModel\MultisearchFulltext\CollectionFactory $collectionFactory
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getCollection(\Magento\Catalog\Model\Category $category)
    {
        return $this->collectionFactory->create();
    }
}
