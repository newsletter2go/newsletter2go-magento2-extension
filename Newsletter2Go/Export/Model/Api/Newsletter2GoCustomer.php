<?php

namespace Newsletter2Go\Export\Model\Api;

use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Request;
use Magento\Newsletter\Model\Subscriber;
use Newsletter2Go\Export\Api\Data\ResponseFactoryInterface;
use Newsletter2Go\Export\Api\Newsletter2GoCustomerInterface;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Customer\Model as CustomerModel;
use Magento\Newsletter\Model as NewsletterModel;
use Magento\Customer\Model\ResourceModel as CustomerResourceModel;
use Magento\Newsletter\Model\ResourceModel as NewsletterResourceModel;

class Newsletter2GoCustomer extends AbstractNewsletter2Go implements Newsletter2GoCustomerInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $config,
        Request $request,
        Response $response,
        ResponseFactoryInterface $responseFactory)
    {
        parent::__construct($responseFactory);

        $this->storeManager = $storeManager;
        $this->om = ObjectManager::getInstance();
        $this->request = $request;
        $this->response = $response;
    }

    public function getCustomers()
    {
        static $genderMap = [1 => 'm', 2 => 'f'];
        $group = $this->request->getParam('group');
        $hours = $this->request->getParam('hours');
        $subscribed = $this->request->getParam('subscribed');
        $fields = $this->request->getParam('fieldIds');
        $limit = $this->request->getParam('limit');
        $offset = $this->request->getParam('offset');
        $emails = $this->request->getParam('emails');
        $storeId = $this->request->getParam('storeId');

        try {
            $billingAdded = false;
            if (empty($fields)) {
                $fields = array_keys($this->buildCustomerFields());
            } else if (!in_array('default_billing', $fields, true)) {
                $fields[] = 'default_billing';
                $billingAdded = true;
            }

            if ($group === 'subscribers-only') {
                if ($billingAdded) {
                    $index = array_search('default_billing', $fields, true);
                    unset($fields[$index]);
                }

                return $this->getOnlySubscribers($fields, $subscribed, $limit, $offset, $emails, $storeId);
            }

            $subscribedCond = 1;
            if ($subscribed) {
                $subscribedCond = 'ns.subscriber_status = ' . Subscriber::STATUS_SUBSCRIBED;
            }

            /** @var CustomerResourceModel\Customer\Collection $collection */
            $collection = $this->om->get(CustomerResourceModel\Customer\Collection::class);
            $collection->addAttributeToSelect('*');

            //Join with subscribers
            if ($subscribed || in_array('subscriber_status', $fields, true)) {
                $collection->joinTable(
                    ['ns' => 'newsletter_subscriber'],
                    'customer_id=entity_id',
                    ['subscriber_status'],
                    'ns.subscriber_status = ' . Subscriber::STATUS_SUBSCRIBED,
                    'left'
                );
            }

            if ($group !== null) {
                $collection->addAttributeToFilter('group_id', $group);
            }

            if (!empty($storeId)) {
                $collection->addAttributeToFilter('store_id', $storeId);
            }

            if (!empty($emails)) {
                $collection->addAttributeToFilter('email', ['in' => $emails]);
            }

            if ($hours && is_numeric($hours)) {
                $ts = date('Y-m-d H:i:s', time() - 3600 * $hours);
                $collection->addAttributeToFilter('updated_at', ['gteq' => $ts]);
            }

            $collection->groupByAttribute('entity_id');
            $collection->getSelect()->where($subscribedCond);
            if ($limit) {
                $offset = $offset ?: 0;
                $collection->getSelect()->limit($limit, $offset);
            }

            $customers = $collection->load()->toArray($fields);
            /** @var CustomerModel\Address $addressModel */
            $addressModel = $this->om->get(CustomerModel\Address::class);

            foreach ($customers as &$customer) {
                $addressModel->load($customer['default_billing']);
                if (array_key_exists('telephone', $customer)) {
                    $customer['telephone'] = $addressModel->getTelephone();
                }

                if (array_key_exists('subscriber_status', $customer) && $customer['subscriber_status'] === null) {
                    $customer['subscriber_status'] = 0;
                }

                if (!$billingAdded && isset($customer['default_billing'])) {
                    $customer['default_billing'] = json_encode($addressModel->toArray());
                } else {
                    unset($customer['default_billing']);
                }

                if (isset($customer['default_shipping'])) {
                    $customer['default_shipping'] = json_encode($addressModel->load($customer['default_shipping'])->toArray());
                }

                if (isset($customer['gender'])) {
                    $customer['gender'] = isset($genderMap[$customer['gender']]) ? $genderMap[$customer['gender']] : '';
                }
            }

            return $this->generateSuccessResponse($customers);
        } catch (\Exception $e) {
            return $this->generateErrorResponse($e->getMessage());
        }
    }

    /**
     * Retrieves only subscribers that are not registered as customers
     *
     * @param array $fields
     * @param boolean $subscribed
     * @param int $limit
     * @param int $offset
     * @param array $emails
     * @param int $storeId
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getOnlySubscribers($fields, $subscribed, $limit, $offset = 0, $emails = [], $storeId = null)
    {
        /** @var NewsletterResourceModel\Subscriber\Collection $collection */
        $collection = $this->om->get(NewsletterResourceModel\Subscriber\Collection::class);
        $collection->addFieldToFilter('customer_id', 0);
        $collection->addFieldToSelect('subscriber_email', 'email');
        $collection->addFieldToSelect('store_id');
        $collection->addFieldToSelect('subscriber_status');
        if ($storeId !== null) {
            $collection->addStoreFilter($storeId);
        }

        if (!empty($emails)) {
            $collection->addFieldToFilter('subscriber_email', ['in' => $emails]);
        }

        if ($subscribed) {
            $collection->useOnlySubscribed();
        }

        if ($limit) {
            $offset = $offset ?: 0;
            $collection->getSelect()->limit($limit, $offset);
        }

        $subscribers = $collection->load()->toArray($fields);

        return $this->generateSuccessResponse($subscribers['items']);
    }

    public function getCustomerGroups()
    {
        /** @var CustomerResourceModel\Group\Collection|CustomerModel\Group[] $groups */
        $groups = $this->om->get(CustomerResourceModel\Group\Collection::class);
        $result = [
            [
                'id' => 'subscribers-only',
                'name' => 'Subscribers only',
                'description' => 'Customers that subscribed to newsletter and didn\'t register as customers on system.',
            ],
        ];

        foreach ($groups as $group) {
            $result[] = [
                'id' => $group->getId(),
                'name' => $group->getCode(),
                'description' => '',
            ];
        }

        return $this->generateSuccessResponse($result);
    }

    /**
     * @inheritdoc
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerCount()
    {
        $groupId = $this->request->getParam('groupId');
        $subscribed = $this->request->getParam('subscribed');
        $storeId = $this->request->getParam('storeId');

        if ($groupId === null || $groupId === '') {
            $this->response->setStatusCode(400);

            return $this->generateErrorResponse('Group Id. parameter must be set');
        }

        if ($groupId === 'subscribers-only') {
            /** @var NewsletterResourceModel\Subscriber\Collection $collection */
            $collection = $this->om->get(NewsletterResourceModel\Subscriber\Collection::class);
            $collection->addFieldToFilter('customer_id', 0);
            if ($subscribed) {
                $collection->useOnlySubscribed();
            }
        } else {
            /** @var CustomerResourceModel\Customer\Collection $collection */
            $collection = $this->om->get(CustomerResourceModel\Customer\Collection::class);
            $collection->addAttributeToFilter('group_id', $groupId);
            if ($subscribed) {
                $collection->joinTable(['ns' => 'newsletter_subscriber'], 'customer_id=entity_id', ['subscriber_status'], 'ns.subscriber_status=1');
            }
        }

        if ($storeId) {
            $collection->addAttributeToFilter('store_id', $storeId);
        }

        return $this->generateSuccessResponse($collection->count());
    }

    /**
     * @inheritdoc
     *
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function changeSubscriberStatus($email, $status = '0', $storeId = '1')
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->response->setStatusCode(400);

            return $this->generateErrorResponse('Email parameter is missing or invalid (' . $email . ')!');
        }

        $status = filter_var($status, FILTER_VALIDATE_INT);
        if ($status === false) {
            $this->response->setStatusCode(400);

            return $this->generateErrorResponse('Status parameter must be a number!');
        }

        $storeId = filter_var($status, FILTER_VALIDATE_INT);
        if ($storeId === false) {
            $this->response->setStatusCode(400);

            return $this->generateErrorResponse('Store Id parameter must be a number!');
        }

        /** @var CustomerModel\Customer $customer */
        $customer = $this->om->get(CustomerModel\Customer::class);
        $customer->setWebsiteId($this->storeManager->getWebsite()->getId())->loadByEmail($email);

        /** @var NewsletterModel\Subscriber $subscriber */
        $subscriber = $this->om->get(NewsletterModel\Subscriber::class);
        $subscriber->loadByEmail($email);
        $subscriber->setCustomerId($customer->getId() ?: 0);
        if ($status && !$subscriber->getId()) {
            if (!$customer->getId()) {
                $this->response->setStatusCode(404);

                return $this->generateErrorResponse('No customer or subscriber found with email: ' . $email);
            }

            /** @var NewsletterModel\Subscriber $subscriber */
            $subscriber = $this->om->create(NewsletterModel\Subscriber::class);
            $subscriber->setCustomerId($customer->getId() ?: 0);
            $subscriber->setEmail($email);
            $subscriber->setSubscriberConfirmCode($subscriber->randomSequence());
            $subscriber->setStoreId($storeId);
        } else if (!$status && !$subscriber->getId()) {
            $this->response->setStatusCode(404);

            return $this->generateErrorResponse('No customer or subscriber found with email: ' . $email);
        }

        $subscriber->setSubscriberStatus($status ? Subscriber::STATUS_SUBSCRIBED : Subscriber::STATUS_UNSUBSCRIBED);
        $subscriber->save();

        return $this->generateSuccessResponse($subscriber->toArray());
    }

    public function getCustomerFields()
    {
        return $this->generateSuccessResponse($this->buildCustomerFields());
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function buildCustomerFields()
    {
        return [
            'entity_id' => $this->createArray('entity_id', 'Customer Id.', 'Unique customer number', 'Integer'),
            'website_id' => $this->createArray('website_id', 'Website Id.', 'Unique website number', 'Integer'),
            'email' => $this->createArray('email', 'E-mail', 'E-mail address', 'String'),
            'group_id' => $this->createArray('group_id', 'Group Id.', 'Unique group number', 'Integer'),
            'created_at' => $this->createArray('created_at', 'Created at', 'Timestamp of creation', 'Date'),
            'updated_at' => $this->createArray('updated_at', 'Updated at', 'Timestamp of last update', 'Date'),
            'dob' => $this->createArray('dob', 'Date of birth', 'Date of birth', 'Date'),
            'disable_auto_group_change' => $this->createArray('disable_auto_group_change', 'Disable auto group change', 'Disable auto group change', 'Boolean'),
            'created_in' => $this->createArray('created_in', 'Created in', 'Place it was created admin side or by registration', 'String'),
            'suffix' => $this->createArray('suffix', 'Suffix', 'suffix', 'String'),
            'prefix' => $this->createArray('prefix', 'Prefix', 'Prefix', 'String'),
            'firstname' => $this->createArray('firstname', 'Firstname', 'Firstname', 'String'),
            'middlename' => $this->createArray('middlename', 'Middlename', 'middlename', 'String'),
            'lastname' => $this->createArray('lastname', 'Lastname', 'lastname', 'String'),
            'taxvat' => $this->createArray('taxvat', 'Tax VAT', 'Tax VAT', 'String'),
            'store_id' => $this->createArray('store_id', 'Store Id.', 'Unique store number', 'Integer'),
            'gender' => $this->createArray('gender', 'Gender', 'Gender', 'Integer'),
            'is_active' => $this->createArray('is_active', 'Is active', 'Is Active', 'Boolean'),
            'subscriber_status' => $this->createArray('subscriber_status', 'Subscriber status', 'Subscriber status', 'Integer'),
            'default_billing' => $this->createArray('default_billing', 'Default billing address', 'Default billing address', 'Object'),
            'default_shipping' => $this->createArray('default_shipping', 'Default shipping address', 'Default shipping address', 'Object'),
            'telephone' => $this->createArray('telephone', 'Telephone number', 'Telephone number', 'String'),
        ];
    }
}
