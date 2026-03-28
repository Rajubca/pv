<?php

namespace Shatchi\ProductVariant\Plugin\ConfigurableProduct\Block\Product\View\Type;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Json\DecoderInterface;

class ConfigurablePlugin
{
    protected $jsonEncoder;
    protected $jsonDecoder;

    public function __construct(
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder
    ) {
        $this->jsonEncoder = $jsonEncoder;
        $this->jsonDecoder = $jsonDecoder;
    }

    public function afterGetJsonConfig(Configurable $subject, $result)
    {
        $config = $this->jsonDecoder->decode($result);

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
        // Get all simple products that belong to this configurable product
        $products = $subject->getAllowProducts();

        foreach ($products as $product) {
            $productId = $product->getId();

            // Helper to get attribute safely (handles if not loaded in collection)
            $getAttr = function ($code) use ($product) {
                $val = $product->getData($code);
                if ($val === null) {
                    $resource = $product->getResource();
                    if ($resource) {
                        $rawVal = $resource->getAttributeRawValue($product->getId(), $code, $product->getStoreId());
                        if ($rawVal !== false && !is_array($rawVal)) {
                            return $rawVal;
                        }
                    }
                }
                return $val;
            };

            // Standard Description fallback
            $ledsVal = $product->getAttributeText('leds_no');

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
        return $this->jsonEncoder->encode($config);
    }
}
