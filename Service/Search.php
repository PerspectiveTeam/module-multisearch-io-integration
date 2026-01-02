<?php

namespace Perspective\MultisearchIo\Service;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Perspective\MultisearchIo\Api\AutocompleteSearchInterface;
use Perspective\MultisearchIo\Api\AutocompleteCollectionCustomizerInterface;
use Perspective\MultisearchIo\Model\ResourceModel\MultisearchAutocomplete\Collection;
use Perspective\MultisearchIo\Model\ResourceModel\MultisearchAutocomplete\CollectionFactory;

class Search implements AutocompleteSearchInterface
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly RequestInterface $request,
        private readonly AutocompleteCollectionCustomizerInterface $collectionCustomizer
    )
    {
    }

    public function getAutocomplete()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $query = $this->request->getParam('q', '') ?: \Perspective\MultisearchIo\Api\RequestInterface::PARAM_QUERY_EMPTY;
        $collection->addSearchFilter($query);
        $collection->addPriceData();
        $collection->setPageSize(5); // перевірити чи працює ліміт
        $this->collectionCustomizer->execute($collection);
        $items = $collection->getItems();
        $dtoItems = [];
        $attributeToProcess = $this->collectionCustomizer->getInflateCodes();
        /** @var Product $item */
        foreach ($items as $item) {
            $data = [];

            foreach ($attributeToProcess as $attribute) {
                $data[$attribute] = $this->collectionCustomizer->processLabel($attribute, $item->getData($attribute), $item);
            }
            $data['url'] = $item->getProductUrl();
            $dtoItems[] = $data;
        }
        $categories = $collection->getCategories();
        $categoriesDto = [];
        foreach ($categories as $category) {
            if (!$category->getData('is_active')) {
                continue;
            }
            $categoriesDto[] = [
                'name' => $category->getData('name'),
                'url' => $category->getData('url')
            ];
        }
        return [
            [
                'items' => $dtoItems,
                'total_count' => $collection->getSize(),
                'suggestions' => $collection->getSuggestions(),
                'categories' => $categoriesDto,
                'history' => $collection->getHistory(),
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function deleteFromHistory()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $query = $this->request->getParam('q', '') ?: \Perspective\MultisearchIo\Api\RequestInterface::PARAM_QUERY_EMPTY;
        $collection->addSearchFilter($query);
        $collection->addDeletionFlag();
        // GetItems для тригера видалення
        $collection->getItems();
    }
}
