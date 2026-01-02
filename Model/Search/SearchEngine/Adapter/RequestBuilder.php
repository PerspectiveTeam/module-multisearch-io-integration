<?php

namespace Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Search\Request\Filter\Range;
use Magento\Framework\Search\Request\Filter\Term;
use Perspective\MultisearchIo\Api\RequestBuilderInterface;
use Perspective\MultisearchIo\Api\SortMappingInterface;
use Perspective\MultisearchIo\Api\UserContextInterface;
use Perspective\MultisearchIo\Model\Config;
use Magento\Framework\Search\RequestInterface;

class RequestBuilder extends DataObject implements RequestBuilderInterface
{
    public function __construct(
        private readonly RequestFactory $bareRequestFactory,
        private readonly Config $config,
        private readonly UserContextInterface $userContext,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly SortMappingInterface $sortMapping,
        array $data = []
    )
    {
        parent::__construct($data);
    }


    /**
     * @param RequestInterface $request
     * @param self $requestBuilder
     * @return void
     */
    public function buildQuery(RequestInterface $request, RequestBuilderInterface $requestBuilder): void
    {
        $query = $request->getQuery();
        //Should завжди містить search_main і воно єдине.
        $requestBuilder->setData('query', $query->getShould()['search_main']->getValue());
        $requestBuilder->setData('categories', '0');
        $filters = [];
        $categories = [];
        //Must містить всі фільтри, які застосовані до пошуку. Поки що підтримується лише range
        foreach ($query->getMust() as $key => $filter) {
            if ($filter->getReference() instanceof Range) {
                $filters[$key] = ['from' => $filter->getReference()->getFrom(), 'to' => $filter->getReference()->getTo()];
                continue;
            }
            if ($filter->getReference() instanceof Term) {
                if ($filter->getReference()->getName() === 'category_filter') {
                    //Категорії обробляються окремо
                    $categories = $filter->getReference()->getValue();
                    continue;
                }
                $value = $filter->getReference()->getValue();
                $filters[$filter->getReference()->getField()] = $value;
            }
        }
        if (count($filters) > 0) {
            // Додати general фільтри до запиту
            $requestBuilder->setData('filters', json_encode($filters));
        } else {
            $requestBuilder->setData('filters', 'true');
        }
        // По дефолту ліміт API - 4. Це замало, тому ставимо 24

        if (empty($requestBuilder->getData('limit'))) {
            $requestBuilder->setData('limit', '24');
        }
        if (count($categories) > 0) {
            // Якщо є категорії, додати їх до запиту
            $requestBuilder->setData('t', $categories);
        }
        $requestBuilder->setData('offset', $request->getFrom() ?: 0);
        // Додати повний вівид полів
        $requestBuilder->setData('fields', 'true');
        // Додати мову
        $lang =  $request->getDimensions()['lang']?->getValue() ?? 'uk';
        $requestBuilder->setData('lang', $lang);
        // Додати сортування
        $sortOrders = $request->getSort();
        // Сортування за релевантністю - це сортування за замовчуванням і не має direction
        if (!empty($sortOrders)) {
            $sortOrder = new DataObject(reset($sortOrders));
            $direction = strtolower($sortOrder->getDirection()) === 'asc' ? 'asc' : 'desc';
            $field = $sortOrder->getField();
            $requestBuilder->setData('sort', sprintf("%s.%s", $this->sortMapping->map($field), $direction));
        } else {
            // Це поле передається у фіді
            $requestBuilder->setData('sort', 'relevance.asc');
        }

    }

    public function createSearch($method = 'GET'): \Perspective\MultisearchIo\Api\GuzzleRequestInterface
    {
        $query = array_merge([
            \Perspective\MultisearchIo\Api\RequestInterface::PARAM_ID => $this->config->getApiId(),
            \Perspective\MultisearchIo\Api\RequestInterface::PARAM_UID => $this->userContext->getUserId(),
        ], $this->getData());
        $query = array_filter($query, fn($value) => $value !== null && $value !== '');

        $uri = http_build_query($query);

        $request = $this->bareRequestFactory->create([
            'method' => $method,
            'uri' => '/?' . $uri,
        ]);
        $this->dataPersistor->clear(\Perspective\MultisearchIo\Api\RequestInterface::CURRENT_MULTISEARCH_REQUEST_URI);
        $this->dataPersistor->set(\Perspective\MultisearchIo\Api\RequestInterface::CURRENT_MULTISEARCH_REQUEST_URI, $uri);

        $this->unsetData();
        return $request;
    }
}
