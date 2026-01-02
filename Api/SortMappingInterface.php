<?php

namespace Perspective\MultisearchIo\Api;

interface SortMappingInterface
{
    /**
     * @param string $sort
     * @return string
     */
    public function map($sort);
}
