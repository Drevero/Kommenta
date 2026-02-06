(function($) {
    'use strict';

    var rowIndex = 0;

    /**
     * Initialize color pickers on all rows
     */
    function initColorPickers($scope) {
        var $targets = $scope ? $scope.find('.kommenta-color-picker') : $('.kommenta-color-picker');
        $targets.each(function() {
            if ($(this).closest('.wp-picker-container').length) return;
            $(this).wpColorPicker({
                change: function() {},
                clear: function() {}
            });
        });
    }

    /**
     * Generate slug from label
     */
    function generateSlug(label) {
        return label
            .toLowerCase()
            .trim()
            .replace(/[àáâãäå]/g, 'a')
            .replace(/[èéêë]/g, 'e')
            .replace(/[ìíîï]/g, 'i')
            .replace(/[òóôõö]/g, 'o')
            .replace(/[ùúûü]/g, 'u')
            .replace(/[ñ]/g, 'n')
            .replace(/[ç]/g, 'c')
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s_]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    /**
     * Show toast notification
     */
    function showToast(message, type) {
        var $toast = $('#kommenta-toast');
        $toast.text(message).removeClass('success error visible').addClass(type);

        // Force reflow before adding visible class for animation
        $toast[0].offsetHeight;
        $toast.addClass('visible');

        setTimeout(function() {
            $toast.removeClass('visible');
        }, 3000);
    }

    /**
     * Add a new vote type row
     */
    function addNewRow() {
        rowIndex++;
        var template = $('#tmpl-kommenta-vote-type-row').html();
        template = template.replace(/\{\{data\.index\}\}/g, rowIndex);

        var $newRow = $(template);
        $('#kommenta-vote-types-list').append($newRow);

        // Init color picker on the new row
        initColorPickers($newRow);

        // Focus on label
        $newRow.find('.vote-type-label').focus();

        // Remove animation class after it plays
        setTimeout(function() {
            $newRow.removeClass('new-row');
        }, 350);
    }

    /**
     * Collect all vote types data from the DOM
     */
    function collectVoteTypes() {
        var voteTypes = [];

        $('.kommenta-vote-type-row').each(function() {
            var $row = $(this);
            var label = $row.find('.vote-type-label').val().trim();
            var slug = $row.find('.vote-type-slug').val().trim();
            var color = $row.find('.kommenta-color-picker').val() || '#cccccc';

            if (color.charAt(0) !== '#') {
                color = '#' + color;
            }

            if (label) {
                voteTypes.push({ label: label, slug: slug, color: color });
            }
        });

        return voteTypes;
    }

    /**
     * Save settings via AJAX
     */
    function saveSettings() {
        var voteTypes = collectVoteTypes();

        if (voteTypes.length === 0) {
            showToast(kommentaAdmin.i18n.labelRequired, 'error');
            return;
        }

        var $saveBtn = $('#kommenta-save-settings');
        $saveBtn.prop('disabled', true);

        $.ajax({
            url: kommentaAdmin.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'kommenta_save_vote_types',
                nonce: kommentaAdmin.nonce,
                vote_types: voteTypes
            },
            success: function(response) {
                if (response.success) {
                    showToast(kommentaAdmin.i18n.saved, 'success');

                    // Update slugs with server-generated values
                    if (response.data && response.data.vote_types) {
                        $('.kommenta-vote-type-row').each(function(index) {
                            if (response.data.vote_types[index]) {
                                var newSlug = response.data.vote_types[index].slug;
                                $(this).find('.vote-type-slug').val(newSlug);
                                $(this).find('.vote-type-slug-badge').text(newSlug);
                            }
                        });
                    }
                } else {
                    var msg = (response.data && response.data.message) ? response.data.message : kommentaAdmin.i18n.error;
                    showToast(msg, 'error');
                }
            },
            error: function(xhr) {
                showToast(kommentaAdmin.i18n.error + ' (' + xhr.status + ')', 'error');
            },
            complete: function() {
                $saveBtn.prop('disabled', false);
            }
        });
    }

    /**
     * Reset to default vote types
     */
    function resetToDefaults() {
        var defaults = [
            { label: 'Positive', slug: 'positive', color: '#6bc9a0' },
            { label: 'Negative', slug: 'negative', color: '#e88a94' },
            { label: 'Neutral', slug: 'neutral', color: '#9ba3d0' }
        ];

        var $list = $('#kommenta-vote-types-list');
        $list.empty();

        defaults.forEach(function(type, index) {
            var template = $('#tmpl-kommenta-vote-type-row').html();
            template = template.replace(/\{\{data\.index\}\}/g, index);

            var $row = $(template);
            $row.removeClass('new-row');
            $row.find('.vote-type-label').val(type.label);
            $row.find('.vote-type-slug').val(type.slug);
            $row.find('.vote-type-slug-badge').text(type.slug);
            $row.find('.kommenta-color-picker').val(type.color);

            $list.append($row);
        });

        initColorPickers();
    }

    /* =====================================
       DOM ready
       ===================================== */
    $(document).ready(function() {
        rowIndex = $('.kommenta-vote-type-row').length;

        // Init existing color pickers
        initColorPickers();

        // Add vote type
        $('#kommenta-add-type').on('click', addNewRow);

        // Remove vote type (delegated)
        $('#kommenta-vote-types-list').on('click', '.kommenta-remove-type', function() {
            var $row = $(this).closest('.kommenta-vote-type-row');
            if (!confirm(kommentaAdmin.i18n.confirmDelete)) return;

            $row.css({ opacity: 0, transform: 'translateX(20px)', transition: 'all 0.2s ease' });
            setTimeout(function() { $row.remove(); }, 220);
        });

        // Auto-generate slug on first blur (only if slug is empty)
        $('#kommenta-vote-types-list').on('blur', '.vote-type-label', function() {
            var $row = $(this).closest('.kommenta-vote-type-row');
            var $slug = $row.find('.vote-type-slug');
            var label = $(this).val().trim();

            if (!$slug.val() && label) {
                var slug = generateSlug(label);
                $slug.val(slug);
                $row.find('.vote-type-slug-badge').text(slug);
            }
        });

        // Save
        $('#kommenta-save-settings').on('click', saveSettings);

        // Reset
        $('#kommenta-reset-defaults').on('click', function() {
            if (confirm(kommentaAdmin.i18n.confirmDelete)) {
                resetToDefaults();
            }
        });
    });

})(jQuery);
