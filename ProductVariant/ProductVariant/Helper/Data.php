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

    // Base Columns Configuration Paths
    const XML_PATH_BASE_COLUMNS = 'shatchi_variant/base_columns';

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
     * Get configured base columns with their headers and sort orders
     */
    protected function getBaseColumns($storeId = null)
    {
        $baseColumns = [];

        $fields = [
            'item_code' => ['header' => 'Item Code', 'sort' => 10],
            'min_qty' => ['header' => 'Min Qty', 'sort' => 20],
            'moq_price' => ['header' => 'Moq Price/PC', 'sort' => 30],
            'carton_qty' => ['header' => 'Ctn Qty', 'sort' => 40],
            'carton_price' => ['header' => 'Ctn Price/PC', 'sort' => 50],
            'qty' => ['header' => 'Qty', 'sort' => 60],
            'subtotal' => ['header' => 'Subtotal', 'sort' => 70],
        ];

        foreach ($fields as $code => $defaults) {
            $headerConfig = $this->scopeConfig->getValue(
                self::XML_PATH_BASE_COLUMNS . '/' . $code . '_header',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            $sortConfig = $this->scopeConfig->getValue(
                self::XML_PATH_BASE_COLUMNS . '/' . $code . '_sort',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            $baseColumns[] = [
                'code' => $code,
                'header' => $headerConfig !== null && $headerConfig !== '' ? $headerConfig : $defaults['header'],
                'sort_order' => $sortConfig !== null && $sortConfig !== '' ? (int)$sortConfig : $defaults['sort']
            ];
        }

        return $baseColumns;
    }

    public function getGridColumns($attributeSetIds, $storeId = null)
    {
        if (!is_array($attributeSetIds)) {
            $attributeSetIds = [$attributeSetIds];
        }

        // 1. Get Base Columns
        $baseColumns = $this->getBaseColumns($storeId);

        // 2. Get Dynamic Columns
        $columnsData = $this->scopeConfig->getValue(
            self::XML_PATH_GRID_COLUMNS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $dynamicColumns = [];
        if ($columnsData) {
            try {
                $parsedData = $columnsData;

                if (is_string($columnsData)) {
                    $parsedData = json_decode($columnsData, true);
                    if ($parsedData === null && json_last_error() !== JSON_ERROR_NONE) {
                        $parsedData = $this->serializer->unserialize($columnsData);
                    }
                }

                if (is_array($parsedData)) {
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

                        if (in_array((int)$row['attribute_set'], $attributeSetIds)) {
                            $matchedColumns[(int)$row['attribute_set']][] = $colData;
                        } elseif ($row['attribute_set'] == 0 || $row['attribute_set'] == '0') {
                            $defaultColumns[] = $colData;
                        }
                    }

                    $dynamicColumns = $defaultColumns;
                    foreach ($attributeSetIds as $id) {
                        if (isset($matchedColumns[$id]) && !empty($matchedColumns[$id])) {
                            $dynamicColumns = $matchedColumns[$id];
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Keep empty
            }
        }

        // 3. Merge and Sort
        $finalColumns = array_merge($baseColumns, $dynamicColumns);

        usort($finalColumns, function($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });

        return $finalColumns;
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
