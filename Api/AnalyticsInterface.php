<?php

namespace Perspective\MultisearchIo\Api;

interface AnalyticsInterface
{
    /**
     * @param string $queryText
     * @return mixed
     */
    public function sendSearchEvent($queryText);
}
