(function($) {
    'use strict';

    const TLMS_Public = {
        init: function() {
            this.bindEvents();
            this.initializeRegistrationFields();
        },

        bindEvents: function() {
            // Education type change on registration
            $(document).on('change', '#tlms_education_type', this.handleEducationTypeChange.bind(this));
            
            // Category chain selection
            $(document).on('change', '.tlms-category-select', this.handleCategoryChange.bind(this));
            
            // Form submission validation
            $(document).on('submit', 'form.tutor-registration-form, form#your-profile', this.validateForm.bind(this));
        },

        initializeRegistrationFields: function() {
            const $educationType = $('#tlms_education_type');
            if ($educationType.length && $educationType.val()) {
                this.handleEducationTypeChange({ target: $educationType[0] });
            }
        },

        handleEducationTypeChange: function(e) {
            const educationType = $(e.target).val();
            const $container = $('#tlms_academic_categories_container');
            
            if (educationType) {
                this.showLoading($container);
                this.loadEducationTypeCategories(educationType, 0, $container);
                $container.show();
            } else {
                $container.hide().empty();
            }
        },

        handleCategoryChange: function(e) {
            const $select = $(e.target);
            const level = $select.data('level');
            const categoryId = $select.val();
            const $container = $select.closest('.tlms-category-level');
            
            // Remove higher levels
            $container.nextAll('.tlms-category-level').remove();
            
            if (categoryId) {
                this.showLoading($container);
                this.loadChildCategories(categoryId, level + 1, $container);
            }
        },

        loadEducationTypeCategories: function(educationType, level, $container) {
            $.ajax({
                url: tlms_public_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'tlms_get_academic_categories',
                    education_type: educationType,
                    level: level,
                    nonce: tlms_public_ajax.nonce
                },
                success: (response) => {
                    this.hideLoading($container);
                    if (response.success) {
                        $container.html(response.data);
                    } else {
                        this.showError($container, 'Failed to load categories');
                    }
                },
                error: (xhr, status, error) => {
                    this.hideLoading($container);
                    this.showError($container, 'Error loading categories. Please try again.');
                    console.error('Error loading education type categories:', error);
                }
            });
        },

        loadChildCategories: function(parentId, level, $container) {
            $.ajax({
                url: tlms_public_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'tlms_get_child_categories',
                    parent_id: parentId,
                    level: level,
                    nonce: tlms_public_ajax.nonce
                },
                success: (response) => {
                    this.hideLoading($container);
                    if (response.success && response.data) {
                        $container.after(response.data);
                    }
                },
                error: (xhr, status, error) => {
                    this.hideLoading($container);
                    this.showError($container, 'Error loading sub-categories. Please try again.');
                    console.error('Error loading child categories:', error);
                }
            });
        },

        validateForm: function(e) {
            const $form = $(e.target);
            const $educationType = $('#tlms_education_type');
            
            if ($educationType.length) {
                if (!$educationType.val()) {
                    e.preventDefault();
                    this.showFieldError($educationType, 'Please select an education type');
                    $('html, body').animate({
                        scrollTop: $educationType.offset().top - 100
                    }, 500);
                    return false;
                }
                
                if ($educationType.val() !== 'general') {
                    const $categorySelects = $('.tlms-category-select');
                    let isValid = true;
                    
                    $categorySelects.each(function() {
                        const $select = $(this);
                        if (!$select.val()) {
                            isValid = false;
                            TLMS_Public.showFieldError($select, 'Please select a ' + $select.closest('.tlms-category-level').find('label').text().toLowerCase());
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        $('html, body').animate({
                            scrollTop: $categorySelects.first().offset().top - 100
                        }, 500);
                        return false;
                    }
                }
            }
            
            return true;
        },

        showLoading: function($element) {
            $element.append('<div class="tlms-loading"></div>');
        },

        hideLoading: function($element) {
            $element.find('.tlms-loading').remove();
        },

        showError: function($element, message) {
            $element.append('<div class="tlms-error-message">' + message + '</div>');
            setTimeout(() => {
                $element.find('.tlms-error-message').remove();
            }, 5000);
        },

        showFieldError: function($field, message) {
            $field.addClass('tlms-field-error');
            $field.after('<span class="tlms-error-message">' + message + '</span>');
            
            $field.one('change', function() {
                $field.removeClass('tlms-field-error');
                $field.next('.tlms-error-message').remove();
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        TLMS_Public.init();
    });

})(jQuery);