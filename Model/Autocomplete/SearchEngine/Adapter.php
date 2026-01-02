<?php

namespace Perspective\MultisearchIo\Model\Autocomplete\SearchEngine;

use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Perspective\MultisearchIo\Api\RequestBuilderInterface;
use Perspective\MultisearchIo\Api\ResponseConverterInterface;
use Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter\Request\Client;

class Adapter extends \Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter implements AdapterInterface
{
    /**
     * @param RequestBuilderInterface $requestBuilder
     * @param Client $client
     * @param ResponseConverterInterface $responseConverter
     * @SuppressWarnings("php:S1185")
     */
    public function __construct(
        private readonly RequestBuilderInterface $requestBuilder,
        private readonly Client $client,
        private readonly ResponseConverterInterface $responseConverter
    )
    {
        parent::__construct($requestBuilder, $client, $responseConverter);
    }

    /**
     * @inheritDoc
     */
    public function query(RequestInterface $request)
    {
        $methodToOperate = 'GET';
        $requestBuilder = $this->requestBuilder;
        $requestBuilder->buildQuery($request, $requestBuilder);
        if ($requestBuilder->getData('is_deletion') === true) {
            $methodToOperate = 'DELETE';
            $requestBuilder->unsetData('is_deletion');
        }
        $request = $requestBuilder->createSearch($methodToOperate);
        $response = $this->client->fetchResponse($request);
        return $this->responseConverter->convert($response);
    }
}
