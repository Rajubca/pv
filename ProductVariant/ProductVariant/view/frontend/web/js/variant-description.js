require(['jquery'], function ($) {
    'use strict';

    $(document).on('shatchi:variant:selected', function (e, productId, widget) {

        if (!productId || !widget || !widget.options.jsonConfig) return;

        var config = widget.options.jsonConfig;

        // Cache DOM elements
        var $desc = $('.variant-description-content');
        var $care = $('.variant-care-instructions-content');
        var $tab1Table = $('#variant-tech-spec-table-tab1');
        var $tab1Container = $tab1Table.closest('.variant-technical-specifications');

        // -------------------------------
        // 1. Description
        // -------------------------------
        if (config.description_tab && config.description_tab[productId]) {
            $desc.html(config.description_tab[productId]);
        } else if (config.descriptions && config.descriptions[productId]) {
            $desc.html(config.descriptions[productId]);
        } else {
            $desc.html(''); // ✅ Clear if empty
        }

        // -------------------------------
        // 2. Care Instructions
        // -------------------------------
        if (config.care_instruction_tab && config.care_instruction_tab[productId]) {
            $care.html(config.care_instruction_tab[productId]);
        } else {
            $care.html(''); // ✅ Clear if empty
        }

        // -------------------------------
        // 3. Technical Specs (Tab 1)
        // -------------------------------
        if (config.technical_specs_tab1 && config.technical_specs_tab1[productId]) {
            $tab1Table.html(config.technical_specs_tab1[productId]);
            $tab1Container.show();
        } else if (config.technical_specs_tab && config.technical_specs_tab[productId]) {
            // fallback support
            $tab1Table.html(config.technical_specs_tab[productId]);
            $tab1Container.show();
        } else {
            $tab1Table.html('');   // ✅ Clear old data
            $tab1Container.hide(); // ✅ Hide if empty
        }

    });
});