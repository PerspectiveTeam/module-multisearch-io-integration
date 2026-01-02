<?php

namespace Perspective\MultisearchIo\Model\ResourceModel\Layer\Filter;

class Price extends  \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price
{
    /**
     * Apply price range filter to product collection
     *
     * @param \Magento\Catalog\Model\Layer\Filter\FilterInterface $filter
     * @param mixed $interval
     * @return $this
     */
    public function applyPriceRange(\Magento\Catalog\Model\Layer\Filter\FilterInterface $filter, $interval)
    {
        if (!$interval) {
            return $this;
        }

        list($from, $to) = $interval;
        if ($from === '' && $to === '') {
            return $this;
        }

        /** @var \Perspective\MultisearchIo\Model\ResourceModel\MultisearchFulltext\Collection $layerCollection */
        $layerCollection = $filter->getLayer()->getProductCollection();

        if ($to !== '') {
            $to = (double)$to;
            if ($from == $to) {
                $to += self::MIN_POSSIBLE_PRICE;
            }
        }
        $layerCollection->addFieldToFilter('price', array('from' => $from, 'to' => $to));

        return $this;
    }
}
