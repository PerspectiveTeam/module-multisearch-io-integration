<?php

namespace Perspective\MultisearchIo\Model\Search\SearchEngine;

use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Perspective\MultisearchIo\Api\RequestBuilderInterface;
use Perspective\MultisearchIo\Api\ResponseConverterInterface;
use Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter\Request\Client;

class Adapter implements AdapterInterface
{
    public function __construct(
        private readonly RequestBuilderInterface $requestBuilder,
        private readonly Client $client,
        private readonly ResponseConverterInterface $responseConverter
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function query(RequestInterface $request)
    {
        $requestBuilder = $this->requestBuilder;
        $requestBuilder->buildQuery($request, $requestBuilder);
        $request = $requestBuilder->createSearch();
        $response = $this->client->fetchResponse($request);
        return $this->responseConverter->convert($response);
    }
}
