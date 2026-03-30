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

        // Grab the options array from our renderer to build the filter dropdown
        $optionsHtml = $this->getAttributeSetRenderer()->toHtml();

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
        </style>

        <div id="shatchi_grid_columns_filter" class="shatchi-filter-container">
            <label for="shatchi_filter_select">Configure Columns For:</label>
            <!-- We reuse the generated select HTML but change its ID and disable its name so it doesn't save to config -->
            <select id="shatchi_filter_select" class="admin__control-select">
                <option value="all">-- Show Everything (All Sets) --</option>
            </select>
        </div>

        <script>
        require(['jquery', 'domReady!'], function($) {
            var filterSelect = $('#shatchi_filter_select');

            // Populate the filter dropdown with the attribute sets dynamically by stealing options from the first row template
            setTimeout(function() {
                var prototypeHtml = $('#grid_columns_row_template').html() || '';
                var match = prototypeHtml.match(/<select[^>]*class="[^"]*shatchi-attr-set-dropdown[^"]*"[^>]*>([\s\S]*?)<\/select>/i);

                if (match && match[1]) {
                    filterSelect.append(match[1]);
                } else {
                    // Fallback to searching the DOM if template string parsing fails
                    var firstSelect = $('tr[id^="grid_columns"] .shatchi-attr-set-dropdown').first();
                    if (firstSelect.length) {
                        filterSelect.append(firstSelect.html());
                    }
                }

                // Initially set filter to Global (0)
                filterSelect.val('0');
                applyFilter();
            }, 500);

            function applyFilter() {
                var selectedVal = filterSelect.val();

                // If "all" is selected, show everything
                if (selectedVal === 'all') {
                    $('#row_shatchi_variant_general_grid_columns table tbody tr').show();
                    return;
                }

                // Loop through all data rows (ignoring header)
                $('#row_shatchi_variant_general_grid_columns table tbody tr').each(function() {
                    var \$row = $(this);
                    var \$dropdown = \$row.find('.shatchi-attr-set-dropdown');

                    if (\$dropdown.length) {
                        if (\$dropdown.val() === selectedVal) {
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
            // Magento dynamically adds the row to the DOM, so we wait 50ms then update the new row's dropdown!
            $('#row_shatchi_variant_general_grid_columns .action-add').on('click', function() {
                var selectedVal = filterSelect.val();

                if (selectedVal !== 'all') {
                    setTimeout(function() {
                        var \$newRow = $('#row_shatchi_variant_general_grid_columns table tbody tr:last');
                        var \$dropdown = \$newRow.find('.shatchi-attr-set-dropdown');

                        if (\$dropdown.length) {
                            \$dropdown.val(selectedVal);
                        }
                    }, 50);
                }
            });

            // Re-apply filter when Magento does anything to the rows natively (e.g. deleting a row)
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