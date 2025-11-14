(function($) {
    'use strict';

    const TLMS_Admin = {
        init: function() {
            this.bindEvents();
            this.initializeCategoryBuilder();
        },

        bindEvents: function() {
            // Export/Import handlers
            $(document).on('click', '#tlms-export-settings', this.exportSettings.bind(this));
            $(document).on('change', '#tlms-import-file', this.importSettings.bind(this));
            
            // Bulk actions
            $(document).on('click', '.tlms-bulk-action', this.handleBulkAction.bind(this));
        },

        exportSettings: function(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const originalText = $button.text();
            
            $button.text('Exporting...').prop('disabled', true);
            
            $.ajax({
                url: tlms_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'tlms_export_settings',
                    nonce: tlms_admin_ajax.nonce
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: (response, status, xhr) => {
                    // Create download link
                    const blob = new Blob([response]);
                    const link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = 'tlms-settings-export.json';
                    link.click();
                    
                    $button.text(originalText).prop('disabled', false);
                },
                error: (xhr, status, error) => {
                    console.error('Export error:', error);
                    alert('Error exporting settings. Please try again.');
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        handleBulkAction: function(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const action = $button.data('action');
            const originalText = $button.text();
            
            $button.text('Processing...').prop('disabled', true);
            
            $.ajax({
                url: tlms_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'tlms_admin_actions',
                    action_type: action,
                    nonce: tlms_admin_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert('Error: ' + response.data);
                    }
                    $button.text(originalText).prop('disabled', false);
                },
                error: (xhr, status, error) => {
                    console.error('Bulk action error:', error);
                    alert('Error processing action. Please try again.');
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        initializeCategoryBuilder: function() {
            // يمكن إضافة وظائف إضافية هنا
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        TLMS_Admin.init();
    });

})(jQuery);