<?php
namespace Shatchi\ProductVariant\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class GridColumns extends AbstractFieldArray
{
    /**
     * @var AttributeSetColumn
     */
    private $attributeSetRenderer;

    /**
     * @var AttributeColumn
     */
    private $attributeRenderer;

    protected function _prepareToRender()
    {
        $this->addColumn('attribute_set', [
            'label' => __('Attribute Set'),
            'renderer' => $this->getAttributeSetRenderer(),
        ]);

        $this->addColumn('column_code', [
            'label' => __('Column'),
            'renderer' => $this->getAttributeRenderer(),
        ]);

        $this->addColumn('custom_header', [
            'label' => __('Custom Header'),
            'class' => 'required-entry',
        ]);

        $this->addColumn('sort_order', [
            'label' => __('Sort Order'),
            'class' => 'required-entry validate-number',
            'style' => 'width: 60px;',
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Column');
    }

    protected function _prepareArrayRow(DataObject $row)
    {
        $options = [];

        $attributeSet = $row->getAttributeSet();
        if ($attributeSet !== null) {
            $options['option_' . $this->getAttributeSetRenderer()->calcOptionHash($attributeSet)] = 'selected="selected"';
        }

        $columnCode = $row->getColumnCode();
        if ($columnCode !== null) {
            $options['option_' . $this->getAttributeRenderer()->calcOptionHash($columnCode)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    private function getAttributeSetRenderer()
    {
        if (!$this->attributeSetRenderer) {
            $this->attributeSetRenderer = $this->getLayout()->createBlock(
                AttributeSetColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->attributeSetRenderer;
    }

    private function getAttributeRenderer()
    {
        if (!$this->attributeRenderer) {
            $this->attributeRenderer = $this->getLayout()->createBlock(
                AttributeColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->attributeRenderer;
    }

    /**
     * Inject custom JS to filter rows by Attribute Set visually!
     */
    protected function _toHtml()
    {
        $html = parent::_toHtml();

        // Render the exact HTML options natively from PHP, so JS doesn't have to guess or wait for DOM
        $optionsHtml = $this->getAttributeSetRenderer()->_toHtml();
        // Since _toHtml for a Select returns <select...><option...></select>, we need to strip the <select> tags
        // to cleanly inject just the <option> blocks into our custom header dropdown
        if (preg_match('/<select[^>]*>(.*?)<\/select>/is', $optionsHtml, $matches)) {
            $rawOptions = $matches[1];
        } else {
            // Ultimate fallback (should never happen because we control AttributeSetColumn)
            $rawOptions = '<option value="0">-- Global / All Attribute Sets --</option>';
        }

        $js = <<<HTML
        <style>
            .shatchi-filter-container {
                margin-bottom: 20px;
                padding: 15px;
                background: #f8f8f8;
                border: 1px solid #ccc;
                display: flex;
                align-items: center;
                gap: 15px;
            }
            .shatchi-filter-container label {
                font-weight: 600;
            }
            .shatchi-filter-container select {
                width: 250px;
            }
            /* Visually hide the Attribute Set dropdown column headers and cells from the Magento table */
            #row_shatchi_variant_general_grid_columns table th:first-child,
            #row_shatchi_variant_general_grid_columns table td:first-child {
                display: none !important;
            }
        </style>

        <div id="shatchi_grid_columns_filter" class="shatchi-filter-container">
            <label for="shatchi_filter_select">Configure Columns For:</label>
            <select id="shatchi_filter_select" class="admin__control-select">
                <option value="all">-- Show Everything (All Sets) --</option>
                {$rawOptions}
            </select>
        </div>

        <script>
        require(['jquery', 'domReady!'], function($) {
            var filterSelect = $('#shatchi_filter_select');

            // Set initial state to Global
            filterSelect.val('0');

            // The row template might not be fully instantiated by Magento's JS yet,
            // so we wait briefly then apply our initial filter.
            setTimeout(applyFilter, 100);

            function applyFilter() {
                var selectedVal = filterSelect.val();

                // Show/Hide rows
                $('#row_shatchi_variant_general_grid_columns table tbody tr').each(function() {
                    var \$row = $(this);
                    var \$dropdown = \$row.find('.shatchi-attr-set-dropdown');

                    if (\$dropdown.length) {
                        if (selectedVal === 'all') {
                            \$row.show();
                        } else if (\$dropdown.val() === selectedVal) {
                            \$row.show();
                        } else {
                            \$row.hide();
                        }
                    }
                });
            }

            // Listen for filter changes
            filterSelect.on('change', applyFilter);

            // Listen for clicks on the "Add Column" button
            // Automatically assign the newly added row to the currently selected attribute set!
            $('#row_shatchi_variant_general_grid_columns').on('click', '.action-add', function() {
                var selectedVal = filterSelect.val();

                // If they are on "Show Everything", default the new row to Global (0)
                if (selectedVal === 'all') {
                    selectedVal = '0';
                }

                // Wait for Magento to render the row into the DOM
                setTimeout(function() {
                    var \$newRow = $('#row_shatchi_variant_general_grid_columns table tbody tr:last');
                    var \$dropdown = \$newRow.find('.shatchi-attr-set-dropdown');

                    if (\$dropdown.length) {
                        \$dropdown.val(selectedVal);
                    }
                }, 50);
            });

            // Re-apply filter when Magento deletes a row
            $('body').on('click', '#row_shatchi_variant_general_grid_columns .action-delete', function() {
                setTimeout(applyFilter, 100);
            });
        });
        </script>
HTML;

        // Prepend our filter UI right before the actual Magento table element
        return $js . $html;
    }
}