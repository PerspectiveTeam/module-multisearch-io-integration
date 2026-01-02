<?php

namespace Perspective\MultisearchIo\Service\Search;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Store;
use Perspective\MultisearchIo\Api\AutocompleteCollectionCustomizerInterface;
use Perspective\MultisearchIo\Service\Cache\OperationsCache;
use Zend_Db_Expr;

class AutocompleteCollectionCustomizer implements AutocompleteCollectionCustomizerInterface
{
    const CODE_QTY = 'qty';
    const CODE_MANAGE_STOCK = 'manage_stock';
    const CODE_STOCK_STATUS = 'stock_status';
    protected $attributesToSelect = ['name', 'price', 'sku', 'entity_id'];

    public function __construct(
        protected readonly ScopeConfigInterface $scopeConfig,
        protected readonly Product $productResource,
        protected readonly OperationsCache $cache,
        protected array $additionalAttributesToSelect = []
    )
    {
        $this->attributesToSelect = array_merge($this->attributesToSelect, $additionalAttributesToSelect);
    }

    /**
     * @inheritDoc
     */
    public function execute(Collection $collection)
    {
        $richChips = $this->getRichChipsAttributeCodes();
        $attributeToSelect = array_merge($this->attributesToSelect, $richChips);
        $collection->addAttributeToSelect($attributeToSelect);
        $collection->getSelect()->joinLeft(
            ['css' => 'cataloginventory_stock_status'],
            'e.entity_id = css.product_id',
            [self::CODE_QTY, self::CODE_STOCK_STATUS]
        );
        $collection->getSelect()->joinLeft(
            ['cisi' => 'cataloginventory_stock_item'],
            'e.entity_id = cisi.product_id',
            [self::CODE_MANAGE_STOCK => new Zend_Db_Expr('IF(cisi.use_config_manage_stock = 1, 1, cisi.manage_stock)')]
        );
    }

    /**
     * @inheritDoc
     */
    public function getInflateCodes()
    {
        return array_merge($this->attributesToSelect, [self::CODE_QTY, self::CODE_MANAGE_STOCK, self::CODE_STOCK_STATUS], $this->getRichChipsAttributeCodes());
    }

    /**
     * @return array
     */
    public function getRichChipsAttributeCodes(): array
    {
        $richChips = $this->scopeConfig->getValue('perspective_multisearch_io/autocomplete/chips');
        $richChipsArr = explode(',', $richChips);
        return $richChipsArr ?: [];
    }


    /**
     * @inheritDoc
     */
    public function processLabel($attributeCode, $attributeOptionIdOrValue, $product)
    {
        $cacheId = $this->cache->getCacheId('acmplt_lbl_' . $attributeCode . '_' . $attributeOptionIdOrValue);
        if ($cacheResult = $this->cache->load($cacheId)) {
            return $cacheResult;
        }
        $attribute = $this->getAttributeByCode($attributeCode);
        if ($attribute && $attribute->usesSource()) {
            // Важкий по часу варіант
            $result = $attribute->getFrontend()->getOption($attributeOptionIdOrValue);
        } else {
            $result = $attributeOptionIdOrValue;
        }

        $this->cache->save($result, $cacheId, tags: [$product->getId()]);
        return $result;
    }

    protected function getAttributeByCode(string $attributeCode)
    {
        return $this->productResource->getAttribute($attributeCode);
    }

}
