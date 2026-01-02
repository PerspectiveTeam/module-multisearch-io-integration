<?php

namespace Perspective\MultisearchIo\Model\Autocomplete;

class SearchResult extends \Magento\Framework\Api\Search\SearchResult
{
    const SUGGESTIONS = 'suggestions';
    const HISTORY = 'history';

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
}
