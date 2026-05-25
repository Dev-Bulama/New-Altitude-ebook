/**
 * SkillScore Ebook Commerce - Public JavaScript - FIXED
 * Fixes: Purchase button not proceeding after loading
 *
 * @package SkillScore_Ebook
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Handle purchase form submission - ENHANCED WITH DEBUGGING
         */
        $('#ebook-purchase-form').on('submit', function(e) {
            e.preventDefault();

            console.log('Purchase form submitted');

            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var originalText = $button.html();

            // Validate form
            if (!$form[0].checkValidity()) {
                console.log('Form validation failed');
                $form[0].reportValidity();
                return;
            }

            // Check if payment gateway is selected
            var selectedGateway = $form.find('input[name="gateway"]:checked').val();
            if (!selectedGateway) {
                console.log('No payment gateway selected');
                showMessage('error', 'Please select a payment method.');
                return;
            }

            console.log('Payment gateway selected:', selectedGateway);

            // Disable button and show loading
            $button.prop('disabled', true).html(
                '<svg class="animate-spin h-5 w-5 mr-2 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">' +
                '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
                '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
                '</svg> Processing...'
            );

            // Prepare data
            var formData = {
                action: 'skillscore_initiate_payment',
                nonce: skillscoreEbook.nonce,
                ebook_id: $form.find('input[name="ebook_id"]').val(),
                quantity: $form.find('input[name="quantity"]').val() || 1,
                user_name: $form.find('input[name="user_name"]').val(),
                user_email: $form.find('input[name="user_email"]').val(),
                gateway: selectedGateway
            };

            console.log('Sending payment request with data:', formData);

            // Send AJAX request
            $.ajax({
                url: skillscoreEbook.ajaxUrl,
                type: 'POST',
                data: formData,
                timeout: 30000, // 30 second timeout
                success: function(response) {
                    console.log('Payment response received:', response);
                    
                    if (response.success && response.data && response.data.redirect_url) {
                        console.log('Redirecting to:', response.data.redirect_url);
                        // Redirect to payment gateway
                        window.location.href = response.data.redirect_url;
                    } else {
                        console.error('Payment initiation failed:', response);
                        var errorMsg = 'Payment initiation failed.';
                        if (response.data && response.data.message) {
                            errorMsg = response.data.message;
                        }
                        showMessage('error', errorMsg);
                        $button.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    console.error('Response:', xhr.responseText);
                    
                    var errorMsg = 'An error occurred. Please try again.';
                    if (status === 'timeout') {
                        errorMsg = 'Request timed out. Please check your connection and try again.';
                    } else if (xhr.status === 500) {
                        errorMsg = 'Server error. Please try again or contact support.';
                    } else if (xhr.status === 403) {
                        errorMsg = 'Permission denied. Please refresh the page and try again.';
                    }
                    
                    showMessage('error', errorMsg);
                    $button.prop('disabled', false).html(originalText);
                }
            });
        });

        /**
         * Load audio preview - FIXED TO USE SPECIFIC BOOK CONTENT
         */
        $('#load-audio-preview').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var ebookId = $button.data('ebook-id');
            var originalText = $button.html();

            console.log('Loading audio preview for ebook ID:', ebookId);

            // Disable button and show loading
            $button.prop('disabled', true).html('Loading...');

            $.ajax({
                url: skillscoreEbook.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'skillscore_get_audio_preview',
                    nonce: skillscoreEbook.nonce,
                    ebook_id: ebookId
                },
                success: function(response) {
                    console.log('Audio preview response:', response);
                    
                    if (response.success && response.data.audio_url) {
                        if (response.data.audio_url === 'browser_tts') {
                            // Use browser TTS (Web Speech API)
                            console.log('Using browser TTS for ebook', ebookId);
                            useBrowserTTS(ebookId);
                        } else {
                            // Load audio file
                            console.log('Loading audio file:', response.data.audio_url);
                            $('#audio-preview-source').attr('src', response.data.audio_url);
                            $('#audio-preview-player').removeClass('hidden');
                            $('#audio-preview-player audio')[0].load();
                            $button.hide();
                        }
                    } else {
                        console.error('Audio preview failed:', response);
                        showMessage('error', response.data.message || 'Failed to load audio preview.');
                        $button.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Audio preview AJAX error:', status, error);
                    showMessage('error', 'An error occurred while loading audio preview.');
                    $button.prop('disabled', false).html(originalText);
                }
            });
        });

        /**
         * Browser-based TTS using Web Speech API
         * FIXED: Now gets specific ebook content
         */
        function useBrowserTTS(ebookId) {
            if ('speechSynthesis' in window) {
                // Get preview text from the page excerpt
                var previewText = $('.ebook-single-grid .preview-text').text();
                
                // If no preview text found, try description
                if (!previewText || previewText.length < 50) {
                    previewText = $('.ebook-single-grid h3:contains("ABOUT THIS BOOK")').next('div').text();
                }
                
                // Trim to reasonable length for TTS
                previewText = previewText.substring(0, 500).trim();
                
                console.log('Using browser TTS with text length:', previewText.length);

                if (previewText && previewText.length > 10) {
                    var utterance = new SpeechSynthesisUtterance(previewText);
                    utterance.rate = 0.9;
                    utterance.pitch = 1.0;

                    // Show custom player controls
                    var playerHtml = '<div class="browser-tts-player" style="padding: 1rem; background: var(--dark-gray); border: 2px solid var(--neon-yellow); border-radius: 8px;">' +
                        '<div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">' +
                        '<button id="tts-play" class="btn-primary" style="flex: 1;">▶ Play</button>' +
                        '<button id="tts-pause" class="btn-secondary" style="flex: 1;" disabled>⏸ Pause</button>' +
                        '<button id="tts-stop" class="btn-secondary" style="flex: 1;" disabled>⏹ Stop</button>' +
                        '</div>' +
                        '<p style="font-size: 0.75rem; color: #9ca3af; margin: 0;">🎧 Browser text-to-speech preview</p>' +
                        '</div>';

                    $('#audio-preview-player').html(playerHtml).removeClass('hidden');
                    $('#load-audio-preview').hide();

                    // Play button
                    $('#tts-play').on('click', function() {
                        window.speechSynthesis.speak(utterance);
                        $(this).prop('disabled', true);
                        $('#tts-pause, #tts-stop').prop('disabled', false);
                    });

                    // Pause button
                    $('#tts-pause').on('click', function() {
                        if (window.speechSynthesis.speaking && !window.speechSynthesis.paused) {
                            window.speechSynthesis.pause();
                            $(this).html('▶ Resume');
                        } else {
                            window.speechSynthesis.resume();
                            $(this).html('⏸ Pause');
                        }
                    });

                    // Stop button
                    $('#tts-stop').on('click', function() {
                        window.speechSynthesis.cancel();
                        $('#tts-play').prop('disabled', false);
                        $('#tts-pause, #tts-stop').prop('disabled', true);
                        $('#tts-pause').html('⏸ Pause');
                    });

                    // Reset buttons when speech ends
                    utterance.onend = function() {
                        $('#tts-play').prop('disabled', false);
                        $('#tts-pause, #tts-stop').prop('disabled', true);
                        $('#tts-pause').html('⏸ Pause');
                    };
                } else {
                    showMessage('error', 'No preview text available for audio generation.');
                }
            } else {
                showMessage('error', 'Your browser does not support text-to-speech.');
            }
        }

        /**
         * Show message to user - ENHANCED STYLING
         */
        function showMessage(type, message) {
            var messageClass = type === 'error' 
                ? 'bg-red-100 border-red-500 text-red-700' 
                : 'bg-green-100 border-green-500 text-green-700';
            
            var icon = type === 'error'
                ? '<svg style="width: 20px; height: 20px; display: inline-block; margin-right: 8px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>'
                : '<svg style="width: 20px; height: 20px; display: inline-block; margin-right: 8px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>';
            
            var messageHtml = '<div class="' + messageClass + ' border-l-4 p-4 mb-4 rounded" role="alert" style="animation: slideDown 0.3s ease-out;">' +
                '<p style="display: flex; align-items: center;">' + icon + message + '</p>' +
                '</div>';

            // Insert message at the top of the ebook single view or near the form
            if ($('.skillscore-ebook-single').length) {
                $('.skillscore-ebook-single').prepend(messageHtml);
            } else {
                $('#ebook-purchase-form').before(messageHtml);
            }

            // Auto-remove after 8 seconds
            setTimeout(function() {
                $('[role="alert"]').first().fadeOut(function() {
                    $(this).remove();
                });
            }, 8000);
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $('[role="alert"]').first().offset().top - 100
            }, 300);
        }

        /**
         * Smooth scroll to purchase form
         */
        $('a[href="#purchase"], button[onclick*="ebook-purchase-form"]').on('click', function(e) {
            if ($(this).attr('onclick')) {
                return; // Let inline onclick handler work
            }
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('#ebook-purchase-form').offset().top - 100
            }, 500);
        });

        /**
         * Update quantity price display (if exists)
         */
        $('input[name="quantity"]').on('input', function() {
            var quantity = parseInt($(this).val()) || 1;
            var basePrice = parseFloat($(this).data('price')) || 0;
            var totalPrice = quantity * basePrice;

            if ($('.total-price-display').length) {
                $('.total-price-display').text(skillscoreEbook.currencySymbol + totalPrice.toFixed(2));
            }
        });

        /**
         * Copy download link functionality
         */
        $('.copy-download-link').on('click', function(e) {
            e.preventDefault();
            var link = $(this).data('link');

            if (navigator.clipboard) {
                navigator.clipboard.writeText(link).then(function() {
                    showMessage('success', 'Download link copied to clipboard!');
                });
            } else {
                // Fallback for older browsers
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(link).select();
                document.execCommand('copy');
                $temp.remove();
                showMessage('success', 'Download link copied to clipboard!');
            }
        });
        
        /**
         * Debug info logger
         */
        console.log('SkillScore Ebook Commerce initialized');
        console.log('AJAX URL:', skillscoreEbook.ajaxUrl);
        console.log('Nonce:', skillscoreEbook.nonce ? 'Present' : 'MISSING');
        console.log('Purchase form found:', $('#ebook-purchase-form').length > 0);
        console.log('Audio preview button found:', $('#load-audio-preview').length > 0);
    });

})(jQuery);

// Add CSS animation for message
if (!document.getElementById('skillscore-animations')) {
    var style = document.createElement('style');
    style.id = 'skillscore-animations';
    style.innerHTML = `
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
}