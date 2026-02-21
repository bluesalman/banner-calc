/**
 * BannerCalc Admin Scripts
 *
 * @package BannerCalc
 */
(function($) {
    'use strict';

    const BannerCalcAdmin = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Toggle override fields in product metabox.
            $(document).on('change', '#bannercalc-override-toggle', function() {
                $('#bannercalc-override-fields').toggle(this.checked);
            });

            // Future: drag-to-reorder preset sizes, attribute pricing tables, etc.
        }
    };

    $(document).ready(function() {
        BannerCalcAdmin.init();
    });

})(jQuery);
