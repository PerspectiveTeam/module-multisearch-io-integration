<?php

namespace Perspective\MultisearchIo\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Perspective\MultisearchIo\Api\RequestInterface;

class MultisearchViewModel implements ArgumentInterface
{
    public function __construct(
        private readonly DataPersistorInterface $dataPersistor,
        private readonly ScopeConfigInterface $scopeConfig,
    )
    {
    }

    /**
     * Виключно для спрощення дебагу
     * @return mixed|string
     */
    public function getMultisearchRequestUri()
    {
        return base64_encode($this->dataPersistor->get(RequestInterface::CURRENT_MULTISEARCH_REQUEST_URI) ?? '');
    }

    /**
     * @return string|null
     */
    public function getMultisearchTrackerId()
    {
        return $this->scopeConfig->getValue('perspective_multisearch_io/analytics/tracking_id');
    }

}
