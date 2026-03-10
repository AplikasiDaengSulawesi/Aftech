/**
 * AFTECH JAVASCRIPT UTILITIES
 * Shared functions for PT AFTECH MAKASSAR INDONESIA
 */

window.AFTECH = {
    /**
     * Format number to Indonesian standard (e.g. 18.569)
     */
    formatNumber: function(num) {
        if (num === null || num === undefined) return "0";
        return parseInt(num).toLocaleString('id-ID');
    },

    /**
     * Show standardized Toast notification
     */
    showToast: function(title, message, type = 'success') {
        if (typeof toastr === 'undefined') {
            console.warn("Toastr is not loaded.");
            return;
        }
        toastr[type](message, title);
    },

    /**
     * Standardized AJAX Error Handler
     */
    handleAjaxError: function(error, customMsg = "Gagal mengambil data dari server.") {
        console.error("API Error:", error);
        this.showToast("Error", customMsg, 'error');
    },

    /**
     * Get URL Parameters
     */
    getUrlParam: function(name) {
        const results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
        return results ? results[1] : null;
    },

    /**
     * Setup DataTable defaults (if used)
     */
    initDataTable: function(selector, options = {}) {
        if (!$.fn.DataTable) return;
        return $(selector).DataTable({
            language: {
                paginate: {
                    next: '<i class="fa fa-angle-double-right" aria-hidden="true"></i>',
                    previous: '<i class="fa fa-angle-double-left" aria-hidden="true"></i>'
                }
            },
            ...options
        });
    }
};

// Global shorthand
window.formatNumber = (n) => window.AFTECH.formatNumber(n);