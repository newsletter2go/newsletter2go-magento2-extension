<?php

namespace Newsletter2Go\Export\Model\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validator\Exception;
use Magento\Framework\Phrase;
use Magento\Backend\Model\Auth\Session;
use Magento\Integration\Model as IntegrationModel;

class RegisterIntegration implements ObserverInterface
{
    const NEWSLETTER2GO_URL = 'https://www.newsletter2go.com/';

    /** @var ObjectManagerInterface */
    private $om;

    /** @var ScopeConfigInterface */
    private $config;

    /**
     * RegisterIntegration constructor.
     *
     * @param ScopeConfigInterface $config
     * @param ObjectManagerInterface $om
     */
    public function __construct(ScopeConfigInterface $config, ObjectManagerInterface $om)
    {
        $this->config = $config;
        $this->om = $om;
    }

    /**
     * @param Observer $observer
     *
     * @throws Exception
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $tokenString = $this->config->getValue('newsletter_go/general/token');
        if (!$tokenString) {
            throw new Exception(new Phrase('Reset current API token because token must not be empty!'));
        }

        $tokenModel = $this->getToken($tokenString);
        if (!$tokenModel->getId()) {
            $this->createNewToken($tokenString);
            $this->revokePreviousTokens($tokenString);
        } else if ($tokenModel->getRevoked()) {
            throw new Exception(new Phrase("Reset current API token because token ($tokenString) is revoked!"));
        }
    }

    /**
     * Returns admin token that is used for api authentication.
     * Token is either fetched if it exists and is not revoked or new token is created.
     *
     * @param string $currentToken
     *
     * @return IntegrationModel\Oauth\Token
     */
    protected function getToken($currentToken)
    {
        /** @var IntegrationModel\Oauth\Token $tokenModel */
        $tokenModel = $this->om->get(IntegrationModel\Oauth\Token::class);

        return $tokenModel->loadByToken($currentToken);
    }

    /**
     * Creates new token
     *
     * @param string $token
     *
     * @throws \Exception
     */
    protected function createNewToken($token)
    {
        /** @var Session $adminSession */
        $adminSession = $this->om->get(Session::class);
        $adminId = $adminSession->getUser()->getData('user_id');

        /** @var IntegrationModel\Oauth\Token $tokenModel */
        $tokenModel = $this->om->create(IntegrationModel\Oauth\Token::class);
        $tokenModel->createAdminToken($adminId);
        $tokenModel->setToken($token);
        $tokenModel->setCallbackUrl(self::NEWSLETTER2GO_URL);
        $tokenModel->save();
    }

    /**
     * Revokes all previous tokens
     *
     * @param $activeToken
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function revokePreviousTokens($activeToken)
    {
        /** @var IntegrationModel\ResourceModel\Oauth\Token $resource */
        $resource = $this->om->get(IntegrationModel\ResourceModel\Oauth\Token::class);
        $connection = $resource->getConnection();
        if (!$connection) {
            throw new Exception(new Phrase('Unable to fetch db connection!'));
        }

        $callback = self::NEWSLETTER2GO_URL;
        $where = "token != '$activeToken' AND callback_url = '$callback' AND revoked = 0";

        return $connection->update($resource->getMainTable(), ['revoked' => 1], $where);
    }
}
