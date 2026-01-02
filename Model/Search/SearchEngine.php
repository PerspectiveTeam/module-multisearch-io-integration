<?php

namespace Perspective\MultisearchIo\Model\Search;

use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Search\Model\AdapterFactory;

class SearchEngine extends \Magento\Search\Model\SearchEngine
{
    /**
     * @var AdapterInterface
     */
    private $adapter = null;

    public function __construct(AdapterFactory $originalAdapterFactory,
        private readonly \Perspective\MultisearchIo\Model\Search\SearchEngine\AdapterFactory $adapterFactory
    )
    {
        parent::__construct($originalAdapterFactory);
    }
    /**
     * {@inheritdoc}
     */
    public function search(RequestInterface $request)
    {
        return $this->getConnection()->query($request);
    }

    /**
     * Get adapter
     *
     * @return AdapterInterface
     */
    protected function getConnection()
    {
        if ($this->adapter === null) {
            $this->adapter = $this->adapterFactory->create();
        }
        return $this->adapter;
    }
}
