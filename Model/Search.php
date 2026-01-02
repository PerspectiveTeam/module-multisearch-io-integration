<?php

namespace Perspective\MultisearchIo\Model;

use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Search\Request\Builder;
use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\Search\SearchEngineInterface;
use Magento\Framework\Search\SearchResponseBuilder;
use Magento\Search\Api\SearchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use ReflectionClass;

class Search extends \Magento\Framework\Search\Search implements SearchInterface
{

    public function __construct(
        private readonly Builder $requestBuilder,
        private readonly ScopeResolverInterface $scopeResolver,
        private readonly SearchEngineInterface $searchEngine,
        private readonly SearchResponseBuilder $searchResponseBuilder,
        private readonly ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($requestBuilder, $scopeResolver, $searchEngine, $searchResponseBuilder);
    }

    /**
     * @inheritdoc
     */
    public function search(SearchCriteriaInterface $searchCriteria)
    {
        $this->requestBuilder->setRequestName($searchCriteria->getRequestName());

        $scope = $this->scopeResolver->getScope()->getId();
        $this->requestBuilder->bindDimension('scope', $scope);

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $this->addFieldToFilter($filter->getField(), $filter->getValue());
            }
        }

        $this->requestBuilder->setFrom($searchCriteria->getCurrentPage() * $searchCriteria->getPageSize());
        $this->requestBuilder->setSize($searchCriteria->getPageSize());

        /**
         * This added in Backward compatibility purposes.
         * Temporary solution for an existing API of a fulltext search request builder.
         * It must be moved to different API.
         * Scope to split Search request builder API in MC-16461.
         */
        if (method_exists($this->requestBuilder, 'setSort')) {
            $this->requestBuilder->setSort($searchCriteria->getSortOrders());
        }
        $request = $this->requestBuilder->create();

        $this->processLang($request);

        $searchResponse = $this->searchEngine->search($request);

        return $this->searchResponseBuilder->build($searchResponse)
            ->setSearchCriteria($searchCriteria);
    }

    /**
     * Apply attribute filter to facet collection
     *
     * @param string $field
     * @param string|array|null $condition
     * @return $this
     */
    private function addFieldToFilter($field, $condition = null)
    {
        if (!is_array($condition) || !in_array(key($condition), ['from', 'to'], true)) {
            $this->requestBuilder->bind($field, $condition);
        } else {
            if (!empty($condition['from'])) {
                $this->requestBuilder->bind("{$field}.from", $condition['from']);
            }
            if (!empty($condition['to'])) {
                $this->requestBuilder->bind("{$field}.to", $condition['to']);
            }
        }

        return $this;
    }

    public function resolveLangCode()
    {
        $scope = $this->scopeResolver->getScope()->getId();
        $languageCode = $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $scope);
        $langISO = explode('_', $languageCode ?: 'uk_UA');
        return reset($langISO);
    }

    /**
     * Цей метод додає dimension lang до запиту, якщо його немає.
     * Зроблено через рефлексію, бо у search_request.xml неможливо додати кастомний dimension.
     * @param \Magento\Framework\Search\RequestInterface $request
     * @return void
     * @throws \ReflectionException
     */
    private function processLang(\Magento\Framework\Search\RequestInterface $request): void
    {
        $reflection = new ReflectionClass($request);
        $reflection->getProperty('dimensions')?->setAccessible(true);
        $dimensions = $reflection->getProperty('dimensions')->getValue($request);
        if (empty($dimensions['lang'])) {
            $dimensions['lang'] = new Dimension('lang', $this->resolveLangCode());
            $reflection->getProperty('dimensions')->setValue($request, $dimensions);
        }
    }
}
