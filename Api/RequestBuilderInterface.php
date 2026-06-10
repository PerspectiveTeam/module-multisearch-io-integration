<?php

namespace Perspective\MultisearchIo\Api;
use Magento\Framework\Search\RequestInterface;

interface RequestBuilderInterface
{
    const X_FORWARDED_FOR_HEADER = 'X-Forwarded-For';
    public function createSearch($method = 'GET'): \Perspective\MultisearchIo\Api\GuzzleRequestInterface;

    public function buildQuery(RequestInterface $request, RequestBuilderInterface $requestBuilder): void;

}
