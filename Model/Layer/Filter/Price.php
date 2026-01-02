<?php

namespace Perspective\MultisearchIo\Model\Layer\Filter;

use Magento\Framework\App\ObjectManager;

class Price extends \Magento\Catalog\Model\Layer\Filter\Price
{
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price $resource,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Search\Dynamic\Algorithm $priceAlgorithm,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory $algorithmFactory,
        \Perspective\MultisearchIo\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $originalDataProviderFactory,
        private readonly \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository,
        array $data = []
    )
    {
        $this->priceCurrency = $priceCurrency;
        $this->_resource = $resource;
        $this->_customerSession = $customerSession;
        $this->_priceAlgorithm = $priceAlgorithm;
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $resource, $customerSession, $priceAlgorithm, $priceCurrency, $algorithmFactory, $originalDataProviderFactory, $data);
        $this->_requestVar = 'price';
        $this->algorithmFactory = $algorithmFactory;
        $this->dataProvider = $dataProviderFactory->create(['layer' => $this->getLayer()]);
    }

    /**
     * Apply price range filter
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        /**
         * Filter must be string: $fromPrice-$toPrice
         */
        $filter = $request->getParam($this->getRequestVar());
        if (!$filter || is_array($filter)) {
            return $this;
        }

        //validate filter
        $filterParams = explode(',', $filter);
        $filter = $this->dataProvider->validateFilter($filterParams[0]);
        if (!$filter) {
            return $this;
        }

        list($from, $to) = $filter;

        $this->dataProvider->setInterval([$from, $to]);

        $priorFilters = $this->dataProvider->getPriorFilters($filterParams);
        if ($priorFilters) {
            $this->dataProvider->setPriorIntervals($priorFilters);
        }

        $this->_applyPriceRange();
        $this->getLayer()
            ->getState()
            ->addFilter(
                $this->_createItem($this->_renderRangeLabel(empty($from) ? 0 : $from, $to), $filter)
            );

        return $this;
    }

    /**
     * Get attribute model associated with filter
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributeModel()
    {
        $attribute = $this->getData('attribute_model');
        if ($attribute === null) {
            $attribute = $this->productAttributeRepository->get('price');
            if (!$attribute) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The attribute model is not defined.'));
            }
        }
        return $attribute;
    }

    /**
     * Apply price range filter to collection
     *
     * @return $this
     */
    protected function _applyPriceRange()
    {
        $this->dataProvider->getResource()->applyPriceRange($this, $this->dataProvider->getInterval());

        return $this;
    }

    protected function _getItemsData()
    {
        $attribute = $this->getAttributeModel();
        $this->_requestVar = $attribute->getAttributeCode();

        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();
        $facets = [];
        try {
            $facets = $productCollection->getFacetedData($attribute->getAttributeCode());
        } catch (\Magento\Framework\Exception\StateException $stateException) {
            return $this->itemDataBuilder->build();
        }

        $data = [];
        if (count($facets) > 1) { // two range minimum
            foreach ($facets as $key => $aggregation) {
                if (strpos($key, 'max') !== false || strpos($key, 'min') !== false) {
                    $formattedFromPrice = $this->priceCurrency->format($aggregation['price']);
                    $data[] = [
                        'label' => $formattedFromPrice,
                        'value' => $aggregation['price'],
                        'count' => $aggregation['count'],
                        'from' => $aggregation['price'],
                        'to' => $aggregation['price'],
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Метод реально викликається - не видаляти!
     * @param string $url
     * @return array
     * @see \Mirasvit\LayeredNavigation\Block\Renderer\SliderRenderer::getSliderData
     * (if exist)
     */
    public function getSliderData(string $url): array
    {
        /** @var \Perspective\MultisearchIoCustomization\Service\SliderService $sliderService */
        $sliderService = ObjectManager::getInstance()->get('\Perspective\MultisearchIoCustomization\Service\SliderService');
        $interval = $this->dataProvider->getInterval();
        $fromToData = ['from' => reset($interval), 'to' => end($interval)];
        if (empty($fromToData) || count($fromToData) < 2 || (empty($fromToData['from']) && empty($fromToData['to']))) {
            $fromToData = null;
        }
        return $sliderService->getSliderData(
            $sliderService->getFacetedData($this),
            $this->getRequestVar(),
            $fromToData,
            $url,
            1
        );
    }

}
