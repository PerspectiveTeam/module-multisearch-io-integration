<?php

namespace Perspective\MultisearchIo\Model\Layer\Filter\DataProvider;

use Magento\Catalog\Model\CategoryFactory as CategoryModelFactory;
use Magento\Catalog\Model\Layer;
use Magento\Framework\Registry;


class Category extends \Magento\Catalog\Model\Layer\Filter\DataProvider\Category
{
    private ?array $categoriesCache = null;

    public function __construct(
        Registry $coreRegistry,
        CategoryModelFactory $categoryFactory,
        private readonly \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        Layer $layer
    )
    {
        parent::__construct($coreRegistry, $categoryFactory, $layer);
    }

    public function loadCategoryDataInMemory($categoryIds)
    {

        if (!$this->categoriesCache) {
            $categoryCollection = $this->categoryCollectionFactory->create();
            $categoryCollection->addAttributeToSelect('name');
            $categoryCollection->addAttributeToFilter('entity_id', ['in' => $categoryIds]);

            $this->categoriesCache = [];
            foreach ($categoryCollection as $category) {
                $this->categoriesCache[$category->getId()] = $category;
            }
        }
        return $this->categoriesCache;

    }

    public function getCategoryName($categoryId)
    {
        $this->loadCategoryDataInMemory([$categoryId]);
        return isset($this->categoriesCache[$categoryId]) ? $this->categoriesCache[$categoryId]->getName() : '';
    }
}
