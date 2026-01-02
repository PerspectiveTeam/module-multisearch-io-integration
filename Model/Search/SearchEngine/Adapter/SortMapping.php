<?php

namespace Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter;

use Perspective\MultisearchIo\Api\SortMappingInterface;

class SortMapping implements SortMappingInterface
{

    /**
     * Дефолтні М2 сортування.
     * Щоб використовувати кастомні сортування, потрібно створити окремий клас і прокинути через DI в
     * @see \Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter
     *
     * Доступні, додаткові, кастомні сортування:
     * name, ordering, created_at, profit, presence
     * @inheritDoc
     */
    public function map($sort)
    {
        $fieldValueMap = [
            'price' => 'price',
            'relevance' => 'relevance',
        ];
        return $fieldValueMap[$sort] ?? 'relevance';
    }

}
