/**
 * BannerCalc Admin Scripts
 *
 * @package BannerCalc
 */
(function($) {
    'use strict';

    var units = { mm: 'Millimetres (mm)', cm: 'Centimetres (cm)', inch: 'Inches (in)', ft: 'Feet (ft)', m: 'Metres (m)' };

    function buildUnitOptions(selected) {
        var html = '';
        for (var key in units) {
            var sel = (key === (selected || 'ft')) ? ' selected' : '';
            html += '<option value="' + key + '"' + sel + '>' + units[key] + '</option>';
        }
        return html;
    }

    function buildStarOptions(selected) {
        selected = parseInt(selected, 10) || 3;
        var html = '';
        for (var s = 5; s >= 1; s--) {
            var sel = (s === selected) ? ' selected' : '';
            html += '<option value="' + s + '"' + sel + '>' + s + '★</option>';
        }
        return html;
    }

    function buildPresetRow(data) {
        data = data || {};
        return '<tr class="bannercalc-preset-row">'
            + '<td style="padding:4px 4px;"><input type="text" name="bannercalc_category[preset_label][]" value="' + (data.label || '') + '" placeholder="e.g. 6ft × 3ft" style="width:100%;" /></td>'
            + '<td style="padding:4px 4px;"><input type="number" name="bannercalc_category[preset_width][]" value="' + (data.width || '') + '" step="any" min="0" style="width:70px;" /></td>'
            + '<td style="padding:4px 4px;"><input type="number" name="bannercalc_category[preset_height][]" value="' + (data.height || '') + '" step="any" min="0" style="width:70px;" /></td>'
            + '<td style="padding:4px 4px;"><select name="bannercalc_category[preset_unit][]" style="width:80px;">' + buildUnitOptions(data.unit) + '</select></td>'
            + '<td style="padding:4px 4px;"><input type="text" name="bannercalc_category[preset_desc][]" value="' + (data.description || '') + '" placeholder="e.g. Trade shows, retail" style="width:100%;" /></td>'
            + '<td style="padding:4px 4px;text-align:center;"><select name="bannercalc_category[preset_popularity][]" style="width:50px;">' + buildStarOptions(data.popularity) + '</select></td>'
            + '<td style="padding:4px 4px;"><input type="number" name="bannercalc_category[preset_price][]" value="' + (data.price || '') + '" step="0.01" min="0" style="width:80px;" placeholder="Auto" /></td>'
            + '<td style="padding:4px 4px;text-align:center;"><button type="button" class="bannercalc-remove-preset" title="Remove" style="background:none;border:none;color:#ED1C24;cursor:pointer;font-size:18px;">&times;</button></td>'
            + '</tr>';
    }

    const BannerCalcAdmin = {
        init: function() {
            this.bindEvents();
            this.initQuantityToggle();
        },

        /**
         * Show/hide bundle fields based on quantity mode.
         */
        initQuantityToggle: function() {
            var $select = $('#bannercalc-quantity-mode');
            if (!$select.length) return;

            function toggle() {
                var mode = $select.val();
                $('#bannercalc-bundles-row').toggle(mode === 'bundles');
            }

            $select.on('change', toggle);
            toggle();
        },

        bindEvents: function() {
            // Toggle override fields in product metabox.
            $(document).on('change', '#bannercalc-override-toggle', function() {
                $('#bannercalc-override-fields').toggle(this.checked);
            });

            // Preset sizes repeater — add row.
            $(document).on('click', '#bannercalc-add-preset', function() {
                $('#bannercalc-presets-body').append(buildPresetRow());
            });

            // Preset sizes repeater — remove row.
            $(document).on('click', '.bannercalc-remove-preset', function() {
                $(this).closest('tr').remove();
            });

            // Service type — add row.
            $(document).on('click', '#bannercalc-add-service-type', function() {
                var $table = $('#bannercalc-service-types-table');
                var idx = $table.find('.bannercalc-service-type-row').length;
                var row = '<tr class="bannercalc-service-type-row">' +
                    '<th scope="row" style="width:50px;"><label><input type="radio" name="bannercalc_settings[service_types_default]" value="" /> Default</label></th>' +
                    '<td><input type="hidden" class="bannercalc-st-slug" name="bannercalc_settings[service_types][' + idx + '][slug]" value="" />' +
                    '<div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">' +
                    '<label style="font-size:12px;">Label: <input type="text" name="bannercalc_settings[service_types][' + idx + '][label]" value="" class="regular-text bannercalc-st-label" style="width:220px;" /></label>' +
                    '<label style="font-size:12px;">Markup %: <input type="number" name="bannercalc_settings[service_types][' + idx + '][markup]" value="0" step="0.1" min="0" max="100" class="small-text" style="width:70px;" /></label>' +
                    '<button type="button" class="button-link bannercalc-remove-service-type" style="color:#b32d2e;font-size:12px;" title="Remove">✕</button>' +
                    '</div></td></tr>';
                $table.append(row);
            });

            // Service type — remove row.
            $(document).on('click', '.bannercalc-remove-service-type', function() {
                if (!confirm('Remove this service type?')) return;
                $(this).closest('tr').remove();
                // Re-index remaining rows.
                $('#bannercalc-service-types-table .bannercalc-service-type-row').each(function(i) {
                    $(this).find('[name]').each(function() {
                        var name = $(this).attr('name');
                        $(this).attr('name', name.replace(/service_types\[\d+\]/, 'service_types[' + i + ']'));
                    });
                });
            });

            // Service type — auto-generate slug from label.
            $(document).on('blur', '.bannercalc-st-label', function() {
                var $row = $(this).closest('tr');
                var $slug = $row.find('.bannercalc-st-slug');
                var $radio = $row.find('input[type="radio"]');
                var label = $(this).val().trim();
                if (label && !$slug.val()) {
                    var slug = label.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
                    $slug.val(slug);
                    $radio.val(slug);
                }
            });

            // Import Popular Sizes from reference data.
            $(document).on('click', '#bannercalc-import-presets', function() {
                var $btn    = $(this);
                var catId   = $btn.data('cat-id');
                var $status = $('#bannercalc-import-status');

                if (!catId) {
                    $status.text('No category selected.').css('color', '#ED1C24');
                    return;
                }

                var existing = $('#bannercalc-presets-body tr').length;
                if (existing > 0 && !confirm('This will ADD imported sizes to the existing ' + existing + ' row(s). Continue?')) {
                    return;
                }

                $btn.prop('disabled', true);
                $status.text('Loading…').css('color', '#8892A0');

                $.post(bannercalcAdmin.ajaxUrl, {
                    action: 'bannercalc_get_seed_data',
                    nonce:  bannercalcAdmin.nonce,
                    cat_id: catId
                }, function(response) {
                    $btn.prop('disabled', false);

                    if (!response.success) {
                        $status.text(response.data || 'No reference data found.').css('color', '#ED1C24');
                        return;
                    }

                    var sizes = response.data.sizes || [];
                    var count = 0;
                    sizes.forEach(function(s) {
                        $('#bannercalc-presets-body').append(buildPresetRow(s));
                        count++;
                    });

                    $status.text('✓ Imported ' + count + ' sizes for "' + response.data.category + '"').css('color', '#39B54A');
                }).fail(function() {
                    $btn.prop('disabled', false);
                    $status.text('Request failed.').css('color', '#ED1C24');
                });
            });
        }
    };

    $(document).ready(function() {
        BannerCalcAdmin.init();
    });
})(jQuery);
