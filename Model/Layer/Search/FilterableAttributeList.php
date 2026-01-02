<?php

namespace Perspective\MultisearchIo\Model\Layer\Search;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;

class FilterableAttributeList extends \Magento\Catalog\Model\Layer\Search\FilterableAttributeList
{

    public function getList()
    {
        /** @var $collection Collection */
        $collection = $this->collectionFactory->create();
        $collection->setItemObjectClass(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class);
        $collection = $this->_prepareAttributeCollection($collection);

        return $collection;
    }

    protected function _prepareAttributeCollection($collection)
    {
        $collection->addVisibleFilter();
        $collection->addIsSearchableFilter();
        $collection->addIsFilterableFilter();
        return $collection;
    }
}
