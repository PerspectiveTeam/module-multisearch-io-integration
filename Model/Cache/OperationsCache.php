<?php

namespace Perspective\MultisearchIo\Model\Cache;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class OperationsCache extends TagScope
{

    /**
     * @param \Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool
     */
    public function __construct(
        FrontendPool $cacheFrontendPool
    ) {
        parent::__construct($cacheFrontendPool->get(\Perspective\MultisearchIo\Service\Cache\OperationsCache::TYPE_IDENTIFIER), \Perspective\MultisearchIo\Service\Cache\OperationsCache::CACHE_TAG);
    }
}
