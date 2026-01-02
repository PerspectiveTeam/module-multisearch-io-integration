<?php

namespace Perspective\MultisearchIo\Controller\Search;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestSafetyInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Search\Model\Query;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Perspective\MultisearchIo\Api\AnalyticsInterface;


class Index extends Action implements HttpGetActionInterface, RequestSafetyInterface
{

    public function __construct(
        Context $context,
        private readonly QueryFactory $queryFactory,
        private readonly Resolver $layerResolver,
        private readonly StoreManagerInterface $storeManager,
        private readonly AnalyticsInterface $analytics
    )
    {
        parent::__construct($context);
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $this->layerResolver->create('multisearch');

        /* @var $query Query */
        $query = $this->queryFactory->get();

        $storeId = $this->storeManager->getStore()->getId();
        $query->setStoreId($storeId);

        $queryText = $query->getQueryText();

        if ($queryText != '') {
            $getAdditionalRequestParameters = $this->getRequest()->getParams();
            unset($getAdditionalRequestParameters[QueryFactory::QUERY_VAR_NAME]);
            $query->saveIncrementalPopularity();
            $handles = null;
            if ($query->getNumResults() == 0) {
                $this->_view->getPage()->initLayout();
                $handles = $this->_view->getLayout()->getUpdate()->getHandles();
                $handles[] = \Magento\CatalogSearch\Controller\Result\Index::DEFAULT_NO_RESULT_HANDLE;
            }

            $this->_view->loadLayout($handles);
            $this->getResponse()->setNoCacheHeaders();
            $this->_view->renderLayout();
        } else {
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
        }
        $this->analytics->sendSearchEvent($queryText);
    }

    /**
     * @inheritDoc
     */
    public function isSafeMethod()
    {
        return true;
    }
}
