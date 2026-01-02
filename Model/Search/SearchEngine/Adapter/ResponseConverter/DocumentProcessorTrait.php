<?php

namespace Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter\ResponseConverter;

use Magento\Framework\Api\Search\DocumentFactory;

trait DocumentProcessorTrait
{
    private array $items = [];

    public function __construct(
        protected readonly DocumentFactory $documentFactory,
    )
    {
    }

    public function getProductIds($responseData)
    {
        $this->items = [];
        if (isset($responseData['results']['ids'])) {
            $productIds = array_values($responseData['results']['ids']);
        }
        if (isset($responseData['results']['items'])) {
            $productIds = array_map(function ($item) {
                $this->items[$item['id']] = $item;
                return $item['id'];
            }, $responseData['results']['items']);
        }
        if (empty($productIds)) {
            $productIds = [];
        }
        return $productIds;
    }

    public function getDocumentsByProductIds($productIds)
    {
        return array_map(function ($index, $id) {
            $presenceValue = $this->items[$id]['is_presence'] ?? $this->items[$id]['presence'] ?? false;
            return $this->documentFactory
                ->create()
                ->setId($id)
                ->setCustomAttribute(
                    'score',
                    $this->attributeFactory->create()->setValue($index + 1)
                )
                ->setCustomAttribute('presence', $this->attributeFactory->create()->setValue($presenceValue));
        }, array_reverse(array_keys($productIds)), $productIds);
    }
}
