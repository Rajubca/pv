<?php

namespace Shatchi\ProductVariant\Plugin;

use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Store\Model\StoreManagerInterface;
use Shatchi\ProductVariant\Helper\Data as HelperData;

class QuoteItemSetQtyPlugin
{
    /**
     * @var ProductResource
     */
    protected $productResource;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var HelperData
     */
    protected $helper;

    public function __construct(
        ProductResource $productResource,
        StoreManagerInterface $storeManager,
        HelperData $helper
    ) {
        $this->productResource = $productResource;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
    }

    /**
     * Intercept setting quantity to apply custom carton pricing
     *
     * @param Item $subject
     * @param Item $result
     * @return Item
     */
    public function afterSetQty(Item $subject, $result)
    {
        $storeId = $this->storeManager->getStore()->getId();

        // Check backend configuration
        if (!$this->helper->isCartonPricingEnabled($storeId)) {
            return $result;
        }

        // When adding configurable products, the main quote item is the configurable one,
        // and its first child represents the selected simple product variant.
        $product = $subject->getProduct();
        $simpleProductId = null;

        if ($product && $product->getTypeId() === 'configurable') {
            $children = $subject->getChildren();
            if (!empty($children)) {
                $simpleProductId = $children[0]->getProduct()->getId();
            }
        } elseif ($product && $product->getTypeId() === 'simple') {
            // Fallback for standalone simple products
            $simpleProductId = $product->getId();
        }

        if ($simpleProductId) {
            $qty = (float)$subject->getQty();

            // Reliably fetch the attributes directly from DB as quote items sometimes miss custom attributes
            $cartonQty = (float)$this->productResource->getAttributeRawValue($simpleProductId, 'shatchi_carton_qty', $storeId);
            $cartonPrice = (float)$this->productResource->getAttributeRawValue($simpleProductId, 'shatchi_carton_price', $storeId);

            // If quantity meets or exceeds carton quantity, apply the custom carton price
            if ($cartonQty > 0 && $qty >= $cartonQty && $cartonPrice > 0) {
                // Apply the custom price to the parent quote item
                $subject->setCustomPrice($cartonPrice);
                $subject->setOriginalCustomPrice($cartonPrice);
            } else {
                // If the quantity drops below carton qty, we must remove the custom price
                // so it falls back to the standard Magento price calculation
                if ($subject->hasCustomPrice()) {
                    $subject->unsCustomPrice();
                    $subject->unsOriginalCustomPrice();
                }
            }

            // Re-calculate the product data
            $subject->getProduct()->setIsSuperMode(true);
        }

        return $result;
    }
}
