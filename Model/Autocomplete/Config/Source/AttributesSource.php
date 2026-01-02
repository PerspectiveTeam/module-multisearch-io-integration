<?php

declare(strict_types=1);

namespace Perspective\MultisearchIo\Model\Autocomplete\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Eav\Model\Entity;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection;

class AttributesSource implements OptionSourceInterface
{

    public function __construct(
        private readonly Entity $eavEntity,
        private readonly Collection $attributeCollection
    )
    {
    }

    public function toOptionArray()
    {
        $entityTypeId = $this->eavEntity->setType('catalog_product')->getTypeId();

        $attributes = $this->attributeCollection->setEntityTypeFilter($entityTypeId)
            ->addFieldToFilter('frontend_input', ['select', 'multiselect']);

        $result = [];

        /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
        foreach ($attributes as $attribute) {
            if ($attribute->getStoreLabel()) {
                $result[] = [
                    'label' => $attribute->getStoreLabel() . ' (' . $attribute->getAttributeCode() . ')',
                    'value' => $attribute->getAttributeCode(),
                ];
            }
        }

        return $result;
    }
}
