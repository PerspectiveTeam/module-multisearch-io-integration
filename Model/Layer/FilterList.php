<?php

namespace Perspective\MultisearchIo\Model\Layer;

use Magento\Catalog\Model\Config\LayerCategoryConfig;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Catalog\Model\Layer\Filter\Decimal;
use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\ObjectManagerInterface;
use Perspective\MultisearchIo\Model\Layer\Filter\Attribute;
use Perspective\MultisearchIo\Model\Layer\Filter\Category;
use Perspective\MultisearchIo\Model\Layer\Filter\Price;

class FilterList extends \Magento\Catalog\Model\Layer\FilterList
{
    protected $filterTypes = [
        self::CATEGORY_FILTER => Category::class,
        self::ATTRIBUTE_FILTER => Attribute::class,
        self::PRICE_FILTER => Price::class,
//        self::DECIMAL_FILTER   => Decimal::class,
    ];

    public function __construct(
        ObjectManagerInterface $objectManager,
        FilterableAttributeListInterface $filterableAttributes,
        LayerCategoryConfig $layerCategoryConfig,
        private readonly FilterManager $filterManager,
        array $filters = [])
    {
        parent::__construct($objectManager, $filterableAttributes, $layerCategoryConfig, $filters);
    }

    /**
     * @param Layer $layer
     * @return array|AbstractFilter[]
     */
    public function getFilters(Layer $layer)
    {
        if (!count($this->filters)) {
            $this->filters[] = $this->objectManager->create($this->filterTypes[self::PRICE_FILTER], ['layer' => $layer]);
            $this->filters[] = $this->objectManager->create($this->filterTypes[self::CATEGORY_FILTER], ['layer' => $layer]);
            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
            foreach ($this->filterableAttributes->getList() as $attribute) {
                $attributesToSkip = ['price', 'category_ids'];
                if (in_array($attribute->getAttributeCode(), $attributesToSkip)) {
                    continue;
                }
//            $attribute->setOriginalAttributeCode($attribute->getAttributeCode());
                // Крафтимо код по назві атрибута, бо в мультісьорчі фільтри йдуть по name
                $attribute->setAlternativeAttributeCode(strtolower($this->filterManager->translitUrl($attribute->getStoreLabel())));
                $this->filters[] = $this->objectManager->create(
                    $this->filterTypes[self::ATTRIBUTE_FILTER],
                    ['data' => ['attribute_model' => $attribute], 'layer' => $layer]
                );
            }
        }

        return $this->filters;
    }
}
