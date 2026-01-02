<?php

namespace Perspective\MultisearchIo\Model\Autocomplete;

use Magento\Framework\Api\Search\DocumentFactory;
use Magento\Framework\Api\Search\SearchResultFactory;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Search\ResponseInterface;

class SearchResponseBuilder extends \Magento\Framework\Search\SearchResponseBuilder
{
    /**
     * @var DocumentFactory
     * @deprecated 100.1.0
     */
    private $documentFactory;

    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @param SearchResultFactory $searchResultFactory
     * @param DocumentFactory $documentFactory
     */
    public function __construct(
        SearchResultFactory $searchResultFactory,
        DocumentFactory $documentFactory
    )
    {
        $this->documentFactory = $documentFactory;
        $this->searchResultFactory = $searchResultFactory;
        parent::__construct($searchResultFactory, $documentFactory);
    }

    /**
     * Build search result by search response.
     *
     * @param ResponseInterface $response
     * @return SearchResultInterface
     */
    public function build(ResponseInterface $response)
    {
        /** @var \Perspective\MultisearchIo\Model\Autocomplete\SearchResult $searchResult */
        $searchResult = $this->searchResultFactory->create();

        $documents = iterator_to_array($response);
        $searchResult->setItems($documents);
        $searchResult->setAggregations($response->getAggregations());
        $count = method_exists($response, 'getTotal')
            ? $response->getTotal()
            : count($documents);
        $searchResult->setTotalCount($count);
        $searchResult->setSuggestions(method_exists($response, 'getSuggestions') ? $response->getSuggestions() : []);
        $searchResult->setHistory(method_exists($response, 'getHistory') ? $response->getHistory() : []);
        return $searchResult;
    }
}
