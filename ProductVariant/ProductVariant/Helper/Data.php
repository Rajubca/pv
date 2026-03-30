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

    /**
     * Get grid columns configured in the admin for a specific attribute set
     *
     * @param int $attributeSetId
     * @param int|null $storeId
     * @return array
     */
        public function getGridColumns($attributeSetId, $storeId = null)
    {
        $columnsData = $this->scopeConfig->getValue(
            self::XML_PATH_GRID_COLUMNS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!$columnsData) {
            return [];
        }

        try {
            // In Magento 2.4.5, ArraySerialized backend model saves as JSON string
            $parsedData = json_decode($columnsData, true);

            // Fallback to serializer if json_decode fails (for legacy serialized data)
            if ($parsedData === null && json_last_error() !== JSON_ERROR_NONE) {
                $parsedData = $this->serializer->unserialize($columnsData);
            }

            if (!is_array($parsedData)) {
                return [];
            }

            $matchedColumns = [];
            $defaultColumns = []; // Fallback for "All Attribute Sets" (0)

            foreach ($parsedData as $key => $row) {
                // Skip the hidden '__empty' field Magento dynamic rows might inject
                if ($key === '__empty') {
                    continue;
                }

                if (!isset($row['attribute_set']) || !isset($row['column_code']) || !isset($row['custom_header'])) {
                    continue;
                }

                $colData = [
                    'code' => $row['column_code'],
                    'header' => $row['custom_header'],
                    'sort_order' => isset($row['sort_order']) ? (int)$row['sort_order'] : 0
                ];

                // Loose comparison to handle string '4' vs int 4
                if ($row['attribute_set'] == $attributeSetId) {
                    $matchedColumns[] = $colData;
                } elseif ($row['attribute_set'] == 0 || $row['attribute_set'] == '0') {
                    $defaultColumns[] = $colData;
                }
            }

            // Return matched columns, or defaults if no specific config exists for this set
            $finalColumns = !empty($matchedColumns) ? $matchedColumns : $defaultColumns;

            // Sort by sort_order
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
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_CARTON_PRICING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isShowBasePriceSummary($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_BASE_PRICE_SUMMARY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isTechSpecEnabledInDescription($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_DESC_TECH_SPEC,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isTechSpecEnabledInDetails($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_DETAILS_TECH_SPEC,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
