<?php

namespace Perspective\MultisearchIo\Model\ResourceModel;

class MultisearchAutocomplete extends \Magento\Catalog\Model\ResourceModel\Product
{
    /**
     * @var string
     * @SuppressWarnings("php:S116")
     */
    protected $_eventPrefix = 'pst_ms_autocomplete_stub_tablename_resource_model';


    public function delete($object)
    {
        throw new \Magento\Framework\Exception\CouldNotSaveException('You cannot delete autocomplete suggestions using models');
    }
    public function save(\Magento\Framework\Model\AbstractModel $object)
    {
        throw new \Magento\Framework\Exception\CouldNotSaveException('You cannot save autocomplete suggestions using models');
    }

}
