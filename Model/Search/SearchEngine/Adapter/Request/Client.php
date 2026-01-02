<?php

namespace Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter\Request;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientFactory as HttpClientFactory;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Client
{

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        ResponseFactory $responseFactory,
        Json $json,
        HttpClientFactory $httpClientFactory,
        ScopeConfigInterface $scopeConfig,
        string $apiBaseUrl = 'https://api.multisearch.io'
    )
    {
        $this->responseFactory = $responseFactory;
        $this->json = $json;
        $apiBaseUrlMod = $scopeConfig->getValue('perspective_multisearch_io/general/api_url') ?: $apiBaseUrl;
        $apiBaseUrlMod = rtrim($apiBaseUrlMod, '/');

        $this->httpClient = $httpClientFactory->create(
            [
                'config' => [
                    'base_uri' => $apiBaseUrlMod,
                ]
            ]
        );
    }

    public function fetchResponse(RequestInterface $request): ResponseInterface
    {
        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            /** @var Response $response */
            $response = $this->responseFactory->create([
                'status' => $exception->getCode(),
                'reason' => $exception->getMessage(),
            ]);
        }

        return $response;
    }

    public function decodeResponseContent(ResponseInterface $response): array
    {
        $responseContent = $response->getBody()->getContents();
        if ($response->getStatusCode() === 204 && empty($responseContent)) {
            //Цілком валідна відповідь, на запит видалення з історії пошуку
            return [];
        }
        $responseData = $this->json->unserialize($responseContent);

        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        if (!\is_array($responseData)) {
            $responseData = [];
        }

        return $responseData;
    }
}
