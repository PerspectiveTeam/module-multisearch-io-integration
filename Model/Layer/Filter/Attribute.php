<?php

namespace Perspective\MultisearchIo\Model\Layer\Filter;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Catalog\Model\ResourceModel\Layer\Filter\AttributeFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\StripTags;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Store\Model\StoreManagerInterface;

class Attribute extends \Magento\Catalog\Model\Layer\Filter\Attribute
{
    /**
     * @param ItemFactory $filterItemFactory
     * @param StoreManagerInterface $storeManager
     * @param Layer $layer
     * @param DataBuilder $itemDataBuilder
     * @param AttributeFactory $filterAttributeFactory
     * @param StringUtils $string
     * @param StripTags $tagFilter
     * @param Escaper $escaper
     * @param array $data
     * @SuppressWarnings("php:S107")
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\AttributeFactory $filterAttributeFactory,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Filter\StripTags $tagFilter,
        private readonly \Magento\Framework\Escaper $escaper,
        array $data = []

    )
    {
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $filterAttributeFactory, $string, $tagFilter, $data);
    }


    /**
     * Apply attribute option filter to product collection
     *
     * @param RequestInterface $request
     * @return  $this
     * @throws LocalizedException
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        $filterParams = $request->getParam($this->_requestVar);
        if (!$filterParams) {
            $filterParams = $request->getParam($this->getAttributeModel()->getAttributeCode());
        }
        if (is_array($filterParams)) {
            return $this;
        }
        $attributeFilterParams = explode(',', $filterParams ?? '');

        $attributeFilterParams = array_filter($attributeFilterParams);

        foreach ($attributeFilterParams as $filter) {
            if ($filter && strlen($filter)) {
                $text = $this->getOptionText($filter);
                $this->getLayer()->getState()->addFilter($this->_createItem($text, $filter));
            }
        }

        if (count($attributeFilterParams) > 0) {
            $collection = $this->getLayer()->getProductCollection();
            $collection->addFieldToFilter(
                $this->getAttributeModel()->getAttributeCode(),
                $attributeFilterParams,
            );
        }
        return $this;
    }

    /**
     * Get data array for building attribute filter items
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getItemsData()
    {
        $attribute = $this->getAttributeModel();
        $this->_requestVar = $attribute->getAttributeCode();

        /** @var \Perspective\MultisearchIo\Model\ResourceModel\MultisearchFulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();
        try {
            $facets = $productCollection->getFacetedData($this->_requestVar);
        } catch (\Magento\Framework\Exception\StateException $stateException) {
            return $this->itemDataBuilder->build();
        }

        foreach ($facets as $facet) {
            $label = $this->getOptionText($facet['value']);
            $this->itemDataBuilder->addItemData(
                $this->escaper->escapeHtml($label),
                $facet['value'],
                $facet['count']
            );
        }

        return $this->itemDataBuilder->build();
    }
}
