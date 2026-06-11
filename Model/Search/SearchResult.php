<?php

namespace Perspective\MultisearchIo\Model\Search;

class SearchResult extends \Magento\Framework\Api\Search\SearchResult
{
    public const DIRECT = 'direct';

    /**
     * @return array|null
     */
    public function getDirect()
    {
        return $this->_get(self::DIRECT);
    }

    /**
     * @param array $direct
     * @return SearchResult
     */
    public function setDirect($direct)
    {
        return $this->setData(self::DIRECT, $direct);
    }
}
