<?php

namespace Perspective\MultisearchIo\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class MultisearchFulltext extends \Magento\Catalog\Model\ResourceModel\Product
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'pst_ms_full_text_stub_tablename_resource_model';

}
