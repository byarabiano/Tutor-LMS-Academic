/**
 * Tutor LMS Academic Pro - Registration Fields JavaScript
 */
(function($) {
    'use strict';

    const TLMS_Registration = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Education type button clicks
            $(document).on('click', '.tlms-edu-btn', this.handleEducationTypeClick.bind(this));
            
            // Category selection changes
            $(document).on('change', '.tlms-category-select', this.handleCategoryChange.bind(this));
            
            // Form submission validation
            $(document).on('submit', 'form.tutor-registration-form', this.validateFormSubmission.bind(this));
        },

        handleEducationTypeClick: function(e) {
            const $button = $(e.target);
            const educationType = $button.data('education-type');
            
            // Update active button
            $('.tlms-edu-btn').removeClass('active');
            $button.addClass('active');
            
            // Set hidden field value
            $('#tlms_education_type').val(educationType);
            
            // Hide error message
            $('#tlms_education_type_error').hide();
            
            // Show categories container
            $('#tlms_academic_categories_container').show();
            
            // Load categories for this education type
            this.loadEducationCategories(educationType, 0);
        },

        handleCategoryChange: function(e) {
            const $select = $(e.target);
            const level = $select.data('level');
            const categoryId = $select.val();
            const $container = $select.closest('.tlms-category-level');
            
            // Remove higher levels
            $container.nextAll('.tlms-category-level').remove();
            
            // Hide errors
            $container.find('.tlms-field-error').hide();
            $select.removeClass('error');
            
            if (categoryId) {
                this.loadChildCategories(categoryId, level + 1, $container);
            }
        },

        loadEducationCategories: function(educationType, level) {
            const $container = $('#tlms_categories_dynamic_content');
            this.showLoading($container, tutor_lms_academic_pro.loading_text);
            
            $.ajax({
                url: tutor_lms_academic_pro.ajax_url,
                type: 'POST',
                data: {
                    action: 'tlms_get_academic_categories',
                    education_type: educationType,
                    level: level,
                    nonce: tutor_lms_academic_pro.nonce
                },
                success: (response) => {
                    if (response.success && response.data) {
                        $container.html(response.data);
                    } else {
                        $container.html('<p class="tutor-color-muted">' + tutor_lms_academic_pro.no_categories_text + '</p>');
                    }
                },
                error: () => {
                    $container.html('<p class="tutor-color-muted">' + tutor_lms_academic_pro.error_text + '</p>');
                }
            });
        },

        loadChildCategories: function(parentId, level, container) {
            const $loading = $('<div class="tlms-category-level"><div class="tlms-loading"></div> ' + tutor_lms_academic_pro.loading_text + '</div>');
            $(container).after($loading);
            
            $.ajax({
                url: tutor_lms_academic_pro.ajax_url,
                type: 'POST',
                data: {
                    action: 'tlms_get_academic_categories',
                    education_type: $('#tlms_education_type').val(),
                    parent_id: parentId,
                    level: level,
                    nonce: tutor_lms_academic_pro.nonce
                },
                success: (response) => {
                    $loading.remove();
                    if (response.success && response.data) {
                        $(container).after(response.data);
                    }
                },
                error: () => {
                    $loading.remove();
                }
            });
        },

        validateFormSubmission: function(e) {
            let isValid = true;
            
            // Validate education type selection
            if (!$('#tlms_education_type').val()) {
                $('#tlms_education_type_error').text(tutor_lms_academic_pro.select_education_type).show();
                isValid = false;
            }
            
            // Validate category chain for non-general education types
            if ($('#tlms_education_type').val() !== 'general') {
                const $categorySelects = $('.tlms-category-select');
                let hasEmptySelections = false;
                
                $categorySelects.each(function() {
                    const $select = $(this);
                    if (!$select.val()) {
                        hasEmptySelections = true;
                        $select.addClass('error');
                        if (!$select.next('.tlms-field-error').length) {
                            $select.after('<div class="tlms-field-error">' + tutor_lms_academic_pro.field_required + '</div>');
                        } else {
                            $select.next('.tlms-field-error').show();
                        }
                    } else {
                        $select.removeClass('error');
                        $select.next('.tlms-field-error').hide();
                    }
                });
                
                if (hasEmptySelections) {
                    isValid = false;
                    $('html, body').animate({
                        scrollTop: $('.tlms-registration-fields').offset().top - 100
                    }, 500);
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
            
            return isValid;
        },

        showLoading: function($element, text) {
            $element.html('<div class="tlms-loading"></div> ' + text);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        TLMS_Registration.init();
    });

})(jQuery);