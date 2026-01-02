<?php

namespace Perspective\MultisearchIo\Model\Layer\Filter;

use Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory as OriginalCategoryFactory;
use Perspective\MultisearchIo\Model\Layer\Filter\DataProvider\CategoryFactory;

class Category extends \Magento\Catalog\Model\Layer\Filter\Category
{
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Escaper $escaper,
        OriginalCategoryFactory $categoryDataProviderFactoryOriginal,
        CategoryFactory $categoryDataProviderFactory,
        \Magento\Catalog\Model\Product\Attribute\Repository $attributeRepository,
        array $data = []
    )
    {
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $escaper, $categoryDataProviderFactoryOriginal, $data);
        $this->_escaper = $escaper;
        $this->_requestVar = 'cat';
        $this->dataProvider = $categoryDataProviderFactory->create(['layer' => $this->getLayer()]);
        $this->setAttributeModel($attributeRepository->get('category_ids'));
    }

    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        $filterParams = $request->getParam($this->getRequestVar());
        if (!$filterParams) {
            return $this;
        }
        $categoryIds = explode(',', $filterParams);
        if (empty($categoryIds)) {
            return $this;
        }
        $categoryIdCandidates = [];
        /** @var \Perspective\MultisearchIo\Model\ResourceModel\MultisearchFulltext\Collection $collection */
        $collection = $this->getLayer()->getProductCollection();
        foreach ($categoryIds as $categoryId) {
            if (!is_numeric($categoryId)) {
                return $this;
            }
            $this->dataProvider->setCategoryId($categoryId);

            if ($this->dataProvider->isValid()) {
                $category = $this->dataProvider->getCategory();
                $categoryIdCandidates [$categoryId] = $category;

            }
        }
        foreach ($categoryIdCandidates as $categoryId => $category) {
            $this->getLayer()->getState()->addFilter($this->_createItem($category->getName(), $categoryId));
        }
        $collection->addCategoryIdFilter(array_keys($categoryIdCandidates));

        return $this;
    }

    /**
     * Метод сумістності з 3rd party модулями
     * @return bool
     */
    public function isShowNestedCategories(): bool
    {
        return true;
    }

    protected function _getItemsData()
    {
        /** @var \Perspective\MultisearchIo\Model\ResourceModel\MultisearchFulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();
        $facets = [];
        try {
            $facets = $productCollection->getFacetedData('category');
        } catch (\Magento\Framework\Exception\StateException $stateException) {
            return $this->itemDataBuilder->build();
        }
        $ids = array_keys($facets);
        $this->dataProvider->loadCategoryDataInMemory($ids);
        foreach ($ids as $id) {
            if (isset($facets[$id])) {
                $categoryName = $this->dataProvider->getCategoryName($id);
                $this->itemDataBuilder->addItemData(
                    $this->_escaper->escapeHtml($categoryName),
                    $id,
                    $facets[$id]['count']
                );
            }
        }
        return $this->itemDataBuilder->build();
    }
}
