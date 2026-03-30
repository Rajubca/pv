<?php

namespace Shatchi\ProductVariant\Plugin\ConfigurableProduct\Block\Product\View\Type;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Json\DecoderInterface;
use Shatchi\ProductVariant\Helper\Data as VariantHelper;
use Magento\Store\Model\StoreManagerInterface;

class ConfigurablePlugin
{
    protected $jsonEncoder;
    protected $jsonDecoder;
    protected $variantHelper;
    protected $storeManager;

    public function __construct(
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
        VariantHelper $variantHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->jsonEncoder = $jsonEncoder;
        $this->jsonDecoder = $jsonDecoder;
        $this->variantHelper = $variantHelper;
        $this->storeManager = $storeManager;
    }

    public function afterGetJsonConfig(Configurable $subject, $result)
    {
        $config = $this->jsonDecoder->decode($result);

                // Grid Configuration Logic
        $product = $subject->getProduct();
        $storeId = $this->storeManager->getStore()->getId();

        // Build an array of Attribute Set IDs involved (Parent first, then Children)
        $attributeSetIds = [(int)$product->getAttributeSetId()];

        // Get all simple products that belong to this configurable product
        $products = $subject->getAllowProducts();

        foreach ($products as $child) {
            $childSetId = (int)$child->getAttributeSetId();
            if (!in_array($childSetId, $attributeSetIds)) {
                $attributeSetIds[] = $childSetId;
            }
        }

        $config['attribute_set_id'] = $attributeSetIds; // Useful for JS debugging

        // Fetch configured grid columns matching ANY of the product's attribute sets
        $gridColumns = $this->variantHelper->getGridColumns($attributeSetIds, $storeId);
        $config['shatchi_grid_columns'] = $gridColumns;

        // Custom attribute arrays
        $descTab = [];
        $prodDetailsTab = [];
        $careInstTab = [];
        $techSpecTab1 = [];
        $techSpecTab2 = [];
        $descriptions = []; // fallback
        $moqList = [];
        $shatchiMoqList = [];
        $cartonPriceList = [];
        $cartonQtyList = [];
        $ledsNoList = [];
        $dynamicAttrs = [];

        foreach ($products as $child) {
            $productId = $child->getId();

            // Helper to get attribute safely (handles if not loaded in collection)
            $getAttr = function ($code) use ($child) {
                $val = $child->getData($code);
                if ($val === null) {
                    $resource = $child->getResource();
                    if ($resource) {
                        $rawVal = $resource->getAttributeRawValue($child->getId(), $code, $child->getStoreId());
                        if ($rawVal !== false && !is_array($rawVal)) {
                            return $rawVal;
                        }
                    }
                }
                return $val;
            };

            // Standard Description fallback
            $ledsVal = $child->getAttributeText('leds_no');

            if (!$ledsVal) {
                $ledsVal = $getAttr('leds_no');
            }

            if ($ledsVal !== null) {
                $ledsNoList[$productId] = $ledsVal;
            }
            $desc = $getAttr('description');
            $shortDesc = $getAttr('short_description');
            if ($desc) {
                $descriptions[$productId] = $desc;
            } else if ($shortDesc) {
                $descriptions[$productId] = $shortDesc;
            }

            // Custom Tabs
            $descTabVal = $getAttr('description_tab');
            if ($descTabVal) $descTab[$productId] = $descTabVal;

            $prodDetailsVal = $getAttr('product_details_tab');
            if ($prodDetailsVal) $prodDetailsTab[$productId] = $prodDetailsVal;

            // Check both singular and plural for Care Instructions
            $careInstVal = $getAttr('care_instruction_tab');
            if (!$careInstVal) $careInstVal = $getAttr('care_instructions_tab');
            if ($careInstVal) $careInstTab[$productId] = $careInstVal;

            $techSpec1Val = $getAttr('technical_specs_tab1');
            if (!$techSpec1Val) $techSpec1Val = $getAttr('technical_specs_tab');
            if ($techSpec1Val) $techSpecTab1[$productId] = $techSpec1Val;

            $techSpec2Val = $getAttr('technical_specs_tab2');
            if ($techSpec2Val) $techSpecTab2[$productId] = $techSpec2Val;

            $moqVal = $getAttr('moq');
            if ($moqVal !== null) $moqList[$productId] = $moqVal;

            $shatchiMoqVal = $getAttr('shatchi_moq');
            if ($shatchiMoqVal !== null) $shatchiMoqList[$productId] = $shatchiMoqVal;

            $cartonPriceVal = $getAttr('shatchi_carton_price');
            if ($cartonPriceVal !== null) $cartonPriceList[$productId] = $cartonPriceVal;

            $cartonQtyVal = $getAttr('shatchi_carton_qty');
            if ($cartonQtyVal !== null) $cartonQtyList[$productId] = $cartonQtyVal;

            // Populate dynamically requested attributes
            if (!empty($gridColumns)) {
                foreach ($gridColumns as $col) {
                    if (strpos($col['code'], 'attr_') === 0) {
                        $attrCode = substr($col['code'], 5);
                        $attrValue = $getAttr($attrCode);
                        $dynamicAttrs[$attrCode][$productId] = $attrValue !== null ? $attrValue : '-';
                    }
                }
            }
        }

        // Inject our custom arrays into the main jsonConfig
        $config['descriptions'] = $descriptions;
        $config['description_tab'] = $descTab;
        $config['product_details_tab'] = $prodDetailsTab;
        $config['care_instruction_tab'] = $careInstTab;
        $config['technical_specs_tab1'] = $techSpecTab1;
        $config['technical_specs_tab2'] = $techSpecTab2;
        $config['moq'] = $moqList;
        $config['shatchi_moq'] = $shatchiMoqList;
        $config['shatchi_carton_price'] = $cartonPriceList;
        $config['shatchi_carton_qty'] = $cartonQtyList;
        $config['leds_no'] = $ledsNoList;
        $config['dynamic_attrs'] = $dynamicAttrs;

        return $this->jsonEncoder->encode($config);
    }
}