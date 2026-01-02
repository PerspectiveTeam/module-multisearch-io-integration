<?php

namespace Perspective\MultisearchIo\Api;

use Psr\Http\Message\ResponseInterface;

interface ResponseConverterInterface
{
    public function convert(ResponseInterface $response): \Magento\Framework\Search\ResponseInterface;
}
