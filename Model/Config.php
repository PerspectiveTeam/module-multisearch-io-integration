<?php

namespace Perspective\MultisearchIo\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{

    public const GROUP_GENERAL = 'general';



    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfig(string $group, string $key)
    {
        return $this->scopeConfig->getValue("perspective_multisearch_io/$group/$key");
    }

    public function isEnabled()
    {
        return $this->getConfig(self::GROUP_GENERAL, 'enabled') ?? false;
    }

    public function getApiId()
    {
        return $this->getConfig(self::GROUP_GENERAL, 'api_id');
    }

}
