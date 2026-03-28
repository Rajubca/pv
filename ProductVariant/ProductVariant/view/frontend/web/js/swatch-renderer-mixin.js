define([
    'jquery',
    'underscore',
    'mage/translate'
], function ($, _, $t) {
    'use strict';

    return function (widget) {
        $.widget('mageplaza_configurable.SwatchRenderer', widget, {

            _getActiveAttribute: function (product, productId, widget) {
                var isEnabled = window.shatchiVariantGridEnabled || false;

                if (!isEnabled) {
                    return this._super(product, productId, widget);
                }

                var container = widget.formContainer.find('#mpcpgv-attribute-table>tbody'),
                    classes = widget.options.classes;

                // -------------------------------
                // 1. Resolve MOQ
                // -------------------------------
                var productMoq = 1;

                if (widget.options.jsonConfig) {
                    if (widget.options.jsonConfig.moq && widget.options.jsonConfig.moq[productId]) {
                        productMoq = parseFloat(widget.options.jsonConfig.moq[productId]);
                    } else if (widget.options.jsonConfig.shatchi_moq && widget.options.jsonConfig.shatchi_moq[productId]) {
                        productMoq = parseFloat(widget.options.jsonConfig.shatchi_moq[productId]);
                    }
                }

                var jsonConfig = widget.options.jsonConfig || {};

                var cartonQty = '-';
                var cartonPrice = '-';

                // Carton Qty with formatting to 2 decimal places
                if (jsonConfig.shatchi_carton_qty && jsonConfig.shatchi_carton_qty[productId]) {
                    var rawCartonQty = parseFloat(jsonConfig.shatchi_carton_qty[productId]);
                    // Format to 2 decimal places, remove trailing zeros if needed
                    cartonQty = rawCartonQty.toFixed(2).replace(/\.?0+$/, '');

                    // Alternative: always show 2 decimal places (e.g., 10.00)
                    // cartonQty = rawCartonQty.toFixed(2);
                }

                // Carton Price (already formatted by widget.getFormattedPrice)
                if (jsonConfig.shatchi_carton_price && jsonConfig.shatchi_carton_price[productId]) {
                    cartonPrice = widget.getFormattedPrice(
                        jsonConfig.shatchi_carton_price[productId]
                    );
                }

                // Get MOQ Price (base price)
                var moqPricePerUnit = 0;
                if (jsonConfig.optionPrices && jsonConfig.optionPrices[productId]) {
                    moqPricePerUnit = jsonConfig.optionPrices[productId].finalPrice.amount;
                }

                var formattedMoqPrice = widget.getFormattedPrice(moqPricePerUnit);

                // MOQ Total Price (MOQ × MOQ Price)
                var moqTotalPrice = moqPricePerUnit * productMoq;
                var formattedMoqTotalPrice = widget.getFormattedPrice(moqTotalPrice);

                // -------------------------------
                // 2. Separate attributes + other data
                // -------------------------------
                var attributes = [];
                var otherData = {};

                _.each(product, function (item, key) {
                    if (typeof item === 'object' && item.code) {
                        attributes.push(item);
                    } else {
                        otherData[key] = item;
                    }
                });

                // -------------------------------
                // 3. Sort attributes (Color first)
                // -------------------------------
                var order = ['shatchi_color_v2', 'leds_no', 'r_size'];

                attributes = _.sortBy(attributes, function (attr) {
                    var idx = order.indexOf(attr.code);
                    return idx === -1 ? 999 : idx;
                });

                // -------------------------------
                // 4. Extract COLOR for grouping
                // -------------------------------
                var colorLabel = 'Unknown';

                _.each(attributes, function (attr) {
                    if (attr.code === 'shatchi_color_v2' && attr.options && attr.options.length) {
                        colorLabel = attr.options[0].label;
                    }
                });

                var safeColor = colorLabel.replace(/\s+/g, '-').toLowerCase();
                var groupId = 'shatchi-group-' + safeColor;

                // -------------------------------
                // 5. Build row with custom column order
                // -------------------------------
                var row = '<tr class="shatchi-custom-row" product-id="' + productId + '">';

                // Render attributes
                var attrMap = {};

                _.each(attributes, function (attr) {
                    if (attr.options && attr.options.length) {
                        attrMap[attr.code] = attr.options[0].label;
                    }
                });

                // -------------------------------

                // -------------------------------
                // DYNAMIC COLUMN ORDER FROM CONFIG
                // -------------------------------
                var dynamicColumns = widget.options.jsonConfig.shatchi_grid_columns || [];

                // Fallback to default layout if no dynamic columns configured
                if (dynamicColumns.length === 0) {
                    dynamicColumns = [
                        {code: 'item_code', header: 'Item Code'},
                        {code: 'attr_leds_no', header: 'LEDs'},
                        {code: 'attr_r_size', header: 'Size'},
                        {code: 'min_qty', header: 'Min Qty'},
                        {code: 'moq_price', header: 'Moq Price/PC'},
                        {code: 'carton_qty', header: 'Ctn Qty'},
                        {code: 'carton_price', header: 'Ctn Price/PC'},
                        {code: 'qty', header: 'Qty'},
                        {code: 'subtotal', header: 'Subtotal'}
                    ];
                }

                // Build row based on dynamic column order
                _.each(dynamicColumns, function (columnData) {
                    var column = columnData.code;

                    switch (column) {
                        case 'item_code':
                            if (otherData.sku) {
                                row += `<td class="mpcpgv-sku">${otherData.sku}<\/td>`;
                            } else {
                                row += `<td class="mpcpgv-sku">-<\/td>`;
                            }
                            break;

                        case 'min_qty':
                            row += `<td class="shatchi-moq">${productMoq}<\/td>`;
                            break;

                        case 'moq_price':
                            row += `<td class="shatchi-moq-price">${formattedMoqPrice}<\/td>`;
                            break;

                        case 'carton_qty':
                            row += `<td class="shatchi-carton-qty">${cartonQty}<\/td>`;
                            break;

                        case 'carton_price':
                            row += `<td class="shatchi-carton-price">${cartonPrice}<\/td>`;
                            break;

                        case 'qty':
                            if (otherData.qty) {
                                var $itemHtml = $('<div>').append(otherData.qty);
                                var $input = $itemHtml.find('input.mpcpgv-input');

                                if ($input.length) {
                                    $input.attr({
                                        min: productMoq,
                                        step: 1,
                                        value: productMoq
                                    });

                                    var isFirstRow = !container.children().length;

                                    if (isFirstRow) {
                                        $input.val(productMoq);   // first row
                                    } else {
                                        $input.val(0);            // others
                                    }
                                }

                                row += `<td class="mpcpgv-qty">${$itemHtml.html()}<\/td>`;
                            } else {
                                row += `<td class="mpcpgv-qty">-<\/td>`;
                            }
                            break;

                        case 'subtotal':
                            if (otherData.subtotal) {
                                row += `<td class="mpcpgv-subtotal">${otherData.subtotal}<\/td>`;
                            } else {
                                row += `<td class="mpcpgv-subtotal">-<\/td>`;
                            }
                            break;

                        default:
                            // Handle dynamic attributes
                            if (column.startsWith('attr_')) {
                                var attrCode = column.substring(5);
                                var attrVal = '-';

                                // 1. Try to get it from configurable options array (attrMap)
                                if (attrMap[attrCode]) {
                                    attrVal = attrMap[attrCode];
                                }
                                // 2. Try to get it from dynamically fetched attributes in JSON config
                                else if (widget.options.jsonConfig.dynamic_attrs &&
                                         widget.options.jsonConfig.dynamic_attrs[attrCode] &&
                                         widget.options.jsonConfig.dynamic_attrs[attrCode][productId]) {
                                    attrVal = widget.options.jsonConfig.dynamic_attrs[attrCode][productId];
                                }
                                // 3. Try fallback to root level JSON config (backward compatibility)
                                else if (widget.options.jsonConfig[attrCode] &&
                                         widget.options.jsonConfig[attrCode][productId]) {
                                    attrVal = widget.options.jsonConfig[attrCode][productId];
                                }

                                row += `<td class="shatchi-dynamic-attr" data-attr="${attrCode}">${attrVal}<\/td>`;
                            }
                            break;
                    }
                });


                row += '<\/tr>';

                // -------------------------------
                // 6. Insert row
                // -------------------------------
                container.append(row);

                // -------------------------------
                // 7. Trigger calculations
                // -------------------------------
                setTimeout(function () {
                    var $input = container.find(`tr[product-id="${productId}"] input.mpcpgv-input`);
                    if ($input.length && $input.val() > 0) {
                        widget._KeyupChange($input, widget, true);
                    }
                }, 50);
            },

            _EventListener: function () {
                this._super();

                setTimeout(function () {
                    // -------------------------------

                    // -------------------------------
                    // DYNAMIC COLUMN ORDER FOR HEADER FROM CONFIG
                    // -------------------------------
                    var jsonConfigData = null;

                    // Mageplaza configurable widget keeps options attached to the element
                    var mpWidget = $('[data-role=swatch-options]').data('mageplaza_configurable-SwatchRenderer') ||
                                   $('[data-role=swatch-options]').data('mage-SwatchRenderer');

                    if (mpWidget && mpWidget.options && mpWidget.options.jsonConfig) {
                        jsonConfigData = mpWidget.options.jsonConfig;
                    } else if (window.mpConfigurableJsonConfig) {
                        jsonConfigData = window.mpConfigurableJsonConfig;
                    }

                    var dynamicColumns = jsonConfigData && jsonConfigData.shatchi_grid_columns ? jsonConfigData.shatchi_grid_columns : [];

                    // Fallback to default layout
                    if (dynamicColumns.length === 0) {
                        dynamicColumns = [
                            {code: 'item_code', header: 'Item Code'},
                            {code: 'attr_leds_no', header: 'LEDs'},
                            {code: 'attr_r_size', header: 'Size'},
                            {code: 'min_qty', header: 'Min Qty'},
                            {code: 'moq_price', header: 'Moq Price/PC'},
                            {code: 'carton_qty', header: 'Ctn Qty'},
                            {code: 'carton_price', header: 'Ctn Price/PC'},
                            {code: 'qty', header: 'Qty'},
                            {code: 'subtotal', header: 'Subtotal'}
                        ];
                    }

                    // Build header HTML based on dynamic columns
                    var headerHtml = '';
                    _.each(dynamicColumns, function (columnData) {
                        headerHtml += `<th>${columnData.header}</th>`;
                    });


                    $('#mpcpgv-attribute-table thead tr').html(headerHtml);

                    var $dropdown = $('.mpcpgv-attribute-inactive select');

                    if ($dropdown.length) {
                        var firstVal = $dropdown.find('option[value!=""]').first().val();

                        if (firstVal) {
                            $dropdown.val(firstVal).trigger('change');
                        }
                    }

                    // Hide only product rows (NOT totals)
                    var $table = $('#mpcpgv-simple-product');

                    // Hide ONLY rows
                    $table.find('tbody tr').hide();

                    // Hide header
                    $table.find('thead').hide();

                    // ✅ KEEP totals visible (IMPORTANT)
                    $table.find('tfoot').show();
                    $table.find('.mpcpgv-summary').show();
                    $('#mpcpgv-overflow').hide();
                    // Optional UI cleanup
                    $('#mpcpgv-detail-title').hide();
                    $('.mpcpgv-attribute-inactive').hide();

                    // ✅ ADD THIS BLOCK HERE
                    $('#product_addtocart_form')
                        .off('submit.shatchiValidation')
                        .on('submit.shatchiValidation', function (e) {

                            var hasQty = false;

                            $('.mpcpgv-input').each(function () {
                                if (parseInt($(this).val()) > 0) {
                                    hasQty = true;
                                }
                            });

                            if (!hasQty) {
                                e.preventDefault();
                                alert('Please select at least one product.');
                            }
                        });

                }, 10);

                var $widget = this;
                var isEnabled = window.shatchiVariantGridEnabled || false;

                if (isEnabled) {
                    // Inject CSS once
                    if (!$('#shatchi-row-style').length) {
                        $('<style id="shatchi-row-style">\
                .shatchi-custom-row { cursor: pointer; transition: background-color 0.2s ease; }\
                .shatchi-custom-row:hover { background-color: #f8fafc; }\
                .shatchi-row-highlight { background-color: #e2e8f0 !important; }\
                .shatchi-row-active { background-color: #f1f5f9; border-left: 3px solid #3b82f6; }\
                /* Disable Mageplaza sorting aggressively */\
                table[id*="mpcpgv"] thead th { pointer-events: none !important; cursor: default !important; }\
                table[id*="mpcpgv"] thead th::after, table[id*="mpcpgv"] thead th::before, \
                table[id*="mpcpgv"] thead th i, table[id*="mpcpgv"] thead th span.sort-icon { display: none !important; opacity: 0 !important; }\
            </style>').appendTo('head');
                    }

                    // Use a timeout to aggressively remove sorting events after Mageplaza initializes them
                    setTimeout(function () {
                        var $headers = $('table[id*="mpcpgv"] thead th');
                        $headers.off('click');
                        $headers.removeClass('mpcpgv-sortable sort-asc sort-desc');
                    }, 500);

                    // Add an active blocker just in case
                    $('body').off('click.shatchiSortBlock').on('click.shatchiSortBlock', 'table[id*="mpcpgv"] thead th', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    });

                    // Use namespaced event to avoid duplicate bindings
                    $('body')
                        .off('click.shatchiVariant')
                        .on('click.shatchiVariant', '.shatchi-custom-row', function (e) {

                            // Ignore clicks inside controls
                            if ($(e.target).closest('input, button, a, .mpcpgv-input, .mpcpgv-decrement, .mpcpgv-increment').length > 0) {
                                return;
                            }

                            var $row = $(this);
                            var productId = $row.attr('product-id');

                            if (!productId) return;

                            // Build attribute map FIRST
                            var selectedMap = {};
                            $row.find('.shatchi-grid-variant-label').each(function () {
                                var attrId = $(this).attr('attribute-id');
                                var optionId = $(this).attr('attribute-value');

                                if (attrId && optionId) {
                                    selectedMap[attrId] = optionId;
                                }
                            });
                            // ✅ THEN APPLY (THIS WAS YOUR BUG)
                            $.each(selectedMap, function (attrId, optionId) {
                                var $select = $(`select[name="super_attribute[${attrId}]"]`);
                                if ($select.length) {
                                    $select.val(optionId).trigger('change');
                                }
                            });
                            // Sync Magento core FIRST
                            if (typeof $widget._EmulateSelectedByAttributeId === 'function') {
                                $widget._EmulateSelectedByAttributeId(selectedMap);
                            }
                            $widget.simpleProduct = productId;

                            // Update Images (Magento-safe)
                            if ($widget.options.jsonConfig && $widget.options.jsonConfig.images && $widget.options.jsonConfig.images[productId]) {
                                var images = $widget.options.jsonConfig.images[productId];
                                var $main = $widget.element.parents('.column.main');

                                if (typeof $widget.updateBaseImage === 'function') {
                                    var sortedImages = typeof $widget._sortImages === 'function' ? $widget._sortImages(images) : images;
                                    $widget.updateBaseImage(
                                        sortedImages,
                                        $main.length ? $main : $('body'),
                                        true
                                    );
                                }
                            }

                            // Trigger tab/content updates
                            $(document).trigger('shatchi:variant:selected', [productId, $widget]);

                            // UI highlight
                            $('.shatchi-custom-row').removeClass('shatchi-row-active');
                            $row.addClass('shatchi-row-active');
                        });
                }
            },

            // Override increment to jump to Shatchi MOQ if current value is 0
            _OnIncClick: function ($this, $widget) {
                var isEnabled = window.shatchiVariantGridEnabled || false;
                if (!isEnabled) {
                    return this._super($this, $widget);
                }

                var input = $this.parent().parent().find('.mpcpgv-input'),
                    old = Number(input.val()),
                    productId = input.attr('product-id');

                var productMoq = 1;
                if ($widget.options.jsonConfig) {
                    if ($widget.options.jsonConfig.moq && $widget.options.jsonConfig.moq[productId]) {
                        productMoq = parseFloat($widget.options.jsonConfig.moq[productId]);
                    } else if ($widget.options.jsonConfig.shatchi_moq && $widget.options.jsonConfig.shatchi_moq[productId]) {
                        productMoq = parseFloat($widget.options.jsonConfig.shatchi_moq[productId]);
                    }
                }

                console.log('--- Shatchi Variant Debug (Increment) ---');
                console.log('Product ID:', productId);
                console.log('Old Value:', old);
                console.log('Resolved MOQ:', productMoq);

                var newValue;
                if (old === 0) {
                    newValue = productMoq; // Jump straight to Shatchi MOQ
                } else {
                    newValue = old + 1; // Then increment by 1
                }

                console.log('New Value applied:', newValue);
                console.log('-----------------------------------------');

                input.val(newValue);

                // This will trigger _KeyupChange which will recalculate with correct pricing
                $widget._KeyupChange(input, $widget, true);
            },

            // Override decrement to drop to 0 if we hit Shatchi MOQ
            _OnDecClick: function ($this, $widget) {
                var isEnabled = window.shatchiVariantGridEnabled || false;
                if (!isEnabled) {
                    return this._super($this, $widget);
                }

                var input = $this.parent().parent().find('.mpcpgv-input'),
                    old = Number(input.val()),
                    productId = input.attr('product-id');

                var productMoq = 1;
                if ($widget.options.jsonConfig) {
                    if ($widget.options.jsonConfig.moq && $widget.options.jsonConfig.moq[productId]) {
                        productMoq = parseFloat($widget.options.jsonConfig.moq[productId]);
                    } else if ($widget.options.jsonConfig.shatchi_moq && $widget.options.jsonConfig.shatchi_moq[productId]) {
                        productMoq = parseFloat($widget.options.jsonConfig.shatchi_moq[productId]);
                    }
                }

                console.log('--- Shatchi Variant Debug (Decrement) ---');
                console.log('Product ID:', productId);
                console.log('Old Value:', old);
                console.log('Resolved MOQ:', productMoq);

                var newValue;
                if (old <= productMoq) {
                    newValue = 0; // Set to 0 when below or equal to MOQ
                } else {
                    newValue = old - 1; // Decrement by 1
                }

                console.log('New Value applied:', newValue);
                console.log('-----------------------------------------');

                input.val(newValue);

                // This will trigger _KeyupChange which will recalculate with correct pricing
                $widget._KeyupChange(input, $widget, false);
            },

            // Enforce constraints on manual input typing
            _KeyupChange: function ($this, $widget, status) {
                var isEnabled = window.shatchiVariantGridEnabled || false;

                var qty = Number($this.val()),
                    productId = $this.attr('product-id');

                if (isEnabled && productId) {
                    var productMoq = 1;
                    if ($widget.options.jsonConfig) {
                        if ($widget.options.jsonConfig.moq && $widget.options.jsonConfig.moq[productId]) {
                            productMoq = parseFloat($widget.options.jsonConfig.moq[productId]);
                        } else if ($widget.options.jsonConfig.shatchi_moq && $widget.options.jsonConfig.shatchi_moq[productId]) {
                            productMoq = parseFloat($widget.options.jsonConfig.shatchi_moq[productId]);
                        }
                    }

                    console.log('--- Shatchi Variant Debug (Keyup/Typing) ---');
                    console.log('Product ID:', productId);
                    console.log('Typed Value:', qty);
                    console.log('Resolved MOQ:', productMoq);

                    // Enforce constraints: minimum selectable qty is strictly productMoq
                    if (qty > 0 && qty < productMoq) {
                        qty = 0;
                        $this.val(qty);
                    }

                    // Get the current price (either MOQ or Carton price based on qty) for calculation
                    var currentUnitPrice = $widget._GetMpPrice($widget, productId, qty);

                    // Calculate subtotal
                    var newSubtotal = qty > 0 ? currentUnitPrice * qty : 0;

                    // Update the subtotal in the row
                    var $row = $this.closest('tr');
                    var $subtotalCell = $row.find('.mpcpgv-subtotal');

                    if ($subtotalCell.length) {
                        // Clear existing content and set new subtotal
                        $subtotalCell.html($widget.getFormattedPrice(newSubtotal));
                    }

                    console.log('Subtotal calculated:', newSubtotal, '(${currentUnitPrice} × ${qty})');
                    console.log('-----------------------------------------');
                }

                // Call the original method for any other Mageplaza functionality
                return this._super($this, $widget, status);
            },


            // Override price calculation to apply Carton Price if Qty >= Carton Qty
            _GetMpPrice: function ($widget, productId, qty) {
                var isEnabled = window.shatchiVariantGridEnabled || false;

                // Get the MOQ price (individual price) - this is the base price
                var moqPricePerUnit = 0;
                if ($widget.options.jsonConfig && $widget.options.jsonConfig.optionPrices && $widget.options.jsonConfig.optionPrices[productId]) {
                    moqPricePerUnit = $widget.options.jsonConfig.optionPrices[productId].finalPrice.amount;
                }

                // Initialize with MOQ price as default
                var finalPrice = moqPricePerUnit;

                if (isEnabled && typeof qty !== 'undefined' && qty > 0) {
                    var cartonQty = 0;
                    var cartonPricePerUnit = 0;

                    // Get Carton Qty and Price
                    if ($widget.options.jsonConfig && $widget.options.jsonConfig.shatchi_carton_qty && $widget.options.jsonConfig.shatchi_carton_qty[productId]) {
                        cartonQty = parseFloat($widget.options.jsonConfig.shatchi_carton_qty[productId]);
                    }

                    if ($widget.options.jsonConfig && $widget.options.jsonConfig.shatchi_carton_price && $widget.options.jsonConfig.shatchi_carton_price[productId]) {
                        cartonPricePerUnit = parseFloat($widget.options.jsonConfig.shatchi_carton_price[productId]);
                    }

                    console.log('--- Shatchi Variant Debug (Carton Pricing) ---');
                    console.log('Product ID:', productId);
                    console.log('Current Input Qty:', qty);
                    console.log('MOQ Price per unit (Base):', moqPricePerUnit);
                    console.log('Carton Qty:', cartonQty);
                    console.log('Carton Price per unit:', cartonPricePerUnit);

                    // Apply carton price logic for CALCULATION only
                    if (cartonQty > 0 && cartonPricePerUnit > 0) {
                        if (qty >= cartonQty) {
                            // When quantity reaches or exceeds carton quantity, use carton price for calculation
                            finalPrice = cartonPricePerUnit;
                            console.log('✅ Using CARTON price for calculation:', finalPrice);
                        } else {
                            // Below carton quantity, use MOQ price for calculation
                            finalPrice = moqPricePerUnit;
                            console.log('📦 Using MOQ price for calculation:', finalPrice);
                        }
                    } else {
                        // If no carton pricing configured, use MOQ price
                        finalPrice = moqPricePerUnit;
                        console.log('⚠️ No carton pricing configured, using MOQ price:', finalPrice);
                    }

                    console.log('Final Price for Calculation:', finalPrice);
                    console.log('----------------------------------------------');
                }

                return finalPrice;
            },

            // Override _ChangeDetail to inject the Base Unit Price into the Total Summary table
            _ChangeDetail: function ($this, $widget, status) {
                // Call original method which rebuilds the summary table rows
                this._super($this, $widget, status);

                var isShowBasePrice = window.shatchiVariantShowBasePrice || false;

                if (isShowBasePrice) {
                    var input = $this.parent().parent().find('.mpcpgv-input'),
                        productId = input.attr('product-id'),
                        old = Number(input.val()),
                        summaryTable = $widget.formContainer.find('#mpcpgv-simple-product');

                    // If qty > 0, the row is present in the summary table
                    if (old >= 1) {
                        // Check if the header for Unit Price exists, if not add it
                        var theadTr = summaryTable.find('thead tr');
                        if (theadTr.find('.shatchi-summary-price-header').length === 0) {
                            // Insert before the 'Qty' header or at the end
                            // In Mageplaza, the last column is typically the delete button, so we insert before Qty
                            // Wait, the thead in summary is: SKU, Attribute, Qty, empty(for delete)
                            var qtyHeaderIndex = theadTr.find('td').filter(function () {
                                return $(this).text().trim() === $t('Qty');
                            }).index();

                            if (qtyHeaderIndex > -1) {
                                $('<td class="shatchi-summary-price-header">' + $t('Unit Price') + '<\/td>').insertBefore(theadTr.find('td').eq(qtyHeaderIndex));
                            } else {
                                $('<td class="shatchi-summary-price-header">' + $t('Unit Price') + '<\/td>').insertBefore(theadTr.find('td:last'));
                            }
                        }

                        // Now find the row for this specific product in the summary
                        var row = summaryTable.find('.mpcpgv-simple[product-id="' + productId + '"]');
                        if (row.length > 0 && row.find('.shatchi-summary-unit-price').length === 0) {
                            // Get the current calculated unit price for this product
                            var currentPrice = $widget._GetMpPrice($widget, productId, old);
                            var formattedPrice = $widget.getFormattedPrice(currentPrice);

                            // Insert the price cell before the Qty cell (which is class mpcpgv-qty)
                            $('<td class="shatchi-summary-unit-price">' + formattedPrice + '<\/td>').insertBefore(row.find('.mpcpgv-qty'));
                        } else if (row.length > 0) {
                            // If the cell already exists, just update the price
                            var currentPrice = $widget._GetMpPrice($widget, productId, old);
                            row.find('.shatchi-summary-unit-price').text($widget.getFormattedPrice(currentPrice));
                        }
                    }
                }
            }
        });

        return $.mageplaza_configurable.SwatchRenderer;
    };
});