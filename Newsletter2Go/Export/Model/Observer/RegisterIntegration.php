<?php

namespace Newsletter2Go\Export\Model\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Validator\Exception;
use Magento\Integration\Model as IntegrationModel;

class RegisterIntegration implements ObserverInterface
{
    const NEWSLETTER2GO_URL = 'https://www.newsletter2go.com/';

    /**
     * @see etc/integrations.xml
     * @see etc/integration/api.xml
     * @see etc/integration/config.xml
     */
    const NEWSLETTER2GO_INTEGRATION_NAME = 'Newsletter2Go Integration';

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
            $this->recreateToken($tokenString);
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
    protected function recreateToken($token)
    {
        /** @var IntegrationModel\Integration $integration */
        $integration = $this->om->get(IntegrationModel\Integration::class);
        $integration->load(self::NEWSLETTER2GO_INTEGRATION_NAME, IntegrationModel\Integration::NAME);
        $integration->setStatus(IntegrationModel\Integration::STATUS_ACTIVE);
        $integration->save();

        /** @var IntegrationModel\AuthorizationService $authorizeService */
        $authorizeService = $this->om->get(IntegrationModel\AuthorizationService::class);
        $authorizeService->grantAllPermissions($integration->getId());

        /** @var IntegrationModel\Oauth\Token $verifierToken */
        $verifierToken = $this->om->get(IntegrationModel\Oauth\Token::class);
        $verifierToken->createVerifierToken($integration->getConsumerId());
        $verifierToken->setType(IntegrationModel\Oauth\Token::TYPE_ACCESS);
        $verifierToken->setToken($token);
        $verifierToken->save();
    }
}
