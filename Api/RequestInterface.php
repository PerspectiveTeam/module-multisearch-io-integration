<?php

namespace Perspective\MultisearchIo\Api;

interface RequestInterface
{
    public const METHOD = 'method';

    public const PATH = 'path';

    public const PARAM_ID = 'id';

    public const PARAM_QUERY = 'query';

    public const PARAM_UID = 'uid';

    public const PARAM_T = 't';

    public const PARAM_LANG = 'lang';

    public const PARAM_GROUP_BY_CATEGORIES = 'categories';

    public const PARAM_LIMIT = 'limit';

    public const PARAM_OFFSET = 'offset';

    public const PARAM_FIELDS = 'fields';

    public const PARAM_SORT = 'sort';
    public const PARAM_FILTERS = 'filters';

    public const GROUP_BY_CATEGORIES_NONE = '0';

    public const GROUP_BY_CATEGORIES_ALL = 'all';

    public const PARAM_QUERY_EMPTY = '{{empty}}';
    public const CURRENT_MULTISEARCH_REQUEST_URI = 'current_multisearch_request_uri';
}
