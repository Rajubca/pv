<?php

namespace Shatchi\ProductVariant\Plugin;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Store\Model\StoreManagerInterface;
use Shatchi\ProductVariant\Helper\Data as VariantHelper;

class ConfigurableJsonConfig
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
     * @var VariantHelper
     */
    protected $variantHelper;

    /**
     * @param ProductResource $productResource
     * @param StoreManagerInterface $storeManager
     * @param VariantHelper $variantHelper
     */
    public function __construct(
        ProductResource $productResource,
        StoreManagerInterface $storeManager,
        VariantHelper $variantHelper
    ) {
        $this->productResource = $productResource;
        $this->storeManager = $storeManager;
        $this->variantHelper = $variantHelper;
    }

    /**
     * Inject product descriptions and custom Shatchi attributes into the configurable json config
     *
     * @param Configurable $subject
     * @param string $result
     * @return string
     */
    public function afterGetJsonConfig(
        Configurable $subject,
        $result
    ) {
        $config = json_decode($result, true);

        $product = $subject->getProduct();
        $childProducts = $product->getTypeInstance()->getUsedProducts($product);
        $storeId = $this->storeManager->getStore()->getId();
        $attributeSetId = $product->getAttributeSetId();

        $config['attribute_set_id'] = $attributeSetId;

        // Fetch configured grid columns for this attribute set
        $gridColumns = $this->variantHelper->getGridColumns($attributeSetId, $storeId);
        $config['shatchi_grid_columns'] = $gridColumns;

        // Determine which attributes to pull from the DB based on config
        // Base attributes that are always required
        $attributesToFetch = [
            'description',
            'short_description',
            'shatchi_moq',
            'shatchi_carton_price',
            'shatchi_carton_qty'
        ];

        // Add dynamic attributes based on config
        if (!empty($gridColumns)) {
            foreach ($gridColumns as $col) {
                if (strpos($col['code'], 'attr_') === 0) {
                    $attrCode = substr($col['code'], 5); // Strip 'attr_' prefix
                    if (!in_array($attrCode, $attributesToFetch)) {
                        $attributesToFetch[] = $attrCode;
                    }
                }
            }
        } else {
            // Fallback backward compatibility just in case there's no config
            $attributesToFetch[] = 'pack_per_piece';
            $attributesToFetch[] = 'leds_no';
        }

        foreach ($childProducts as $child) {
            $childId = $child->getId();

            // Get raw attribute values directly from the database to avoid N+1 loading performance issues
            $rawAttributes = $this->productResource->getAttributeRawValue($childId, $attributesToFetch, $storeId);

            if (is_array($rawAttributes)) {
                $description = isset($rawAttributes['description']) ? $rawAttributes['description'] : '';
                $shortDescription = isset($rawAttributes['short_description']) ? $rawAttributes['short_description'] : '';
                $shatchiMoq = isset($rawAttributes['shatchi_moq']) ? $rawAttributes['shatchi_moq'] : null;
                $shatchiCartonPrice = isset($rawAttributes['shatchi_carton_price']) ? $rawAttributes['shatchi_carton_price'] : null;
                $shatchiCartonQty = isset($rawAttributes['shatchi_carton_qty']) ? $rawAttributes['shatchi_carton_qty'] : null;
            } else {
                // Fallback if the array format is not returned
                $description = '';
                $shortDescription = '';
                $shatchiMoq = null;
                $shatchiCartonPrice = null;
                $shatchiCartonQty = null;
                $rawAttributes = [];
            }

            $config['descriptions'][$childId] = $description ?: '';
            $config['shortDescriptions'][$childId] = $shortDescription ?: '';

            $config['shatchi_moq'][$childId] = $shatchiMoq > 0 ? (float)$shatchiMoq : 1;
            $config['shatchi_carton_price'][$childId] = $shatchiCartonPrice !== null ? (float)$shatchiCartonPrice : 0;
            $config['shatchi_carton_qty'][$childId] = $shatchiCartonQty > 0 ? (float)$shatchiCartonQty : 1;

            // Populate dynamically requested attributes
            foreach ($attributesToFetch as $attrCode) {
                if (in_array($attrCode, ['description', 'short_description', 'shatchi_moq', 'shatchi_carton_price', 'shatchi_carton_qty'])) {
                    continue; // Skip base attributes we already processed
                }

                $attrValue = isset($rawAttributes[$attrCode]) ? $rawAttributes[$attrCode] : null;
                $config['dynamic_attrs'][$attrCode][$childId] = $attrValue !== null ? $attrValue : '-';
            }
        }

        return json_encode($config);
    }
}
