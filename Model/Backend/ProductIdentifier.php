<?php

namespace Perspective\MultisearchIo\Model\Backend;

use Magento\Framework\Data\OptionSourceInterface;

class ProductIdentifier implements OptionSourceInterface
{

    public const ENTITY_ID = 'entity_id';

    public const SKU = 'sku';

    /**
     * @noinspection ReturnTypeCanBeDeclaredInspection
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ENTITY_ID,
                'label' => __('Entity ID'),
            ],
            [
                'value' => self::SKU,
                'label' => __('SKU'),
            ],
        ];
    }
}
