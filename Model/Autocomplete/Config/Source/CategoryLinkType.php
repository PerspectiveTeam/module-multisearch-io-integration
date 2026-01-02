<?php

declare(strict_types=1);

namespace Perspective\MultisearchIo\Model\Autocomplete\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CategoryLinkType implements OptionSourceInterface
{

    const MAGENTO_2_URL_REWRITE = 'magento_2_url_rewrite';
    const MULTISEARCH_IO_CATEGORY_ID_PLUS_QUERY = 'multisearch_io_category_id_plus_query';

    public function toOptionArray()
    {
        return [
            [
                'label' => 'Magento 2 Url Rewrite',
                'value' => self::MAGENTO_2_URL_REWRITE
            ],
            [
                'label' => 'Multisearch.io Category ID + Query',
                'value' => self::MULTISEARCH_IO_CATEGORY_ID_PLUS_QUERY,
            ]
        ];
    }
}
