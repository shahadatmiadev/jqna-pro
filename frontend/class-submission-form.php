<?php
/**
 * Submission Form Class
 *
 * @package Qnario
 */

if (!defined('ABSPATH')) {
    exit;
}

class JQNA_Submission_Form {
    
    /**
     * Render submission form
     */
    public function render() {
        ob_start();
        ?>
        <div class="jqna-submission-form">
            <form id="jqna-submit-form" method="post">
                <?php wp_nonce_field('jqna_submit_nonce', 'jqna_submit_nonce'); ?>
                
                <div class="jqna-form-group">
                    <label for="question_title"><?php esc_html_e('Question Title', 'jqna_'); ?></label>
                    <input type="text" id="question_title" name="question_title" required>
                </div>
                
                <div class="jqna-form-group">
                    <label for="question_content"><?php esc_html_e('Question Details', 'jqna_'); ?></label>
                    <textarea id="question_content" name="question_content" rows="5" required></textarea>
                </div>
                
                <button type="submit" class="jqna-submit-button">
                    <?php esc_html_e('Submit Question', 'jqna_'); ?>
                </button>
                
                <div class="jqna-form-message"></div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#jqna-submit-form').on('submit', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var messageDiv = form.find('.jqna-form-message');
                var submitBtn = form.find('button[type="submit"]');
                
                submitBtn.prop('disabled', true).text('<?php echo esc_js(__('Submitting...', 'jqna_')); ?>');
                
                $.ajax({
                    url: jqna_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'jqna_submit_question',
                        nonce: jqna_ajax.submit_nonce,
                        title: form.find('#question_title').val(),
                        content: form.find('#question_content').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            messageDiv.html('<div class="jqna-success">' + response.data.message + '</div>');
                            form[0].reset();
                        } else {
                            messageDiv.html('<div class="jqna-error">' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        messageDiv.html('<div class="jqna-error"><?php echo esc_js(__('An error occurred', 'jqna_')); ?></div>');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text('<?php echo esc_js(__('Submit Question', 'jqna_')); ?>');
                        setTimeout(function() {
                            messageDiv.empty();
                        }, 3000);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}