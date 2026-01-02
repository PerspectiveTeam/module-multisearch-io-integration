<?php

namespace Perspective\MultisearchIo\Api;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

interface AutocompleteCollectionCustomizerInterface
{
    /**
     * @param Collection $collection
     * @return void
     */
    public function execute(Collection $collection);

    /**
     * @return array<string>
     */
    public function getInflateCodes();

    /**
     * @param string $attributeCode
     * @param string|int $attributeOptionIdOrValue
     * @param Product $product
     * @return string
     */
    public function processLabel($attributeCode, $attributeOptionIdOrValue, $product);

}
