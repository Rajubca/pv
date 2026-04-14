<?php
namespace Shatchi\ProductVariant\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    const XML_PATH_ENABLE_CUSTOM_GRID = 'shatchi_variant/general/enable_custom_grid';
    const XML_PATH_GRID_COLUMNS = 'shatchi_variant/general/grid_columns';
    const XML_PATH_ENABLE_CARTON_PRICING = 'shatchi_variant/general/enable_carton_pricing';
    const XML_PATH_SHOW_BASE_PRICE_SUMMARY = 'shatchi_variant/general/show_base_price_summary';

    const XML_PATH_ENABLE_DESC_TECH_SPEC = 'shatchi_variant/tabs_settings/enable_description_tech_spec';
    const XML_PATH_ENABLE_DETAILS_TECH_SPEC = 'shatchi_variant/tabs_settings/enable_details_tech_spec';

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    public function __construct(
        Context $context,
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    public function isCustomGridEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_CUSTOM_GRID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

        public function getGridColumns($attributeSetIds, $storeId = null)
    {
        if (!is_array($attributeSetIds)) {
            $attributeSetIds = [$attributeSetIds];
        }

        $columnsData = $this->scopeConfig->getValue(
            self::XML_PATH_GRID_COLUMNS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!$columnsData) {
            return [];
        }

        try {
            $parsedData = $columnsData;

            if (is_string($columnsData)) {
                $parsedData = json_decode($columnsData, true);
                if ($parsedData === null && json_last_error() !== JSON_ERROR_NONE) {
                    $parsedData = $this->serializer->unserialize($columnsData);
                }
            }

            if (!is_array($parsedData)) {
                return [];
            }

            $matchedColumns = [];
            $defaultColumns = [];

            foreach ($parsedData as $key => $row) {
                if ($key === '__empty') continue;
                if (!isset($row['attribute_set']) || !isset($row['column_code']) || !isset($row['custom_header'])) continue;

                $colData = [
                    'code' => $row['column_code'],
                    'header' => $row['custom_header'],
                    'sort_order' => isset($row['sort_order']) ? (int)$row['sort_order'] : 0
                ];

                // Loosely check if it belongs to any of the product's Attribute Sets
                if (in_array((int)$row['attribute_set'], $attributeSetIds)) {
                    $matchedColumns[(int)$row['attribute_set']][] = $colData;
                } elseif ($row['attribute_set'] == 0 || $row['attribute_set'] == '0') {
                    // Global (Base) columns that apply to EVERY attribute set
                    $defaultColumns[] = $colData;
                }
            }

            // MERGE the Global Base columns with the specific Attribute Set's extra columns
            $finalColumns = $defaultColumns;

            // Append any specific columns found for the current product's attribute sets
            foreach ($attributeSetIds as $id) {
                if (isset($matchedColumns[$id]) && !empty($matchedColumns[$id])) {
                    // If a specific column code exactly matches a global base column, we overwrite the global one
                    // so the admin can override a base column's header or sort order for a specific Attribute Set!
                    foreach ($matchedColumns[$id] as $specificCol) {
                        $overwritten = false;
                        foreach ($finalColumns as $k => $baseCol) {
                            if ($baseCol['code'] === $specificCol['code']) {
                                $finalColumns[$k] = $specificCol;
                                $overwritten = true;
                                break;
                            }
                        }
                        if (!$overwritten) {
                            $finalColumns[] = $specificCol;
                        }
                    }
                    break; // Once we find the specific config for this product, we stop appending other child sets
                }
            }

            // Finally, sort ALL merged columns by their designated sort order
            usort($finalColumns, function($a, $b) {
                return $a['sort_order'] <=> $b['sort_order'];
            });

            return $finalColumns;

        } catch (\Exception $e) {
            return [];
        }
    }

    public function isCartonPricingEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE_CARTON_PRICING, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isShowBasePriceSummary($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_SHOW_BASE_PRICE_SUMMARY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isTechSpecEnabledInDescription($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE_DESC_TECH_SPEC, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isTechSpecEnabledInDetails($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE_DETAILS_TECH_SPEC, ScopeInterface::SCOPE_STORE, $storeId);
    }
}