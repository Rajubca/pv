<?php
namespace Shatchi\ProductVariant\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    const XML_PATH_ENABLE_CUSTOM_GRID = 'shatchi_variant/general/enable_custom_grid';
    // No longer a static path, depends on clone group
    // const XML_PATH_GRID_COLUMNS = 'shatchi_variant/general/grid_columns';
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
                    public function getGridColumns($attributeSetIds, $storeId = null)
    {
        // Accept either a single ID or an array of IDs
        if (!is_array($attributeSetIds)) {
            $attributeSetIds = [$attributeSetIds];
        }

        // Push 0 (Global fallback) to the end of the array to check last
        $attributeSetIds[] = 0;

        foreach ($attributeSetIds as $id) {
            // New cloned group path: shatchi_variant/attribute_set_columns_{$id}/grid_columns
            $path = 'shatchi_variant/attribute_set_columns_' . $id . '/grid_columns';

            $columnsData = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            if (!$columnsData) {
                continue; // Not configured for this set, try the next one (eventually hits 0)
            }

            try {
                $parsedData = $columnsData;

                // Depending on cache, Magento may return either a parsed array or a JSON string
                if (is_string($columnsData)) {
                    $parsedData = json_decode($columnsData, true);

                    if ($parsedData === null && json_last_error() !== JSON_ERROR_NONE) {
                        $parsedData = $this->serializer->unserialize($columnsData);
                    }
                }

                if (!is_array($parsedData) || empty($parsedData)) {
                    continue;
                }

                $matchedColumns = [];

                foreach ($parsedData as $key => $row) {
                    // Skip the hidden '__empty' field Magento dynamic rows might inject
                    if ($key === '__empty') {
                        continue;
                    }

                    if (!isset($row['column_code']) || !isset($row['custom_header'])) {
                        continue;
                    }

                    $matchedColumns[] = [
                        'code' => $row['column_code'],
                        'header' => $row['custom_header'],
                        'sort_order' => isset($row['sort_order']) ? (int)$row['sort_order'] : 0
                    ];
                }

                if (!empty($matchedColumns)) {
                    // Sort by sort_order
                    usort($matchedColumns, function($a, $b) {
                        return $a['sort_order'] <=> $b['sort_order'];
                    });

                    // Return the FIRST matching, configured attribute set
                    return $matchedColumns;
                }

            } catch (\Exception $e) {
                continue;
            }
        }

        return [];
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
