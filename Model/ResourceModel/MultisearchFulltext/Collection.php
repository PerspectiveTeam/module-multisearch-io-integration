<?php

namespace Perspective\MultisearchIo\Model\ResourceModel\MultisearchFulltext;

use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Catalog\Model\Indexer\Product\Flat\State;
use Magento\Catalog\Model\Indexer\Product\Price\PriceTableResolver;
use Magento\Catalog\Model\Product\Gallery\ReadHandler as GalleryReadHandler;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Catalog\Model\ResourceModel\Category;
use Magento\Catalog\Model\ResourceModel\Helper;
use Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitationFactory;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Catalog\Model\ResourceModel\Url;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchCriteriaResolverFactory;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchCriteriaResolverInterface;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierFactory;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierInterface;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\TotalRecordsResolverFactory;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\TotalRecordsResolverInterface;
use Magento\CatalogSearch\Model\Search\RequestGenerator;
use Magento\CatalogUrlRewrite\Model\Storage\DbStorage;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\EntityFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultFactory;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Indexer\DimensionFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Module\Manager;
use Magento\Framework\Search\Request\EmptyRequestDataException;
use Magento\Framework\Search\Request\NonExistingRequestNameException;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Validator\UniversalFactory;
use Magento\Search\Api\SearchInterface;
use Magento\Store\Model\StoreManagerInterface;
use Perspective\MultisearchIo\Model\MultisearchFulltext as Model;
use Perspective\MultisearchIo\Model\ResourceModel\MultisearchFulltext as ResourceModel;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{

    /**
     * @var string
     */
    private $queryText;

    /**
     * @var string
     * @SuppressWarnings("php:S116")
     */
    protected $_eventPrefix = 'pst_ms_full_text_collection';

    /**
     * @var SearchResultInterface
     */
    private $searchResult;
    /**
     * @var array
     */
    private $searchOrders;

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Config $eavConfig,
        ResourceConnection $resource,
        EntityFactory $eavEntityFactory,
        Helper $resourceHelper,
        UniversalFactory $universalFactory,
        StoreManagerInterface $storeManager,
        Manager $moduleManager,
        State $catalogProductFlatState,
        ScopeConfigInterface $scopeConfig,
        OptionFactory $productOptionFactory,
        Url $catalogUrl,
        TimezoneInterface $localeDate,
        Session $customerSession,
        DateTime $dateTime,
        GroupManagementInterface $groupManagement,
        private FilterBuilder $filterBuilder,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly TotalRecordsResolverFactory $totalRecordsResolverFactory,
        private readonly SearchCriteriaResolverFactory $searchCriteriaResolverFactory,
        private readonly SearchResultFactory $searchResultFactory,
        private readonly SearchResultApplierFactory $searchResultApplierFactory,
        private readonly SearchInterface $search,
        private $searchRequestName = 'multisearch_io_search',
        AdapterInterface $connection = null,
        ProductLimitationFactory $productLimitationFactory = null,
        MetadataPool $metadataPool = null,
        TableMaintainer $tableMaintainer = null,
        PriceTableResolver $priceTableResolver = null,
        DimensionFactory $dimensionFactory = null,
        Category $categoryResourceModel = null,
        DbStorage $urlFinder = null,
        GalleryReadHandler $productGalleryReadHandler = null,
        Gallery $mediaGalleryResource = null,

    )
    {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $eavConfig, $resource, $eavEntityFactory, $resourceHelper, $universalFactory, $storeManager, $moduleManager, $catalogProductFlatState, $scopeConfig, $productOptionFactory, $catalogUrl, $localeDate, $customerSession, $dateTime, $groupManagement, $connection, $productLimitationFactory, $metadataPool, $tableMaintainer, $priceTableResolver, $dimensionFactory, $categoryResourceModel, $urlFinder, $productGalleryReadHandler, $mediaGalleryResource);
    }

    /**
     * Initialize collection model.
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }

    /**
     * @inheritdoc
     * @since 101.0.4
     */
    public function _loadEntities($printQuery = false, $logQuery = false)
    {
        $this->getEntity();

        $this->printLogQuery($printQuery, $logQuery);
        /**
         * Prepare select query
         * @var string $query
         */
        $query = $this->getSelect();
        try {
            $rows = $this->_fetchAll($query);
        } catch (\Exception $e) {
            $this->printLogQuery(false, true, $query);
            throw $e;
        }

        $position = 0;
        foreach ($rows as $value) {
            if ($this->getFlag('has_category_filter')) {
                $value['cat_index_position'] = $position++;
            }
            $object = $this->getNewEmptyItem()->setData($value);
            $this->addItem($object);
            if (isset($this->_itemsById[$object->getId()])) {
                $this->_itemsById[$object->getId()][] = $object;
            } else {
                $this->_itemsById[$object->getId()] = [$object];
            }
        }
        if ($this->getFlag('has_category_filter')) {
            $this->setFlag('has_category_filter', false);
        }

        return $this;
    }

    /**
     * Apply attribute filter to facet collection
     *
     * @param string $field
     * @param mixed|null $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($this->searchResult !== null) {
            throw new RuntimeException('Illegal state for multisearch collection');
        }

        $this->getSearchCriteriaBuilder();
        $this->getFilterBuilder();
        if (!is_array($condition) || !in_array(key($condition), ['from', 'to'], true)) {
            $this->filterBuilder->setField($field);
            $this->filterBuilder->setValue($condition);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        } else {
            if (!empty($condition['from'])) {
                $this->filterBuilder->setField("{$field}.from");
                $this->filterBuilder->setValue($condition['from']);
                $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
            }
            if (!empty($condition['to'])) {
                $this->filterBuilder->setField("{$field}.to");
                $this->filterBuilder->setValue($condition['to']);
                $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
            }
        }
        return $this;
    }

    public function addCategoryIdFilter(array $categoryIds)
    {
        $this->getSearchCriteriaBuilder();
        $this->getFilterBuilder();

        // Фільтр категорій у Multisearch.io називається 't' (＃￣ω￣)
        // не плутати за categories - відповідає за групування по категоріях у multisearch.io
        $this->filterBuilder->setField('t');
        $this->filterBuilder->setValue($categoryIds);
        $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        return $this;
    }


    protected function _renderFiltersBefore()
    {
        if ($this->isLoaded()) {
            return;
        }

        if ($this->searchRequestName === 'multisearch_io_search'
            || ($this->queryText && strlen(trim($this->queryText)))
        ) {
            $this->prepareSearchTermFilter();

            $searchCriteria = $this->getSearchCriteriaResolver()->resolve();
            try {
                $this->searchResult = $this->getSearch()->search($searchCriteria);
                $this->_totalRecords = $this->getTotalRecordsResolver($this->searchResult)->resolve();
            } catch (EmptyRequestDataException $e) {
                $this->searchResult = $this->createEmptyResult();
            } catch (NonExistingRequestNameException $e) {
                $this->_logger->error($e->getMessage());
                throw new LocalizedException(__('An error occurred. For details, see the error log.'));
            }
        } else {
            $this->searchResult = $this->createEmptyResult();
        }

        $this->getSearchResultApplier($this->searchResult)->apply();
        parent::_renderFiltersBefore();
    }

    /**
     * Prepare search term filter for text query.
     *
     * @return void
     */
    private function prepareSearchTermFilter(): void
    {
        if ($this->queryText) {
            $this->filterBuilder->setField('search_term');
            $this->filterBuilder->setValue($this->queryText);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        }
    }

    /**
     * Add search query filter
     *
     * @param string $query
     * @return $this
     */
    public function addSearchFilter($query)
    {
        $this->queryText = trim($this->queryText . ' ' . $query);
        return $this;
    }

    /**
     * Add attribute to sort order.
     *
     * @param string $attribute
     * @param string $dir
     * @return $this
     */
    public function addAttributeToSort($attribute, $dir = self::SORT_ORDER_ASC)
    {
        $this->setOrder($attribute, $dir);
        return $this;
    }

    /**
     * Set sort order for search query.
     *
     * @param string $field
     * @param string $direction
     * @return void
     */
    private function setSearchOrder($field, $direction)
    {
        $field = (string)$this->_getMappedField($field);
        $direction = strtoupper($direction) == self::SORT_ORDER_ASC ? self::SORT_ORDER_ASC : self::SORT_ORDER_DESC;

        $this->searchOrders[$field] = $direction;
    }

    /**
     * @inheritdoc
     */
    public function setOrder($attribute, $dir = Select::SQL_DESC)
    {
        $this->setSearchOrder($attribute, $dir);

        return $this;
    }

    /**
     * Set search criteria builder.
     *
     * @return SearchCriteriaBuilder
     * @deprecated 100.1.0
     */
    private function getSearchCriteriaBuilder()
    {
        if ($this->searchCriteriaBuilder === null) {
            $this->searchCriteriaBuilder = ObjectManager::getInstance()
                ->get(SearchCriteriaBuilder::class);
        }
        return $this->searchCriteriaBuilder;
    }

    /**
     * Get search criteria resolver.
     *
     * @return SearchCriteriaResolverInterface
     */
    private function getSearchCriteriaResolver(): SearchCriteriaResolverInterface
    {
        return $this->searchCriteriaResolverFactory->create(
            [
                'builder' => $this->getSearchCriteriaBuilder(),
                'collection' => $this,
                'searchRequestName' => $this->searchRequestName,
                'currentPage' => (int)$this->_curPage,
                'size' => $this->getPageSize(),
                'orders' => $this->searchOrders,
            ]
        );
    }

    /**
     * Get total records resolver.
     *
     * @param SearchResultInterface $searchResult
     * @return TotalRecordsResolverInterface
     */
    private function getTotalRecordsResolver(SearchResultInterface $searchResult): TotalRecordsResolverInterface
    {
        return $this->totalRecordsResolverFactory->create(
            [
                'searchResult' => $searchResult,
            ]
        );
    }

    /**
     * Get search.
     *
     * @return SearchInterface
     */
    private function getSearch()
    {
        return $this->search;
    }

    /**
     * Create empty search result
     *
     * @return SearchResultInterface
     */
    private function createEmptyResult()
    {
        return $this->searchResultFactory->create()->setItems([]);
    }

    /**
     * Get filter builder.
     *
     * @return FilterBuilder
     * @deprecated 100.1.0
     */
    private function getFilterBuilder()
    {
        if ($this->filterBuilder === null) {
            $this->filterBuilder = ObjectManager::getInstance()->get(FilterBuilder::class);
        }
        return $this->filterBuilder;
    }

    /**
     * Get search result applier.
     *
     * @param SearchResultInterface $searchResult
     * @return SearchResultApplierInterface
     */
    private function getSearchResultApplier(SearchResultInterface $searchResult): SearchResultApplierInterface
    {
        return $this->searchResultApplierFactory->create(
            [
                'collection' => $this,
                'searchResult' => $searchResult,
                /** This variable sets by serOrder method, but doesn't have a getter method. */
                'orders' => $this->_orders,
                'size' => $this->getPageSize(),
                'currentPage' => (int)$this->_curPage,
            ]
        );
    }

    /**
     * Return field faceted data from faceted search result
     *
     * @param string $field
     * @return array
     * @throws StateException
     */
    public function getFacetedData($field)
    {
        $this->_renderFilters();
        $result = [];
        $aggregations = $this->searchResult->getAggregations();
        // This behavior is for case with empty object when we got EmptyRequestDataException
        if (null !== $aggregations) {
            $bucket = $aggregations->getBucket($field . RequestGenerator::BUCKET_SUFFIX);
            if ($bucket) {
                foreach ($bucket->getValues() as $value) {
                    $metrics = $value->getMetrics();
                    $result[$metrics['value']] = $metrics;
                }
            } else {
                throw new StateException(__("The bucket doesn't exist."));
            }
        }
        return $result;
    }
}
