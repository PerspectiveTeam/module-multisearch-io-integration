<?php

namespace Perspective\MultisearchIo\Model\Search\Response;

use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\Document;

class QueryResponse extends \Magento\Framework\Search\Response\QueryResponse implements \Magento\Framework\Search\ResponseInterface
{
    /**
     * @param Document[] $documents
     * @param AggregationInterface $aggregations
     * @param int $total
     * @param array|null $direct
     * @SuppressWarnings("php:S107")
     */
    public function __construct(
        protected $documents,
        protected $aggregations,
        private int $total = 0,
        private ?array $direct = []
    ) {
        parent::__construct($documents, $aggregations, $total);
    }

    /**
     * @return array|null
     */
    public function getDirect()
    {
        return $this->direct;
    }
}
