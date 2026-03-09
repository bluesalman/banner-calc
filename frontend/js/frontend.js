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
            designService: false,
            designServicePrice: 0,
            serviceType: 'standard',
            serviceMarkupPct: 0,
            serviceMarkupAmt: 0,
            designMode: 'upload',
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

            // Set default service type.
            var serviceTypes = this.config.serviceTypes || [];
            for (var i = 0; i < serviceTypes.length; i++) {
                if (serviceTypes[i]['default']) {
                    this.state.serviceType = serviceTypes[i].slug;
                    break;
                }
            }

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

            // Sync dropdown selects to their default values.
            this.el.find('.bannercalc-attr-dropdown').each(function() {
                var taxonomy = $(this).data('taxonomy');
                var defaultVal = (pricing[taxonomy] && pricing[taxonomy].default_value) ? pricing[taxonomy].default_value : '';
                if (defaultVal) {
                    $(this).val(defaultVal);
                }
            });
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
                    // Clear custom dimensions.
                    self.state.widthRaw = null;
                    self.state.heightRaw = null;
                    $('#bannercalc-width').val('');
                    $('#bannercalc-height').val('');
                    // If no preset selected yet, reset area & price.
                    if (!self.state.selectedPreset) {
                        self.state.widthMetres = null;
                        self.state.heightMetres = null;
                        self.state.areaSqft = null;
                        self.state.areaSqm = null;
                    }
                } else {
                    $('#bannercalc-presets').hide();
                    $('#bannercalc-custom').show();
                    self.state.sizingMode = 'custom';
                    // Clear preset.
                    self.state.selectedPreset = null;
                    self.state._presetPriceOverride = null;
                    self.el.find('.bannercalc-preset-card').removeClass('active');
                    // Hide description callout.
                    $('#bannercalc-preset-desc').hide();
                    // Reset dimensions to empty.
                    self.state.widthMetres = null;
                    self.state.heightMetres = null;
                    self.state.areaSqft = null;
                    self.state.areaSqm = null;
                }

                self.calculateArea();
                self.calculatePrice();
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

                // Show description callout if available.
                var desc = $(this).data('description') || '';
                var $descWrap = $('#bannercalc-preset-desc');
                if (desc) {
                    $('#bannercalc-desc-text').text(desc);
                    $descWrap.slideDown(150);
                } else {
                    $descWrap.slideUp(150);
                }

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

            // Attribute pill buttons (new class: .bannercalc-pill).
            this.el.on('click', '.bannercalc-pill', function() {
                var taxonomy = $(this).data('taxonomy');
                var slug = $(this).data('slug');

                self.el.find('.bannercalc-pill[data-taxonomy="' + taxonomy + '"]').removeClass('active');
                $(this).addClass('active');

                self.state.selectedAttributes[taxonomy] = slug;

                // Update hidden input.
                self.el.find('.bannercalc-attr-input[data-taxonomy="' + taxonomy + '"]').val(slug);

                // Update header selection text.
                $('#bannercalc-selected-' + taxonomy).text($(this).text().split('+')[0].trim());

                self.calculatePrice();
                self.updatePriceDisplay();
            });

            // Attribute dropdown selects.
            this.el.on('change', '.bannercalc-attr-dropdown', function() {
                var taxonomy = $(this).data('taxonomy');
                var slug = $(this).val();

                self.state.selectedAttributes[taxonomy] = slug;

                // Update hidden input.
                self.el.find('.bannercalc-attr-input[data-taxonomy="' + taxonomy + '"]').val(slug);

                self.calculatePrice();
                self.updatePriceDisplay();
            });

            // Toggle switch (yes/no attributes like cable ties).
            this.el.on('change', '.bannercalc-toggle-input', function() {
                var $cb = $(this);
                var taxonomy = $cb.data('taxonomy');
                var isOn = $cb.is(':checked');
                var slug = isOn ? $cb.data('on-slug') : $cb.data('off-slug');

                self.state.selectedAttributes[taxonomy] = slug;

                // Update hidden input.
                self.el.find('.bannercalc-attr-input[data-taxonomy="' + taxonomy + '"]').val(slug);

                // Update label text and style.
                var $wrap = $cb.closest('.bannercalc-toggle-switch');
                var $label = $wrap.find('.bannercalc-switch-label');
                if (isOn) {
                    $label.text($cb.data('on-slug').replace(/-/g, ' ').replace(/\b\w/g, function(c){return c.toUpperCase();}));
                    $label.addClass('is-on');
                } else {
                    $label.text($cb.data('off-slug').replace(/-/g, ' ').replace(/\b\w/g, function(c){return c.toUpperCase();}));
                    $label.removeClass('is-on');
                }

                self.calculatePrice();
                self.updatePriceDisplay();
            });

            // Service type pills (outside #bannercalc-configurator — use form scope).
            this.el.closest('form').on('click', '.bannercalc-service-pill', function() {
                $('.bannercalc-service-pill').removeClass('active');
                $(this).addClass('active');
                self.state.serviceType = $(this).data('service');
                $('#bannercalc-input-service-type').val(self.state.serviceType);
                self.calculatePrice();
                self.updatePriceDisplay();
            });

            // Design mode pills (3-way selector).
            this.el.closest('form').on('click', '.bannercalc-design-pill', function() {
                $('.bannercalc-design-pill').removeClass('active');
                $(this).addClass('active');
                var mode = $(this).data('design-mode');
                self.state.designMode = mode;
                $('#bannercalc-input-design-mode').val(mode);

                // Show/hide conditional panels.
                $('#bannercalc-design-upload').toggle(mode === 'upload');
                $('#bannercalc-design-online').toggle(mode === 'online');
                $('#bannercalc-design-pro').toggle(mode === 'pro');

                // Auto-enable/disable design service based on mode.
                self.state.designService = (mode === 'pro');
                $('#bannercalc-input-design-service').val(mode === 'pro' ? '1' : '0');

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
                    'Area: <strong>' + this.state.areaSqft.toFixed(2) + ' sqft</strong> (' + this.state.areaSqm.toFixed(4) + ' sqm)'
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

            // Design service.
            this.state.designServicePrice = 0;
            if (this.state.designService) {
                var dsConfig = this.config.designService || {};
                if (dsConfig.enabled) {
                    this.state.designServicePrice = parseFloat(dsConfig.price || 0);
                    addonsTotal += this.state.designServicePrice;
                }
            }

            // Service type markup.
            this.state.serviceMarkupPct = 0;
            this.state.serviceMarkupAmt = 0;
            if (this.state.serviceType && this.state.serviceType !== 'standard') {
                var serviceTypes = this.config.serviceTypes || [];
                for (var s = 0; s < serviceTypes.length; s++) {
                    if (serviceTypes[s].slug === this.state.serviceType) {
                        this.state.serviceMarkupPct = parseFloat(serviceTypes[s].markup || 0);
                        break;
                    }
                }
                if (this.state.serviceMarkupPct > 0) {
                    this.state.serviceMarkupAmt = parseFloat(((basePrice + addonsTotal) * (this.state.serviceMarkupPct / 100)).toFixed(2));
                }
            }

            this.state.calculatedPrice = parseFloat((basePrice + addonsTotal + this.state.serviceMarkupAmt).toFixed(2));
            this.state.isValid = this.state.validationErrors.length === 0;

            // Update preview.
            if (BannerCalcPreview.initialized) {
                BannerCalcPreview.checkVisibility();
                BannerCalcPreview.render();
                // Auto-switch to Your Banner tab when state changes.
                BannerCalcPreview.switchToPreview();
            }
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

            // Design service row.
            if (this.state.designService && this.state.designServicePrice > 0) {
                $('#bannercalc-design-row').show();
                $('#bannercalc-design-value').text('+' + cur + this.state.designServicePrice.toFixed(dec));
            } else {
                $('#bannercalc-design-row').hide();
            }

            // Service markup row.
            if (this.state.serviceMarkupAmt > 0) {
                var serviceTypes = this.config.serviceTypes || [];
                var stLabel = 'Delivery Markup';
                for (var s = 0; s < serviceTypes.length; s++) {
                    if (serviceTypes[s].slug === this.state.serviceType) {
                        stLabel = serviceTypes[s].label + ' (+' + parseInt(serviceTypes[s].markup) + '%)';
                        break;
                    }
                }
                $('#bannercalc-service-row').show();
                $('#bannercalc-service-label').text(stLabel + ':');
                $('#bannercalc-service-value').text('+' + cur + this.state.serviceMarkupAmt.toFixed(dec));
            } else {
                $('#bannercalc-service-row').hide();
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
            $('#bannercalc-input-service-type').val(this.state.serviceType || 'standard');
            $('#bannercalc-input-design-service').val(this.state.designService ? '1' : '0');
            $('#bannercalc-input-design-mode').val(this.state.designMode || 'upload');
        }
    };

    // ============================================================
    // BANNER PREVIEW — SVG Rendering Module
    // ============================================================
    var BannerCalcPreview = {
        initialized: false,
        tabsEl: null,
        panelEl: null,
        canvasEl: null,
        galleryEl: null,

        init: function() {
            this.tabsEl   = $('#bannercalc-preview-tabs');
            this.panelEl  = $('#bannercalc-preview-panel');
            this.canvasEl = $('#bannercalc-preview-canvas');

            if (!this.tabsEl.length || !this.panelEl.length) return;

            // Find the product gallery to toggle.
            this.galleryEl = $('.woocommerce-product-gallery');

            // Relocate tabs + panel into the gallery column (before gallery).
            if (this.galleryEl.length) {
                this.tabsEl.insertBefore(this.galleryEl);
                this.panelEl.insertBefore(this.galleryEl);
            }

            this.bindEvents();
            this.initialized = true;
        },

        bindEvents: function() {
            var self = this;

            // Tab switching.
            $(document).on('click', '.bannercalc-preview-tab', function() {
                var tab = $(this).data('tab');
                $('.bannercalc-preview-tab').removeClass('active');
                $(this).addClass('active');

                if (tab === 'preview') {
                    self.panelEl.show();
                    self.galleryEl.hide();
                    self.render();
                } else {
                    self.panelEl.hide();
                    self.galleryEl.show();
                }
            });
        },

        /**
         * Check if preview tabs should be shown (any visual attribute selected).
         */
        checkVisibility: function() {
            if (!this.initialized) return;
            // Tabs are always visible by default now — no need to hide/show.
        },

        /**
         * Auto-switch to the 'Your Banner' preview tab.
         */
        switchToPreview: function() {
            if (!this.initialized) return;
            // Only switch if dimensions are available.
            if (!BannerCalc.state.widthMetres || !BannerCalc.state.heightMetres) return;

            var $activeTab = this.tabsEl.find('.bannercalc-preview-tab.active');
            if ($activeTab.data('tab') !== 'preview') {
                this.tabsEl.find('.bannercalc-preview-tab').removeClass('active');
                this.tabsEl.find('[data-tab="preview"]').addClass('active');
                this.panelEl.show();
                this.galleryEl.hide();
            }
        },

        /**
         * Update the legend active states.
         */
        updateLegend: function() {
            var attrs = BannerCalc.state.selectedAttributes;
            var noneValues = ['none', 'no', ''];

            var map = {
                'eyelets':      'pa_eyelets',
                'pole-pockets': 'pa_pole-pockets',
                'hemming':      'pa_hemming',
                'cable-ties':   'pa_cable-ties'
            };

            for (var key in map) {
                var val = attrs[map[key]] || '';
                var isActive = val && noneValues.indexOf(val) === -1;
                // Cable ties also need eyelets to be active.
                if (key === 'cable-ties') {
                    var eyVal = attrs['pa_eyelets'] || '';
                    isActive = isActive && eyVal && noneValues.indexOf(eyVal) === -1;
                }
                var $item = $('[data-legend="' + key + '"]');
                $item.toggleClass('active', isActive);
            }
        },

        /**
         * Render the SVG preview based on current state.
         */
        render: function() {
            if (!this.initialized || !this.canvasEl.length) return;

            var state = BannerCalc.state;
            var wM = state.widthMetres;
            var hM = state.heightMetres;

            if (!wM || !hM) {
                this.canvasEl.html('<p style="color:#8892A0;font-size:13px;text-align:center;">Select a size to see preview</p>');
                return;
            }

            // Convert metres to feet for display.
            var wFt = wM / 0.3048;
            var hFt = hM / 0.3048;
            var unit = state.selectedUnit || 'ft';
            var factor = TO_METRES[unit] || 0.3048;
            var abbr = UNIT_ABBR[unit] || 'ft';
            var wDisp = (wM / factor).toFixed(1);
            var hDisp = (hM / factor).toFixed(1);

            // Update size label.
            $('#bannercalc-preview-size').text(wDisp + abbr + ' × ' + hDisp + abbr);

            // Calculate SVG dimensions — scale to fit container.
            var containerW = this.canvasEl.width() - 40 || 400;
            var maxW = Math.min(containerW, 500);
            var maxH = 260;
            var scale = Math.min(maxW / wFt, maxH / hFt);
            var bw = wFt * scale;
            var bh = hFt * scale;
            var pad = 36;
            var svgW = bw + pad * 2;
            var svgH = bh + pad * 2 + 24; // extra for dimension labels

            var attrs = state.selectedAttributes;
            var noneVals = ['none', 'no', ''];

            var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + svgW + ' ' + svgH + '" width="' + svgW + '" height="' + svgH + '">';

            // Defs.
            svg += '<defs>';
            svg += '<linearGradient id="bcGloss" x1="0" y1="0" x2="1" y2="1">';
            svg += '<stop offset="0%" stop-color="white" stop-opacity="0"/>';
            svg += '<stop offset="35%" stop-color="white" stop-opacity="0"/>';
            svg += '<stop offset="50%" stop-color="white" stop-opacity="0.12"/>';
            svg += '<stop offset="65%" stop-color="white" stop-opacity="0"/>';
            svg += '<stop offset="100%" stop-color="white" stop-opacity="0"/>';
            svg += '</linearGradient>';
            svg += '<pattern id="bcPocketHash" patternUnits="userSpaceOnUse" width="6" height="6" patternTransform="rotate(45)">';
            svg += '<line x1="0" y1="0" x2="0" y2="6" stroke="rgba(249,110,180,0.25)" stroke-width="1.5"/>';
            svg += '</pattern>';
            svg += '<filter id="bcShadow" x="-5%" y="-5%" width="110%" height="115%">';
            svg += '<feDropShadow dx="0" dy="2" stdDeviation="4" flood-color="rgba(0,0,0,0.12)"/>';
            svg += '</filter>';
            svg += '</defs>';

            var bx = pad, by = pad;

            // Banner body.
            svg += '<rect x="' + bx + '" y="' + by + '" width="' + bw + '" height="' + bh + '" rx="3" fill="#f0f0f0" filter="url(#bcShadow)" stroke="#d0d0d0" stroke-width="0.5"/>';
            svg += '<rect x="' + bx + '" y="' + by + '" width="' + bw + '" height="' + bh + '" rx="3" fill="white"/>';

            // Gloss finish overlay.
            var finish = attrs['pa_finish'] || '';
            if (finish === 'gloss') {
                svg += '<rect x="' + bx + '" y="' + by + '" width="' + bw + '" height="' + bh + '" rx="3" fill="url(#bcGloss)"/>';
            }

            // Placeholder text.
            svg += '<text x="' + (bx + bw/2) + '" y="' + (by + bh/2 - 10) + '" text-anchor="middle" font-family="Outfit, sans-serif" font-size="18" font-weight="700" fill="#a0a0a0" letter-spacing="2">YOUR DESIGN</text>';
            svg += '<text x="' + (bx + bw/2) + '" y="' + (by + bh/2 + 14) + '" text-anchor="middle" font-family="IBM Plex Mono, monospace" font-size="12" fill="#b0b0b0" font-weight="500" letter-spacing="0.5">' + wDisp + abbr + ' × ' + hDisp + abbr + '</text>';

            // HEMMING.
            var hemming = attrs['pa_hemming'] || '';
            if (hemming === 'yes') {
                svg += '<rect x="' + (bx+6) + '" y="' + (by+6) + '" width="' + (bw-12) + '" height="' + (bh-12) + '" rx="2" fill="none" stroke="#F26522" stroke-width="1.2" stroke-dasharray="4 3" opacity="0.6"/>';
            } else if (hemming === 'taped-edge') {
                svg += '<rect x="' + (bx+6) + '" y="' + (by+6) + '" width="' + (bw-12) + '" height="' + (bh-12) + '" rx="2" fill="none" stroke="#F26522" stroke-width="1.2" stroke-dasharray="2 2" opacity="0.6"/>';
            }

            // POLE POCKETS.
            var pp = attrs['pa_pole-pockets'] || '';
            if (pp && noneVals.indexOf(pp) === -1) {
                // Parse pocket depth from slug — matches "3 inches", "3-inches", "4 Inches", "4\"" etc.
                var pocketInches = 3; // default
                var depthMatch = pp.match(/(\d+(?:\.\d+)?)\s*[-\s]*(?:inch|inches|")/i);
                if (!depthMatch) {
                    // Also try matching a bare leading number (e.g. slug "3-inches" → after replace: "3 inches").
                    depthMatch = pp.replace(/-/g, ' ').match(/(\d+(?:\.\d+)?)\s*(?:inch|inches|")/i);
                }
                if (depthMatch) pocketInches = parseFloat(depthMatch[1]);

                // Which sides?
                var sides = { top: false, bottom: false, left: false, right: false };
                var ppLower = pp.toLowerCase().replace(/-/g, ' ');
                if (ppLower.indexOf('top') !== -1 || ppLower.indexOf('all') !== -1) sides.top = true;
                if (ppLower.indexOf('bottom') !== -1 || ppLower.indexOf('all') !== -1) sides.bottom = true;
                if (ppLower.indexOf('left') !== -1 || ppLower.indexOf('all') !== -1) sides.left = true;
                if (ppLower.indexOf('right') !== -1 || ppLower.indexOf('all') !== -1) sides.right = true;
                // "Top & Bottom" or "top-bottom"
                if (ppLower.indexOf('top') !== -1 && ppLower.indexOf('bottom') !== -1) {
                    sides.top = true; sides.bottom = true;
                }
                // "top-only" or "Top Only"
                if (ppLower.indexOf('only') !== -1 && ppLower.indexOf('top') !== -1) {
                    sides.top = true; sides.bottom = false;
                }
                // Default: if just a depth number, assume top+bottom.
                if (!sides.top && !sides.bottom && !sides.left && !sides.right) {
                    sides.top = true; sides.bottom = true;
                }

                var bannerHInches = hFt * 12;
                var pocketPx = Math.max(12, (pocketInches / bannerHInches) * bh);
                var pLabel = pocketInches + '\u2033 pocket';

                if (sides.top) {
                    svg += '<rect x="' + bx + '" y="' + by + '" width="' + bw + '" height="' + pocketPx + '" fill="url(#bcPocketHash)" stroke="rgba(249,110,180,0.4)" stroke-width="1"/>';
                    svg += '<text x="' + (bx + bw/2) + '" y="' + (by + pocketPx/2 + 3) + '" text-anchor="middle" font-family="IBM Plex Mono, monospace" font-size="9" fill="rgba(249,110,180,0.7)" font-weight="500">' + pLabel + '</text>';
                }
                if (sides.bottom) {
                    svg += '<rect x="' + bx + '" y="' + (by + bh - pocketPx) + '" width="' + bw + '" height="' + pocketPx + '" fill="url(#bcPocketHash)" stroke="rgba(249,110,180,0.4)" stroke-width="1"/>';
                    svg += '<text x="' + (bx + bw/2) + '" y="' + (by + bh - pocketPx/2 + 3) + '" text-anchor="middle" font-family="IBM Plex Mono, monospace" font-size="9" fill="rgba(249,110,180,0.7)" font-weight="500">' + pLabel + '</text>';
                }
                if (sides.left) {
                    var lpPx = Math.max(12, (pocketInches / (wFt * 12)) * bw);
                    svg += '<rect x="' + bx + '" y="' + by + '" width="' + lpPx + '" height="' + bh + '" fill="url(#bcPocketHash)" stroke="rgba(249,110,180,0.4)" stroke-width="1"/>';
                }
                if (sides.right) {
                    var rpPx = Math.max(12, (pocketInches / (wFt * 12)) * bw);
                    svg += '<rect x="' + (bx + bw - rpPx) + '" y="' + by + '" width="' + rpPx + '" height="' + bh + '" fill="url(#bcPocketHash)" stroke="rgba(249,110,180,0.4)" stroke-width="1"/>';
                }
            }

            // EYELETS.
            var eyeletPositions = [];
            var eyelets = attrs['pa_eyelets'] || '';
            if (eyelets && noneVals.indexOf(eyelets) === -1) {
                var r = 5, inset = 12;

                var addEyelet = function(cx, cy) {
                    eyeletPositions.push({ cx: cx, cy: cy });
                    svg += '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="none" stroke="#3da6f9" stroke-width="1.8" opacity="0.85"/>';
                    svg += '<circle cx="' + cx + '" cy="' + cy + '" r="1.5" fill="#3da6f9" opacity="0.5"/>';
                };

                var eLower = eyelets.toLowerCase().replace(/-/g, ' ');

                if (eLower === '4 corners' || eLower === '4corners') {
                    addEyelet(bx + inset, by + inset);
                    addEyelet(bx + bw - inset, by + inset);
                    addEyelet(bx + inset, by + bh - inset);
                    addEyelet(bx + bw - inset, by + bh - inset);
                } else if (eLower.indexOf('3') !== -1 && eLower.indexOf('top') !== -1) {
                    // 3 top 3 bottom
                    var arr = [bx + inset, bx + bw/2, bx + bw - inset];
                    for (var ti = 0; ti < arr.length; ti++) { addEyelet(arr[ti], by + inset); }
                    for (var bi = 0; bi < arr.length; bi++) { addEyelet(arr[bi], by + bh - inset); }
                } else if (eLower.indexOf('4') !== -1 && eLower.indexOf('top') !== -1) {
                    // 4 top 4 bottom
                    var sp4 = bw / 5;
                    for (var j = 1; j <= 4; j++) {
                        addEyelet(bx + sp4 * j, by + inset);
                        addEyelet(bx + sp4 * j, by + bh - inset);
                    }
                } else if (eLower.indexOf('50cm') !== -1 || eLower.indexOf('every 50') !== -1) {
                    // Every 50cm (1.64ft).
                    var spaceFt = 1.64;
                    var spacePx = spaceFt * scale;
                    var countW = Math.max(2, Math.floor(wFt / spaceFt) + 1);
                    var countH = Math.max(2, Math.floor(hFt / spaceFt) + 1);
                    // Top and bottom edges
                    for (var wi = 0; wi < countW; wi++) {
                        var cx = bx + (wi / (countW - 1)) * bw;
                        addEyelet(cx, by + inset);
                        addEyelet(cx, by + bh - inset);
                    }
                    // Left and right edges (skip corners)
                    for (var hi = 1; hi < countH - 1; hi++) {
                        var cy = by + (hi / (countH - 1)) * bh;
                        addEyelet(bx + inset, cy);
                        addEyelet(bx + bw - inset, cy);
                    }
                } else if (eLower.indexOf('every 1ft') !== -1 || eLower.indexOf('every 1') !== -1) {
                    // Every 1ft
                    var s1 = 1; // 1ft spacing
                    var c1W = Math.max(2, Math.floor(wFt / s1) + 1);
                    var c1H = Math.max(2, Math.floor(hFt / s1) + 1);
                    for (var e1i = 0; e1i < c1W; e1i++) {
                        var cx1 = bx + (e1i / (c1W - 1)) * bw;
                        addEyelet(cx1, by + inset);
                        addEyelet(cx1, by + bh - inset);
                    }
                    for (var e1j = 1; e1j < c1H - 1; e1j++) {
                        var cy1 = by + (e1j / (c1H - 1)) * bh;
                        addEyelet(bx + inset, cy1);
                        addEyelet(bx + bw - inset, cy1);
                    }
                } else if (eLower.indexOf('every 2ft') !== -1 || eLower.indexOf('every 2') !== -1) {
                    // Every 2ft
                    var s2 = 2;
                    var c2W = Math.max(2, Math.floor(wFt / s2) + 1);
                    var c2H = Math.max(2, Math.floor(hFt / s2) + 1);
                    for (var e2i = 0; e2i < c2W; e2i++) {
                        var cx2 = bx + (e2i / (c2W - 1)) * bw;
                        addEyelet(cx2, by + inset);
                        addEyelet(cx2, by + bh - inset);
                    }
                    for (var e2j = 1; e2j < c2H - 1; e2j++) {
                        var cy2 = by + (e2j / (c2H - 1)) * bh;
                        addEyelet(bx + inset, cy2);
                        addEyelet(bx + bw - inset, cy2);
                    }
                } else if (eLower.indexOf('top only') !== -1 || eLower.indexOf('top-only') !== -1) {
                    var cTW = Math.max(2, Math.round(wFt) + 1);
                    for (var eti = 0; eti < cTW; eti++) {
                        addEyelet(bx + (eti / (cTW - 1)) * bw, by + inset);
                    }
                } else if (eLower.indexOf('2 top corners') !== -1 || eLower.indexOf('2top') !== -1) {
                    addEyelet(bx + inset, by + inset);
                    addEyelet(bx + bw - inset, by + inset);
                } else if (eLower.indexOf('all sides') !== -1 || eLower.indexOf('all-sides') !== -1) {
                    var cAS = Math.max(2, Math.round(wFt) + 1);
                    var cASh = Math.max(2, Math.round(hFt) + 1);
                    for (var ai = 0; ai < cAS; ai++) {
                        var ax = bx + (ai / (cAS - 1)) * bw;
                        addEyelet(ax, by + inset);
                        addEyelet(ax, by + bh - inset);
                    }
                    for (var aj = 1; aj < cASh - 1; aj++) {
                        var ay = by + (aj / (cASh - 1)) * bh;
                        addEyelet(bx + inset, ay);
                        addEyelet(bx + bw - inset, ay);
                    }
                } else {
                    // Fallback: 4 corners
                    addEyelet(bx + inset, by + inset);
                    addEyelet(bx + bw - inset, by + inset);
                    addEyelet(bx + inset, by + bh - inset);
                    addEyelet(bx + bw - inset, by + bh - inset);
                }
            }

            // CABLE TIES.
            var cableTies = attrs['pa_cable-ties'] || '';
            if ((cableTies === 'yes' || cableTies === 'included') && eyeletPositions.length > 0) {
                for (var ci = 0; ci < eyeletPositions.length; ci++) {
                    var ep = eyeletPositions[ci];
                    var loopR = 7;
                    var centerX = bx + bw / 2;
                    var centerY = by + bh / 2;
                    var dx = ep.cx - centerX;
                    var dy = ep.cy - centerY;
                    var len = Math.sqrt(dx*dx + dy*dy) || 1;
                    var nx = dx / len;
                    var ny = dy / len;
                    var lx = ep.cx + nx * (loopR + 3);
                    var ly = ep.cy + ny * (loopR + 3);

                    svg += '<circle cx="' + lx + '" cy="' + ly + '" r="' + loopR + '" fill="none" stroke="#39B54A" stroke-width="1.5" opacity="0.7" stroke-dasharray="2 2"/>';
                    svg += '<line x1="' + ep.cx + '" y1="' + ep.cy + '" x2="' + (lx - nx * (loopR - 2)) + '" y2="' + (ly - ny * (loopR - 2)) + '" stroke="#39B54A" stroke-width="1.2" opacity="0.5"/>';
                }
            }

            // Dimension labels.
            var arrowY = by + bh + 18;
            svg += '<line x1="' + bx + '" y1="' + arrowY + '" x2="' + (bx + bw) + '" y2="' + arrowY + '" stroke="#8892A0" stroke-width="1"/>';
            svg += '<text x="' + (bx + bw/2) + '" y="' + (arrowY + 16) + '" text-anchor="middle" font-family="IBM Plex Mono, monospace" font-size="12" fill="#555E6E" font-weight="600">' + wDisp + abbr + '</text>';

            var arrowX = bx - 16;
            svg += '<line x1="' + arrowX + '" y1="' + by + '" x2="' + arrowX + '" y2="' + (by + bh) + '" stroke="#8892A0" stroke-width="1"/>';
            svg += '<text x="' + arrowX + '" y="' + (by + bh/2 + 3) + '" text-anchor="middle" font-family="IBM Plex Mono, monospace" font-size="12" fill="#555E6E" font-weight="600" transform="rotate(-90,' + arrowX + ',' + (by + bh/2) + ')">' + hDisp + abbr + '</text>';

            svg += '</svg>';

            this.canvasEl.html(svg);
            this.updateLegend();
        }
    };

    // Initialise on DOM ready.
    $(document).ready(function() {
        BannerCalc.init();
        BannerCalcPreview.init();

        // Relocate Personalize link into the "Design Online" panel.
        var $personalizeSlot = $('#bannercalc-personalize-slot');
        if ($personalizeSlot.length) {
            var $personalize = $('form.cart a.product_type_customizable');
            if ($personalize.length) {
                $personalize.appendTo($personalizeSlot);
            }
        }

        // Relocate file uploader into the "Upload Files" panel.
        var $uploadSlot = $('#bannercalc-design-slot');
        if ($uploadSlot.length) {
            var $uploader = $('form.cart .wc-dnd-file-upload');
            if ($uploader.length) {
                $uploader.appendTo($uploadSlot);
            }
        }

        // Hide design mode panels if their content is empty.
        if (!$('#bannercalc-personalize-slot').children().length) {
            // If no personalize button, grey-out the "Design Online" pill.
            $('.bannercalc-design-pill[data-design-mode="online"]').css('opacity', '0.4').attr('title', 'Online designer not available for this product');
        }
        if (!$('#bannercalc-design-slot').children().length && !$('#bannercalc-design-upload').find('.wc-dnd-file-upload').length) {
            // If no uploader, keep the panel but show a placeholder.
            if (!$('#bannercalc-design-slot').children().length) {
                $('#bannercalc-design-slot').html('<p style="color:#8892A0;font-size:13px;">File upload not available for this product.</p>');
            }
        }
    });

})(jQuery);
