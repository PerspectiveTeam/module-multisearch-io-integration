<?php

namespace Perspective\MultisearchIo\Model\Autocomplete;

class SearchResult extends \Magento\Framework\Api\Search\SearchResult
{
    const SUGGESTIONS = 'suggestions';
    const HISTORY = 'history';

    const DIRECT = 'direct';

    /**
     * @return array|null
     */
    public function getSuggestions()
    {
        return $this->_get(self::SUGGESTIONS);
    }

    /**
     * @param array $suggestions
     * @return SearchResult
     */
    public function setSuggestions($suggestions)
    {
        return $this->setData(self::SUGGESTIONS, $suggestions);
    }

    /**
     * @return array|null
     */
    public function getHistory()
    {
        return $this->_get(self::HISTORY);
    }

    /**
     * @param array $history
     * @return SearchResult
     */
    public function setHistory($history)
    {
        return $this->setData(self::HISTORY, $history);
    }

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
