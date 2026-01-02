<?php

namespace Perspective\MultisearchIo\Model;

class MultisearchAutocomplete extends \Magento\Catalog\Model\Product
{
    /**
     * @var string
     * @SuppressWarnings("php:S116")
     */
    protected $_resourceName = \Perspective\MultisearchIo\Model\ResourceModel\MultisearchAutocomplete::class;

    /**
     * @var string
     * @SuppressWarnings("php:S116")
     */
    protected $_collectionName = \Perspective\MultisearchIo\Model\ResourceModel\MultisearchAutocomplete\Collection::class;

    /**
     * @var string
     * @SuppressWarnings("php:S116")
     */
    protected $_eventPrefix = 'pst_ms_autocomplete_model';

    protected function _construct()
    {
        $this->_init(\Perspective\MultisearchIo\Model\ResourceModel\MultisearchAutocomplete::class);
    }

}
