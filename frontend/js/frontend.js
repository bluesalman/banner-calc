/**
 * BannerCalc Frontend Scripts
 *
 * Handles unit conversion, live price calculation, and form validation.
 *
 * @package BannerCalc
 */
(function($) {
    'use strict';

    /**
     * Unit conversion constants.
     */
    const TO_METRES = {
        mm:   0.001,
        cm:   0.01,
        inch: 0.0254,
        ft:   0.3048,
        m:    1
    };

    const SQFT_PER_SQM = 10.7639;

    const UNIT_ABBR = {
        mm: 'mm',
        cm: 'cm',
        inch: 'in',
        ft: 'ft',
        m: 'm'
    };

    /**
     * Main BannerCalc state and controller.
     */
    const BannerCalc = {
        config: null,
        el: null,

        state: {
            sizingMode: 'preset',
            selectedPreset: null,
            selectedUnit: 'ft',
            widthRaw: null,
            heightRaw: null,
            widthMetres: null,
            heightMetres: null,
            areaSqft: null,
            areaSqm: null,
            selectedAttributes: {},
            basePrice: 0,
            addonsTotal: 0,
            calculatedPrice: 0,
            isValid: false,
            validationErrors: []
        },

        /**
         * Initialise the configurator.
         */
        init: function() {
            this.el = $('#bannercalc-configurator');
            if (!this.el.length) return;

            try {
                this.config = JSON.parse(this.el.attr('data-config'));
            } catch(e) {
                console.error('BannerCalc: Failed to parse config', e);
                return;
            }

            this.state.selectedUnit = this.config.defaultUnit || 'ft';
            this.state.sizingMode = (this.config.sizingMode === 'custom_only') ? 'custom' : 'preset';

            // Set default attribute values.
            this.initDefaults();

            // Bind events.
            this.bindEvents();

            // Initial render.
            this.updateConstraintsDisplay();
            this.updatePresetPrices();
            this.updatePriceDisplay();
        },

        /**
         * Set default attribute selections from config.
         */
        initDefaults: function() {
            var pricing = this.config.attributePricing || {};
            for (var attr in pricing) {
                if (pricing.hasOwnProperty(attr) && pricing[attr].default_value) {
                    this.state.selectedAttributes[attr] = pricing[attr].default_value;
                }
            }
        },

        /**
         * Bind all UI event listeners.
         */
        bindEvents: function() {
            var self = this;

            // Size mode toggle.
            this.el.on('click', '.bannercalc-toggle-btn', function() {
                var mode = $(this).data('mode');
                self.el.find('.bannercalc-toggle-btn').removeClass('active');
                $(this).addClass('active');

                if (mode === 'preset') {
                    $('#bannercalc-presets').show();
                    $('#bannercalc-custom').hide();
                    self.state.sizingMode = 'preset';
                } else {
                    $('#bannercalc-presets').hide();
                    $('#bannercalc-custom').show();
                    self.state.sizingMode = 'custom';
                    self.state.selectedPreset = null;
                }

                self.updateHiddenFields();
                self.updatePriceDisplay();
            });

            // Preset size selection.
            this.el.on('click', '.bannercalc-preset-card', function() {
                self.el.find('.bannercalc-preset-card').removeClass('active');
                $(this).addClass('active');

                self.state.selectedPreset = $(this).data('slug');
                self.state.widthMetres = parseFloat($(this).data('width-m'));
                self.state.heightMetres = parseFloat($(this).data('height-m'));
                self.state.sizingMode = 'preset';

                var priceOverride = $(this).data('price');
                // Store for calculation.
                self.state._presetPriceOverride = (priceOverride !== '' && priceOverride !== undefined) ? parseFloat(priceOverride) : null;

                self.calculateArea();
                self.calculatePrice();
                self.updateHiddenFields();
                self.updatePriceDisplay();
            });

            // Unit selector.
            this.el.on('click', '.bannercalc-unit-btn', function() {
                self.el.find('.bannercalc-unit-btn').removeClass('active');
                $(this).addClass('active');

                var newUnit = $(this).data('unit');
                self.changeUnit(newUnit);
            });

            // Dimension inputs (debounced).
            var dimTimeout = null;
            this.el.on('input', '.bannercalc-dim-input', function() {
                clearTimeout(dimTimeout);
                dimTimeout = setTimeout(function() {
                    self.readCustomDimensions();
                    self.calculateArea();
                    self.validateDimensions();
                    self.calculatePrice();
                    self.updateHiddenFields();
                    self.updatePriceDisplay();
                }, 300);
            });

            // Attribute buttons.
            this.el.on('click', '.bannercalc-attr-btn', function() {
                var taxonomy = $(this).data('taxonomy');
                var slug = $(this).data('slug');

                self.el.find('.bannercalc-attr-btn[data-taxonomy="' + taxonomy + '"]').removeClass('active');
                $(this).addClass('active');

                self.state.selectedAttributes[taxonomy] = slug;

                // Update hidden input.
                self.el.find('.bannercalc-attr-input[data-taxonomy="' + taxonomy + '"]').val(slug);

                self.calculatePrice();
                self.updatePriceDisplay();
            });
        },

        /**
         * Change the selected unit, converting existing values.
         */
        changeUnit: function(newUnit) {
            var oldUnit = this.state.selectedUnit;
            this.state.selectedUnit = newUnit;

            // Update unit labels.
            var abbr = UNIT_ABBR[newUnit] || newUnit;
            this.el.find('.bannercalc-unit-label').text(abbr);

            // Convert existing custom dimension values (keep physical size the same).
            if (this.state.widthRaw !== null && this.state.widthMetres !== null) {
                var newWidth = this.state.widthMetres / TO_METRES[newUnit];
                var newHeight = this.state.heightMetres / TO_METRES[newUnit];
                this.state.widthRaw = parseFloat(newWidth.toFixed(4));
                this.state.heightRaw = parseFloat(newHeight.toFixed(4));
                $('#bannercalc-width').val(this.state.widthRaw);
                $('#bannercalc-height').val(this.state.heightRaw);
            }

            this.updateConstraintsDisplay();
            this.updatePresetPrices();
            this.updateHiddenFields();
        },

        /**
         * Read custom dimension values from inputs.
         */
        readCustomDimensions: function() {
            var w = parseFloat($('#bannercalc-width').val());
            var h = parseFloat($('#bannercalc-height').val());

            this.state.widthRaw = isNaN(w) ? null : w;
            this.state.heightRaw = isNaN(h) ? null : h;

            if (this.state.widthRaw !== null) {
                this.state.widthMetres = this.state.widthRaw * TO_METRES[this.state.selectedUnit];
            } else {
                this.state.widthMetres = null;
            }
            if (this.state.heightRaw !== null) {
                this.state.heightMetres = this.state.heightRaw * TO_METRES[this.state.selectedUnit];
            } else {
                this.state.heightMetres = null;
            }
        },

        /**
         * Calculate area from current dimensions.
         */
        calculateArea: function() {
            if (this.state.widthMetres && this.state.heightMetres) {
                this.state.areaSqm = this.state.widthMetres * this.state.heightMetres;
                this.state.areaSqft = this.state.areaSqm * SQFT_PER_SQM;

                $('#bannercalc-area').html(
                    'Area: ' + this.state.areaSqft.toFixed(2) + ' sqft (' + this.state.areaSqm.toFixed(2) + ' sqm)'
                );
            } else {
                this.state.areaSqft = null;
                this.state.areaSqm = null;
                $('#bannercalc-area').html('');
            }
        },

        /**
         * Validate custom dimensions against min/max.
         */
        validateDimensions: function() {
            this.state.validationErrors = [];

            if (this.state.widthMetres === null || this.state.heightMetres === null) {
                return;
            }

            var c = this.config;

            if (c.minWidthM && this.state.widthMetres < c.minWidthM) {
                this.state.validationErrors.push('Width below minimum');
            }
            if (c.minHeightM && this.state.heightMetres < c.minHeightM) {
                this.state.validationErrors.push('Height below minimum');
            }
            if (c.maxWidthM && this.state.widthMetres > c.maxWidthM) {
                this.state.validationErrors.push('Width exceeds maximum');
            }
            if (c.maxHeightM && this.state.heightMetres > c.maxHeightM) {
                this.state.validationErrors.push('Height exceeds maximum');
            }

            var $constraints = $('#bannercalc-constraints');
            if (this.state.validationErrors.length > 0) {
                $constraints.addClass('error').html('⚠ ' + this.state.validationErrors.join(' | '));
                this.el.find('.bannercalc-input-group').addClass('error');
            } else {
                $constraints.removeClass('error');
                this.el.find('.bannercalc-input-group').removeClass('error');
            }
        },

        /**
         * Calculate total price.
         */
        calculatePrice: function() {
            if (!this.state.areaSqft) {
                this.state.basePrice = 0;
                this.state.addonsTotal = 0;
                this.state.calculatedPrice = 0;
                this.state.isValid = false;
                return;
            }

            var rate = this.config.areaRateSqft || 0;
            var basePrice = this.state.areaSqft * rate;

            // Minimum charge.
            var minCharge = this.config.minimumCharge || 0;
            if (basePrice < minCharge) {
                basePrice = minCharge;
            }

            // Preset price override.
            if (this.state.sizingMode === 'preset' && this.state._presetPriceOverride !== null && this.state._presetPriceOverride !== undefined) {
                basePrice = this.state._presetPriceOverride;
            }

            // Add-ons.
            var addonsTotal = 0;
            var pricing = this.config.attributePricing || {};

            for (var attr in this.state.selectedAttributes) {
                if (!this.state.selectedAttributes.hasOwnProperty(attr)) continue;
                if (!pricing[attr]) continue;

                var attrConfig = pricing[attr];
                var termSlug = this.state.selectedAttributes[attr];
                var values = attrConfig.values || {};
                var modifier = parseFloat(values[termSlug] || 0);
                var pType = attrConfig.pricing_type || 'fixed';

                if (pType === 'per_sqft') {
                    addonsTotal += modifier * this.state.areaSqft;
                } else if (pType === 'percentage') {
                    addonsTotal += basePrice * (modifier / 100);
                } else {
                    addonsTotal += modifier;
                }
            }

            this.state.basePrice = parseFloat(basePrice.toFixed(2));
            this.state.addonsTotal = parseFloat(addonsTotal.toFixed(2));
            this.state.calculatedPrice = parseFloat((basePrice + addonsTotal).toFixed(2));
            this.state.isValid = this.state.validationErrors.length === 0;
        },

        /**
         * Update the price display block.
         */
        updatePriceDisplay: function() {
            var cur = this.config.currency || '£';
            var dec = this.config.decimals || 2;

            if (!this.state.areaSqft || this.state.calculatedPrice <= 0) {
                $('#bannercalc-price-placeholder').show();
                $('#bannercalc-price-details').hide();
                return;
            }

            $('#bannercalc-price-placeholder').hide();
            $('#bannercalc-price-details').show();

            // Base price label.
            var rate = this.config.areaRateSqft || 0;
            var baseLabel = this.state.areaSqft.toFixed(2) + ' sqft × ' + cur + rate.toFixed(2) + ' = ' + cur + this.state.basePrice.toFixed(dec);
            $('#bannercalc-base-label').text(baseLabel);
            $('#bannercalc-base-value').text(cur + this.state.basePrice.toFixed(dec));

            // Add-ons.
            if (this.state.addonsTotal > 0) {
                $('#bannercalc-addons-row').show();
                $('#bannercalc-addons-value').text(cur + this.state.addonsTotal.toFixed(dec));
            } else {
                $('#bannercalc-addons-row').hide();
            }

            // Total.
            $('#bannercalc-total-value').text(cur + this.state.calculatedPrice.toFixed(dec));

            // Update hidden input.
            $('#bannercalc-input-price').val(this.state.calculatedPrice);
        },

        /**
         * Update min/max constraints display in selected unit.
         */
        updateConstraintsDisplay: function() {
            var c = this.config;
            var unit = this.state.selectedUnit;
            var factor = TO_METRES[unit] || 1;
            var abbr = UNIT_ABBR[unit] || unit;

            if (!c.minWidthM && !c.maxWidthM) {
                $('#bannercalc-constraints').html('');
                return;
            }

            var minW = c.minWidthM ? (c.minWidthM / factor).toFixed(2) : '—';
            var minH = c.minHeightM ? (c.minHeightM / factor).toFixed(2) : '—';
            var maxW = c.maxWidthM ? (c.maxWidthM / factor).toFixed(2) : '—';
            var maxH = c.maxHeightM ? (c.maxHeightM / factor).toFixed(2) : '—';

            $('#bannercalc-constraints').html(
                'Min: ' + minW + abbr + ' × ' + minH + abbr +
                ' | Max: ' + maxW + abbr + ' × ' + maxH + abbr
            );
        },

        /**
         * Update preset card prices (recalculate when unit changes).
         */
        updatePresetPrices: function() {
            var self = this;
            var cur = this.config.currency || '£';
            var rate = this.config.areaRateSqft || 0;
            var dec = this.config.decimals || 2;
            var minCharge = this.config.minimumCharge || 0;

            this.el.find('.bannercalc-preset-card').each(function() {
                var wm = parseFloat($(this).data('width-m'));
                var hm = parseFloat($(this).data('height-m'));
                var overridePrice = $(this).data('price');

                var price;
                if (overridePrice !== '' && overridePrice !== undefined && overridePrice !== null) {
                    price = parseFloat(overridePrice);
                } else {
                    var sqft = (wm * hm) * SQFT_PER_SQM;
                    price = sqft * rate;
                    if (price < minCharge) price = minCharge;
                }

                $(this).find('.bannercalc-preset-price').text(cur + price.toFixed(dec));
            });
        },

        /**
         * Update hidden form fields for submission.
         */
        updateHiddenFields: function() {
            $('#bannercalc-input-sizing-mode').val(this.state.sizingMode);
            $('#bannercalc-input-preset-slug').val(this.state.selectedPreset || '');
            $('#bannercalc-input-unit').val(this.state.selectedUnit);
            $('#bannercalc-input-width').val(this.state.widthRaw || '');
            $('#bannercalc-input-height').val(this.state.heightRaw || '');
            $('#bannercalc-input-price').val(this.state.calculatedPrice || '');
        }
    };

    // Initialise on DOM ready.
    $(document).ready(function() {
        BannerCalc.init();
    });

})(jQuery);
