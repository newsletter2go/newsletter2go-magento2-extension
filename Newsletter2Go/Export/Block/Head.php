<?php
namespace Newsletter2Go\Export\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;;


class Head extends Template
{

    const NEWSLETTER2GO_SCRIPT_URL = '//static.newsletter2go.com/utils.js';

    /** @var  ObjectManager */
    private $objectManager;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    public function __construct(
        Template\Context $context,
        array $data,
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context, $data);
        $this->objectManager = ObjectManager::getInstance();
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
    }

    public function renderScripts()
    {
        $script = '';
        $companyId = $this->_scopeConfig->getValue('newsletter_go/authentication/company_id');
        $tracking = $this->_scopeConfig->getValue('newsletter_go/tracking/tracking_select');
        $orderId = $this->getData('orderId');

        if ($orderId && $companyId && $tracking) {
            $order = '';
            $orderData = $this->getOrderData($orderId);
            $order .= 'n2g("ecommerce:addTransaction", ' . json_encode($orderData[0]) . ');';
            $itemsData = $this->getItemData($orderId);
            $items = $this->loopThroughItems($itemsData);

            $script = '<script id="n2g_script">
            !function(e,t,n,c,r,a,i){e.Newsletter2GoTrackingObject=r,e[r]=e[r]||function(){(e[r].q=e[r].q||[]).
            push(arguments)},e[r].l=1*new Date,a=t.createElement(n),i=t.getElementsByTagName(n)[0],a.async=1,a.src=c,i.
            parentNode.insertBefore(a,i)}(window,document,"script"," ' . static::NEWSLETTER2GO_SCRIPT_URL. '","n2g");
            n2g("create", "' . $companyId . '"); ' . $order . $items . 'n2g("ecommerce:send");</script>';
        }

        return $script;
    }

    /**
     * Returns string with json encoded items data
     *
     * @param array $itemsData
     * @return string
     */
    private function loopThroughItems($itemsData)
    {
        $result = '';
        foreach ($itemsData as $item) {
            $item = json_encode($item);
            $result .= 'n2g("ecommerce:addItem", ' . $item . ');';
        }

        return $result;
    }

    /**
     * Returns array of order data that based on given order id
     *
     * @param int $orderId
     * @return array
     */
    private function getOrderData($orderId)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->objectManager->get('Magento\Sales\Model\Order')->load($orderId);
        $storeName = explode(PHP_EOL, $order->getStoreName());
        $result[] = [
            'id' => $orderId,
            'affiliation' => $storeName[0],
            'revenue' => strval(round($order->getBaseGrandTotal(), 2)),
            'shipping' => strval(round($order->getShippingAmount(), 2)),
            'tax' => strval(round($order->getTaxAmount(), 2)),
        ];

        return $result;
    }

    /**
     * Returns array of products based on given order id
     *
     * @param integer $orderId
     * @return array
     */
    private function getItemData($orderId)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->objectManager->get('Magento\Sales\Model\Order')->load($orderId);
        $result = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $itemId = $this->getParentProductId($item->getProduct());
            $categoryName = $this->getCategoryName($itemId);
            $result[] = [
                'id' => $orderId,
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'category' => $categoryName,
                'price' => strval(round($item->getBasePrice(), 2)),
                'quantity' => strval(round($item->getQtyOrdered(), 2)),
            ];
        }

        return $result;
    }

    /**
     * Returns category name based on given product id
     *
     * @param integer $itemId
     * @return string
     */
    private function getCategoryName($itemId)
    {
        $result = '';
        $product = $this->productRepository->getById($itemId);
        $category = $product->getCustomAttribute('category_ids');
        $categoryIds = $category->getValue();
        if (count($categoryIds)) {
            $category = $this->categoryRepository->get($categoryIds[0]);
            $result = $category->getName();
        }

        return $result;
    }

    /**
     * Retrieves parent product id if product is configurable
     * @param $product
     * @return mixed
     */
    private function getParentProductId($product)
    {
        $id = $product->getId();
        $type = $product->getTypeId();
        if ($type == 'configurable') {
            $parents = $this->objectManager->
            create('Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable')->
            getParentIdsByChild($id);
            if (!empty($parents)) {
                return $parents[0];
            }
        }

        return $id;
    }
}