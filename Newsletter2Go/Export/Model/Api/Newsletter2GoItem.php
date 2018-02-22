<?php

namespace Newsletter2Go\Export\Model\Api;

use Magento\Catalog\Model as CatalogModel;
use Magento\Store\Model as StoreModel;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Tax\Model\Calculation as TaxCalculation;
use Magento\Tax\Helper\Data as TaxData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Webapi\Request;
use Magento\Framework\Webapi\Rest\Response as RestResponse;
use Newsletter2Go\Export\Api\Newsletter2GoItemInterface;
use Newsletter2Go\Export\Api\Data\ResponseFactoryInterface;
use Magento\Framework\App\ObjectManager;

class Newsletter2GoItem extends AbstractNewsletter2Go implements Newsletter2GoItemInterface
{
    /**
     * @var StoreModel\StoreManagerInterface
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
     * @var CatalogModel\ProductFactory
     */
    private $productFactory;

    /**
     * Newsletter2GoItem constructor.
     *
     * @param StoreModel\StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $config
     * @param Request $request
     * @param RestResponse $response
     * @param ResponseFactoryInterface $responseFactory
     * @param CatalogModel\ProductFactory $productFactory
     */
    public function __construct(
        StoreModel\StoreManagerInterface $storeManager,
        ScopeConfigInterface $config,
        Request $request,
        RestResponse $response,
        ResponseFactoryInterface $responseFactory,
        CatalogModel\ProductFactory $productFactory
    )
    {
        parent::__construct($responseFactory);

        $this->storeManager = $storeManager;
        $this->om = ObjectManager::getInstance();
        $this->request = $request;
        $this->productFactory = $productFactory;
    }

    /**
     * Retrieves product by id or sku
     * @api
     * @param string $itemId
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getItem($itemId)
    {
        try {
            $storeId = $this->request->getParam('storeId');
            $fields = $this->request->getParam('fieldIds', array_keys($this->buildItemFields()));
            /** @var StoreModel\Store $store */
            $store = $this->om->get(StoreModel\Store::class);

            $product = $this->productFactory->create();
            $productId = $itemId;
            if (filter_var($itemId, FILTER_VALIDATE_INT) === false) {
                $productId = $this->om->get(CatalogModel\ResourceModel\Product::class)->getIdBySku($itemId);
            }

            if ($storeId !== null) {
                $product->setData('store_id', $storeId);
            } else {
                $storeId = $this->storeManager->getDefaultStoreView()->getId();
            }

            $store->load($storeId);
            $product->load($productId);
            if (!$product->getId()) {
                return $this->generateErrorResponse('Product with id or sku (' . $itemId . ') not found!');
            }

            $productArray = $product->toArray($fields);
            $this->reformatFields($product, $productArray, $store);

            return $this->generateSuccessResponse([$productArray]);
        } catch (\Exception $e) {
            return $this->generateErrorResponse($e->getMessage());
        }
    }

    /**
     * Retrieves product fields
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getItemFields()
    {
        return $this->generateSuccessResponse($this->buildItemFields());
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function buildItemFields()
    {
        return [
            'images' => $this->createArray('images', 'Images', 'Product images', 'Array'),
            'vat' => $this->createArray('vat', 'VAT', 'Value Added Tax', 'Float'),
            'newPrice' => $this->createArray('newPrice', 'New price', '', 'Float'),
            'newPriceNet' => $this->createArray('newPriceNet', 'New price net', '', 'Float'),
            'oldPriceNet' => $this->createArray('oldPriceNet', 'Old price net', '', 'Float'),
            'oldPrice' => $this->createArray('oldPrice', 'Old price net', '', 'Float'),
            'entity_id' => $this->createArray('entity_id', 'Product Id.', 'Product unique identificator', 'Integer'),
            'type_id' => $this->createArray('type_id', 'Type Id.', 'Type unique identificator'),
            'sku' => $this->createArray('sku', 'SKU', 'Stock Keeping Unit'),
            'name' => $this->createArray('name', 'Name', 'Product name'),
            'meta_title' => $this->createArray('meta_title', 'Meta title'),
            'meta_description' => $this->createArray('meta_description', 'Meta Description'),
            'url_key' => $this->createArray('url_key', 'Link'),
            'shop_url' => $this->createArray('shop_url', 'Shop url'),
            'custom_design' => $this->createArray('custom_design', 'Custom Design'),
            'page_layout' => $this->createArray('page_layout', 'Page Layout'),
            'country_of_manufacture' => $this->createArray('country_of_manufacture', 'Country of Manufacture'),
            'status' => $this->createArray('status', '', '', 'Integer'),
            'visibility' => $this->createArray('visibility', '', '', 'Integer'),
            'tax_class_id' => $this->createArray('tax_class_id', '', '', 'Integer'),
            'description' => $this->createArray('description', 'Description'),
            'short_description' => $this->createArray('short_description', 'Short Description'),
            'meta_keyword' => $this->createArray('meta_keyword', 'Meta Keywords'),
            'msrp' => $this->createArray('msrp', 'MSRP', 'Manufacturer\'s suggested retail price', 'Float'),
            'news_from_date' => $this->createArray('news_from_date', '', '', 'Date'),
            'news_to_date' => $this->createArray('news_to_date', '', '', 'Date'),
            'custom_design_from' => $this->createArray('custom_design_from', '', '', 'Date'),
            'custom_design_to' => $this->createArray('custom_design_to', '', '', 'Date'),
            'is_in_stock' => $this->createArray('is_in_stock', 'Is in stock', '', 'Boolean'),
            'qty' => $this->createArray('qty', 'Quantity', '', 'Integer'),
            'price' => $this->createArray('price', 'Price', '', 'Float'),
            'special_price' => $this->createArray('special_price', 'Special Price', '', 'Float'),
            'special_from_date' => $this->createArray('special_from_date', 'Special Price From Date', '', 'Date'),
            'special_to_date' => $this->createArray('special_to_date', 'Special Price To Date', '', 'Date'),
            'weight' => $this->createArray('weight', 'Weight', '', 'Float'),
            'is_salable' => $this->createArray('is_salable', 'Is Salable', '', 'Boolean'),
            'sale' => $this->createArray('sale', 'On Sale', 'Is product on sale', 'Boolean'),
        ];
    }

    /**
     * @param CatalogModel\Product $product
     * @param array $productArray
     * @param StoreModel\Store $store
     */
    protected function reformatFields(CatalogModel\Product $product, array &$productArray, StoreModel\Store $store)
    {
        /** @var TaxData $taxHelper */
        $taxHelper = $this->om->get(TaxData::class);
        $taxIncluded = $taxHelper->priceIncludesTax($store);

        /** @var TaxCalculation $taxCalculation */
        $taxCalculation = $this->om->get(TaxCalculation::class);
        $rateRequest = $taxCalculation->getRateRequest(null, null, null, $store);
        $taxClassId = $product->getTaxClassId();
        $percent = $taxCalculation->getRate($rateRequest->setData('product_class_id', $taxClassId));
        $vat = $percent;

        foreach ($productArray as $key => &$value) {
            switch ($key) {
                case 'url_key';
                    $url = $this->getParentProductUrl($product);
                    $parts = parse_url(str_replace($store->getBaseUrl(), '', $url));
                    $value = $parts['path'] . '?___store=' . $store->getCode();
                    break;
                case 'shop_url':
                    $value = $store->getBaseUrl();
                    break;
                case 'vat':
                    $value = number_format($vat * 0.01, 2);
                    break;
                case 'newPrice':
                    $value = $this->calculatePrice($product->getFinalPrice(), $vat, $taxIncluded);
                    break;
                case 'newPriceNet':
                    $value = $this->calculateNetPrice($product->getFinalPrice(), $vat, $taxIncluded);
                    break;
                case 'oldPrice':
                    $value = $this->calculatePrice($product->getPrice(), $vat, $taxIncluded);
                    break;
                case 'oldPriceNet':
                    $value = $this->calculateNetPrice($product->getPrice(), $vat, $taxIncluded);
                    break;
                case 'qty':
                    $value = $product->getQty();
                    break;
                case 'is_in_stock':
                    $value = $product->isInStock();
                    break;
                case 'images':
                    $value = [];
                    foreach ($product->getMediaGalleryImages() as $image) {
                        $value[] = $image->getData('url');
                    }
                    break;
            }
        }
    }

    /**
     * @param $price
     * @param $vat
     * @param $taxIncluded
     * @return string
     */
    protected function calculateNetPrice($price, $vat, $taxIncluded)
    {
        return number_format($taxIncluded ? $price / (1 + $vat * 0.01) : $price, 2);
    }

    /**
     * @param $price
     * @param $vat
     * @param bool $taxIncluded
     * @return string
     */
    protected function calculatePrice($price, $vat, $taxIncluded = true)
    {
        return number_format($taxIncluded ? $price : $price * (1 + $vat * 0.01), 2);
    }

    /**
     * Retrieves parent product id if product is configurable
     * @param CatalogModel\Product $product
     * @return mixed
     */
    protected function getParentProductUrl(CatalogModel\Product $product)
    {
        $parents = $this->om->create(Configurable::class)
            ->getParentIdsByChild($product->getId());

        if (!empty($parents)) {
            /** @var CatalogModel\Product $parent */
            $parent = $this->om->get(CatalogModel\Product::class)->load($parents[0]);

            return $parent->getUrlModel()->getProductUrl($parent);
        }

        return $product->getUrlModel()->getProductUrl($product);
    }
}
