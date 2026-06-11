<?php

namespace Perspective\MultisearchIo\Model\Autocomplete\Response;

use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\Document;

class QueryResponse extends \Magento\Framework\Search\Response\QueryResponse implements \Magento\Framework\Search\ResponseInterface
{

    /**
     * @param Document[] $documents
     * @param AggregationInterface $aggregations
     * @param int $total
     * @param array|null $suggestions
     * @param array|null $history
     * @param array|null $direct
     * @SuppressWarnings("php:S1068")
     */
    public function __construct(
        array $documents,
        AggregationInterface $aggregations,
        int $total = 0,
        private ?array $suggestions = [],
        private ?array $history = [],
        private ?array $direct = []
    )
    {
        parent::__construct($documents, $aggregations, $total);
    }

    /**
     * @return array|null
     */
    public function getSuggestions()
    {
        return $this->suggestions;
    }

    /**
     * @return array|null
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * @return array|null
     */
    public function getDirect()
    {
        return $this->direct;
    }
}
