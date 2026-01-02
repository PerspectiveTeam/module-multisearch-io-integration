<?php

namespace Perspective\MultisearchIo\Api;

interface AutocompleteSearchInterface
{
    /**
     * Get autocomplete suggestions based on search query
     *
     * @return string[]
     */
    public function getAutocomplete();

    /**
     * @return mixed
     */
    public function deleteFromHistory();
}
