<?php
namespace Newsletter2Go\Export\Controller\Callback;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Controller\Result\JsonFactory;

class Index extends Action
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param JsonFactory $jsonFactory
     */
    public function __construct(Context $context, Config $config, JsonFactory $jsonFactory)
    {
        parent::__construct($context);
        $this->config = $config;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Catches callback from Newsletter2go and saves data in database
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $authKey = $this->getRequest()->getParam('auth_key');
        $accessToken = $this->getRequest()->getParam('access_token');
        $refreshToken = $this->getRequest()->getParam('refresh_token');
        $companyId = $this->getRequest()->getParam('int_id');

        if (!empty($authKey)) {
            $this->config->saveConfig('newsletter_go/authentication/auth_key', $authKey, 'default', 0);
        }

        if (!empty($accessToken)) {
            $this->config->saveConfig('newsletter_go/authentication/access_token', $accessToken, 'default', 0);
        }

        if (!empty($refreshToken)) {
            $this->config->saveConfig('newsletter_go/authentication/refresh_token', $refreshToken, 'default', 0);
        }

        if (!empty($companyId)) {
            $this->config->saveConfig('newsletter_go/authentication/company_id', $companyId, 'default', 0);
        }

        $result = $this->jsonFactory->create();
        $result->setData(['success' => true]);

        return $result;
    }
}