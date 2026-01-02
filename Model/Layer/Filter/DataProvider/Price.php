<?php

namespace Perspective\MultisearchIo\Model\Layer\Filter\DataProvider;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\ResourceModel\Layer\Filter\Price as LayerFilterPriceOriginal;
use Perspective\MultisearchIo\Model\ResourceModel\Layer\Filter\Price as LayerFilterPrice;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;

class Price extends \Magento\Catalog\Model\Layer\Filter\DataProvider\Price
{
    public function __construct(
        Layer $layer,
        Registry $coreRegistry,
        ScopeConfigInterface $scopeConfig,
        LayerFilterPriceOriginal $resourceOrig,
        protected LayerFilterPrice $resource
    )
    {
        parent::__construct($layer, $coreRegistry, $scopeConfig, $resourceOrig);
    }

    /**
     * Get Resource model for price filter
     *
     * @return \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price
     */
    public function getResource()
    {
        return $this->resource;
    }

}
