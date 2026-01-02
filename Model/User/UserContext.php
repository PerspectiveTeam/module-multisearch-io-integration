<?php

namespace Perspective\MultisearchIo\Model\User;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\PageCache\Version;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Perspective\MultisearchIo\Api\UserContextInterface as MultisearchUserContextInterface;

class UserContext implements MultisearchUserContextInterface
{
    public const MS_COOKIE_NAME = '_ms';
    private ?string $cachedUserId = null;

    public function __construct(
        private readonly UserContextInterface $userContext,
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly Http $request
    )
    {
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        // Якщо є кеш - повертаємо його
        if (!empty($this->cachedUserId)) {
            return $this->cachedUserId;
        }
        // Якщо є кука - повертаємо її. Це кука із Multisearch.io
        $cookieValue = $this->cookieManager->getCookie(self::MS_COOKIE_NAME);

        if (!empty($cookieValue)) {
            if (!empty($this->userContext->getUserId())) {
                $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
                    ->setDuration(Version::COOKIE_PERIOD)
                    ->setPath('/')
                    ->setSecure($this->request->isSecure())
                    ->setHttpOnly(false);
                $this->cookieManager->setPublicCookie(self::MS_COOKIE_NAME, $this->userContext->getUserId(), $publicCookieMetadata);
                $this->cachedUserId = $this->userContext->getUserId();
                return $this->cachedUserId;
            }
            $this->cachedUserId = $cookieValue;
            return $this->cachedUserId;
        }
        // Якщо користувач не залогінений - повертаємо null
        // Якщо залогінений - шукаємо його accuid в базі
        // Ідея в тому, щоб повернути хоч якийсь ідентифікатор користувача

        $this->cachedUserId = $this->userContext->getUserId();
        if (!$this->cachedUserId) {
            $this->cachedUserId = $this->generateValue();
            $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
                ->setDuration(Version::COOKIE_PERIOD)
                ->setPath('/')
                ->setSecure($this->request->isSecure())
                ->setHttpOnly(false);
            $this->cookieManager->setPublicCookie(self::MS_COOKIE_NAME, $this->cachedUserId, $publicCookieMetadata);
        }
        return $this->cachedUserId;
    }

    /**
     * Generate unique version identifier
     *
     * @return string
     */
    protected function generateValue()
    {
        //phpcs:ignore
        return md5(rand() . time());
    }

}
