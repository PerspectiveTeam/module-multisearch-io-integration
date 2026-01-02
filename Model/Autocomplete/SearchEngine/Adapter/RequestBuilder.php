<?php

namespace Perspective\MultisearchIo\Model\Autocomplete\SearchEngine\Adapter;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Search\Request\EmptyRequestDataException;
use Perspective\MultisearchIo\Api\RequestBuilderInterface;
use Perspective\MultisearchIo\Api\SortMappingInterface;
use Perspective\MultisearchIo\Api\UserContextInterface;
use Perspective\MultisearchIo\Model\Config;
use Magento\Framework\Search\RequestInterface;

class RequestBuilder extends DataObject implements RequestBuilderInterface
{
    /**
     * @param RequestFactory $bareRequestFactory
     * @param Config $config
     * @param UserContextInterface $userContext
     * @param DataPersistorInterface $dataPersistor
     * @param SortMappingInterface $sortMapping
     * @param array $data
     * @SuppressWarnings("php:S1068")
     */
    public function __construct(
        private readonly RequestFactory $bareRequestFactory,
        private readonly Config $config,
        private readonly UserContextInterface $userContext,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly SortMappingInterface $sortMapping,
        array $data = []
    )
    {
        parent::__construct($data);
    }


    /**
     * @param RequestInterface $request
     * @param self $requestBuilder
     * @return void
     */
    public function buildQuery(RequestInterface $request, RequestBuilderInterface $requestBuilder): void
    {
        $query = $request->getQuery();
        //Should завжди містить search_main і воно єдине.
        $requestBuilder->setData('query', $query->getShould()['search_main']->getValue());
        if (!empty($query->getShould()['is_deletion']) && boolval($query->getShould()['is_deletion']?->getValue()) === true) {
            if ($this->getData('query') === \Perspective\MultisearchIo\Api\RequestInterface::PARAM_QUERY_EMPTY) {
                $this->unsetData('query');
            }
            $requestBuilder->setData('is_deletion', true);
            return;
        }
        $requestBuilder->setData('autocomplete', 'true');

        $lang = $request->getDimensions()['lang']?->getValue() ?? 'uk';
        $requestBuilder->setData('lang', $lang);
    }

    public function createSearch($method = 'GET'): \Perspective\MultisearchIo\Api\GuzzleRequestInterface
    {
        if (strtoupper($method) === 'DELETE') {
            return $this->processDeletion();
        }
        return $this->processSearch();
    }

    private function processDeletion()
    {
        $this->unsetData('location');
        $query = array_merge([
            \Perspective\MultisearchIo\Api\RequestInterface::PARAM_ID => $this->config->getApiId(),
            \Perspective\MultisearchIo\Api\RequestInterface::PARAM_UID => $this->userContext->getUserId(),
        ], $this->getData());
        $query = array_filter($query, fn($value) => $value !== null && $value !== '');

        $uri = http_build_query($query);

        $request = $this->bareRequestFactory->create([
            'method' => 'DELETE',
            'uri' => '/history/?' . $uri,
        ]);
        $this->unsetData();
        return $request;
    }

    /**
     * @return mixed
     */
    private function processSearch()
    {
        /** Це хак для підтримки пустого запиту, оскільки для того, щоб дійти до цього класу використовується
         * @see \Magento\Framework\Search\Request\Cleaner::clean
         * котрий викидує @see EmptyRequestDataException
         * якщо запит пустий.
         */
        if ($this->getData('query') === \Perspective\MultisearchIo\Api\RequestInterface::PARAM_QUERY_EMPTY) {
            $this->unsetData();
        }
        $query = array_merge([
            \Perspective\MultisearchIo\Api\RequestInterface::PARAM_ID => $this->config->getApiId(),
            \Perspective\MultisearchIo\Api\RequestInterface::PARAM_UID => $this->userContext->getUserId(),
        ], $this->getData());
        $query = array_filter($query, fn($value) => $value !== null && $value !== '');

        $uri = http_build_query($query);

        $request = $this->bareRequestFactory->create([
            'method' => 'GET',
            'uri' => '/?' . $uri,
        ]);
        $this->dataPersistor->clear(\Perspective\MultisearchIo\Api\RequestInterface::CURRENT_MULTISEARCH_REQUEST_URI);
        $this->dataPersistor->set(\Perspective\MultisearchIo\Api\RequestInterface::CURRENT_MULTISEARCH_REQUEST_URI, $uri);

        $this->unsetData();
        return $request;
    }
}
