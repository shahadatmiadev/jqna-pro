/**
 * Qnario Frontend JavaScript
 * Handles accordion, AJAX filtering, pagination, and form submissions
 */

jQuery(document).ready(function($) {
    // Accordion functionality
    $(document).on('click', '.jqna-accordion-header', function() {
        var item = $(this).closest('.jqna-accordion-item');
        var content = item.find('.jqna-accordion-content');
        var icon = $(this).find('.jqna-toggle-icon');
        
        if (content.is(':visible')) {
            content.slideUp();
            icon.text('+');
        } else {
            $('.jqna-accordion-content').slideUp();
            $('.jqna-toggle-icon').text('+');
            content.slideDown();
            icon.text('-');
        }
    });
    
    // Category filter
    $(document).on('click', '.jqna-category-filter', function() {
        var categoryId = $(this).data('category');
        var container = $('.jqna-questions-list');
        
        $('.jqna-category-filter').removeClass('active');
        $(this).addClass('active');
        
        loadQuestions(categoryId, 1, container);
    });
    
    // Reset filter
    $(document).on('click', '.jqna-filter-reset', function() {
        var container = $('.jqna-questions-list');
        
        $('.jqna-category-filter').removeClass('active');
        $(this).addClass('active');
        
        loadQuestions(0, 1, container);
    });
    
    // Pagination
    $(document).on('click', '.jqna-pagination a', function(e) {
        e.preventDefault();
        var paged = $(this).data('page');
        var categoryId = $('.jqna-questions-list').data('category') || 0;
        var container = $('.jqna-questions-list');
        
        loadQuestions(categoryId, paged, container);
    });
    
    /**
     * Load questions via AJAX
     */
    function loadQuestions(categoryId, paged, container) {
        container.data('category', categoryId);
        container.data('paged', paged);
        
        $.ajax({
            url: jqna_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'jqna_filter_questions',
                nonce: jqna_ajax.nonce,
                category_id: categoryId,
                paged: paged
            },
            beforeSend: function() {
                container.html('<div class="jqna-loading">' + jqna_ajax.strings.loading + '</div>');
            },
            success: function(response) {
                if (response.success) {
                    container.html(response.data.html);
                    updatePagination(response.data.max_pages, paged, categoryId);
                    
                    // Re-initialize accordion for new content
                    $('.jqna-accordion-content').hide();
                } else {
                    container.html('<div class="jqna-error">' + jqna_ajax.strings.error + '</div>');
                }
            },
            error: function() {
                container.html('<div class="jqna-error">' + jqna_ajax.strings.error + '</div>');
            }
        });
    }
    
    /**
     * Update pagination links
     */
    function updatePagination(maxPages, currentPage, categoryId) {
        var paginationHtml = '<div class="jqna-pagination-links">';
        var prevText = jqna_ajax.strings.prev || 'Previous';
        var nextText = jqna_ajax.strings.next || 'Next';
        
        if (maxPages > 1) {
            if (currentPage > 1) {
                paginationHtml += '<a href="#" data-page="' + (currentPage - 1) + '" class="prev">« ' + prevText + '</a>';
            }
            
            for (var i = 1; i <= maxPages; i++) {
                if (i === currentPage) {
                    paginationHtml += '<span class="current">' + i + '</span>';
                } else {
                    paginationHtml += '<a href="#" data-page="' + i + '">' + i + '</a>';
                }
            }
            
            if (currentPage < maxPages) {
                paginationHtml += '<a href="#" data-page="' + (currentPage + 1) + '" class="next">' + nextText + ' »</a>';
            }
        }
        
        paginationHtml += '</div>';
        $('.jqna-pagination').html(paginationHtml);
    }
});