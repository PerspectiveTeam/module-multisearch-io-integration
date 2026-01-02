<?php

namespace Perspective\MultisearchIo\Service\Cache;

use Magento\Framework\App\Cache\State;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Filter\FilterManager;

/**
 * @api
 */
class OperationsCache
{
    const CACHE_LIFETIME = 86400; // 1 day

    const TYPE_IDENTIFIER = 'multisearch_operations_cache';

    const CACHE_TAG = 'MULTISEARCH_OPERATIONS_CACHE_TAG';


    /**
     * @param State $cacheState
     * @param FrontendInterface $cache
     * @param FilterManager $filterManager
     */
    public function __construct(
        private readonly State $cacheState,
        private readonly FrontendInterface $cache,
        private readonly FilterManager $filterManager
    ) {

    }

    /**
     * @param null $cacheId
     * @return array|bool|float|int|string|null
     * @SuppressWarnings("php:S1142")
     */
    public function load($cacheId = null)
    {
        if ($this->cacheState->isEnabled(self::TYPE_IDENTIFIER)) {
            /**@phpstan-ignore-next-line */
            if ($cacheId) {
                $cacheIdTranslit = $this->getCacheId($cacheId);
                return $this->cache->load($cacheIdTranslit) ? unserialize($this->cache->load($cacheIdTranslit)) : false;
            }
            $result = $this->cache->load(self::TYPE_IDENTIFIER);
            if ($result) {
                return unserialize($result);
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * @param $data
     * @param string $cacheId
     * @param int $cacheLifetime
     * @param array $tags
     * @return bool
     */
    public function save($data, $cacheId = self::TYPE_IDENTIFIER, $cacheLifetime = self::CACHE_LIFETIME, $tags = []): bool
    {
        if ($this->cacheState->isEnabled(self::TYPE_IDENTIFIER)) {
            $data = serialize($data);
            $tags = array_filter($tags);
            $this->cache->save($data, $this->getCacheId($cacheId), [self::CACHE_TAG, $this->getCacheId($cacheId), ...$tags], $cacheLifetime);
            return true;
        }
        return false;
    }

    public function remove($cacheId = self::TYPE_IDENTIFIER)
    {
        if ($this->cacheState->isEnabled(self::TYPE_IDENTIFIER)) {
            $this->cache->remove($this->getCacheId($cacheId));
        }
    }

    /**
     * @param $cacheId
     * @return string
     */
    public function getCacheId($cacheId): string
    {
        return $this->filterManager->translit(sha1($cacheId));
    }

    public function clean($tags = [])
    {
        if ($this->cacheState->isEnabled(self::TYPE_IDENTIFIER)) {
            $tags = array_filter($tags);
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $tags);
        }
    }
}
