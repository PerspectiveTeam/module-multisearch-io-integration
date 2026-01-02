<?php

namespace Perspective\MultisearchIo\Model\Analytics;

use Magento\Framework\Exception\LocalizedException;
use Perspective\MultisearchIo\Api\AnalyticsInterface;

class Analytics implements AnalyticsInterface
{

    /**
     * @inheritDoc
     */
    public function sendSearchEvent($queryText)
    {
        throw new LocalizedException(__('You have to implement your own Analytics service'));
    }
}
