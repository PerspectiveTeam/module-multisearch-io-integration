<?php

namespace Perspective\MultisearchIo\Model;

use Magento\Framework\Model\AbstractModel;
use Perspective\MultisearchIo\Model\ResourceModel\MultisearchFulltext as ResourceModel;

class MultisearchFulltext extends \Magento\Catalog\Model\Product
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'pst_ms_full_text_model';

}
