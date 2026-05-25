/**
 * SkillScore Ebook Commerce - Public JavaScript - ENHANCED
 * Priority 3: Better audio preview, purchase flow, interface
 *
 * @package SkillScore_Ebook
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * PRIORITY 2: Enhanced Audio Preview Handler
         */
        $('#load-audio-preview').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var ebookId = $button.data('ebook-id');
            var originalHtml = $button.html();

            // Show loading state
            $button.prop('disabled', true).html(
                '<svg class="animate-spin h-5 w-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">' +
                '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
                '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
                '</svg> Loading audio...'
            );

            $.ajax({
                url: skillscoreEbook.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'skillscore_get_audio_preview',
                    nonce: skillscoreEbook.nonce,
                    ebook_id: ebookId
                },
                timeout: 30000, // 30 second timeout
                success: function(response) {
                    if (response.success && response.data.audio_url) {
                        if (response.data.audio_url === 'browser_tts') {
                            // Use browser TTS
                            useBrowserTTS(ebookId, response.data.text);
                            $button.hide();
                        } else {
                            // Load audio file
                            loadAudioFile(response.data.audio_url);
                            $button.hide();
                        }
                    } else {
                        var errorMsg = response.data && response.data.message 
                            ? response.data.message 
                            : 'Failed to load audio preview.';
                        showAudioError(errorMsg);
                        $button.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Audio preview error:', error);
                    var errorMsg = 'Network error loading audio preview. Please try again.';
                    showAudioError(errorMsg);
                    $button.prop('disabled', false).html(originalHtml);
                }
            });
        });

        /**
         * Load audio file
         */
        function loadAudioFile(audioUrl) {
            var audioHtml = '<div class="audio-loaded-player" style="margin-top: 1rem;">' +
                '<audio controls style="width: 100%; max-width: 500px; border-radius: 8px;">' +
                '<source src="' + audioUrl + '" type="audio/mpeg">' +
                '<source src="' + audioUrl + '" type="audio/wav">' +
                '<source src="' + audioUrl + '" type="audio/ogg">' +
                'Your browser does not support the audio element.' +
                '</audio>' +
                '<p style="margin-top: 10px; font-size: 0.875rem; color: #9ca3af;">🎧 Audio preview loaded successfully</p>' +
                '</div>';

            $('#audio-preview-player').html(audioHtml).removeClass('hidden');
        }

        /**
         * Browser-based TTS using Web Speech API
         */
        function useBrowserTTS(ebookId, previewText) {
            if (!('speechSynthesis' in window)) {
                showAudioError('Your browser does not support text-to-speech. Please try a different browser.');
                return;
            }

            // Get text from page if not provided
            if (!previewText) {
                previewText = $('.preview-text').first().text().substring(0, 500);
                if (!previewText) {
                    previewText = $('.ebook-description, .post-excerpt, .entry-excerpt').first().text().substring(0, 500);
                }
            }

            if (!previewText) {
                showAudioError('No preview text available for audio generation.');
                return;
            }

            var utterance = new SpeechSynthesisUtterance(previewText);
            utterance.rate = 0.9;
            utterance.pitch = 1.0;
            utterance.volume = 1.0;

            // Create custom player controls
            var playerHtml = '<div class="browser-tts-player" style="background: #1f2937; padding: 20px; border-radius: 8px; margin-top: 1rem;">' +
                '<div style="display: flex; gap: 10px; margin-bottom: 15px;">' +
                '<button id="tts-play" class="btn-primary" style="flex: 1;">▶ Play</button>' +
                '<button id="tts-pause" class="btn-secondary" style="flex: 1;" disabled>⏸ Pause</button>' +
                '<button id="tts-stop" class="btn-secondary" style="flex: 1;" disabled>⏹ Stop</button>' +
                '</div>' +
                '<p style="font-size: 0.875rem; color: #9ca3af; margin: 0;">🎧 Browser Text-to-Speech (Web Speech API)</p>' +
                '</div>';

            $('#audio-preview-player').html(playerHtml).removeClass('hidden');

            // Play button
            $('#tts-play').on('click', function() {
                window.speechSynthesis.cancel(); // Clear any previous
                window.speechSynthesis.speak(utterance);
                $(this).prop('disabled', true);
                $('#tts-pause, #tts-stop').prop('disabled', false);
            });

            // Pause button
            $('#tts-pause').on('click', function() {
                if (window.speechSynthesis.speaking && !window.speechSynthesis.paused) {
                    window.speechSynthesis.pause();
                    $(this).text('▶ Resume');
                } else {
                    window.speechSynthesis.resume();
                    $(this).text('⏸ Pause');
                }
            });

            // Stop button
            $('#tts-stop').on('click', function() {
                window.speechSynthesis.cancel();
                $('#tts-play').prop('disabled', false);
                $('#tts-pause, #tts-stop').prop('disabled', true);
                $('#tts-pause').text('⏸ Pause');
            });

            // Reset when speech ends
            utterance.onend = function() {
                $('#tts-play').prop('disabled', false);
                $('#tts-pause, #tts-stop').prop('disabled', true);
                $('#tts-pause').text('⏸ Pause');
            };

            // Handle errors
            utterance.onerror = function(event) {
                console.error('Speech synthesis error:', event);
                showAudioError('Browser TTS error: ' + event.error);
                $('#tts-play').prop('disabled', false);
                $('#tts-pause, #tts-stop').prop('disabled', true);
            };
        }

        /**
         * Show audio error message
         */
        function showAudioError(message) {
            var errorHtml = '<div class="audio-error" style="background: #fee2e2; border: 2px solid #ef4444; padding: 15px; border-radius: 8px; margin-top: 1rem;">' +
                '<p style="color: #dc2626; font-weight: 600; margin: 0;">⚠️ Audio Preview Error</p>' +
                '<p style="color: #991b1b; font-size: 0.875rem; margin: 10px 0 0 0;">' + message + '</p>' +
                '</div>';

            $('#audio-preview-player').html(errorHtml).removeClass('hidden');
        }

        /**
         * PRIORITY 1: Purchase Form Handler
         */
        $('#ebook-purchase-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var originalText = $button.html();

            // Validate form
            if (!$form[0].checkValidity()) {
                $form[0].reportValidity();
                return;
            }

            // Validate payment gateway selected
            var gateway = $form.find('input[name="gateway"]:checked').val();
            if (!gateway) {
                showMessage('error', 'Please select a payment method.');
                return;
            }

            // Show loading
            $button.prop('disabled', true).html(
                '<svg class="animate-spin h-5 w-5 mr-2 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">' +
                '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
                '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
                '</svg> Processing Payment...'
            );

            // Prepare data
            var formData = {
                action: 'skillscore_initiate_payment',
                nonce: skillscoreEbook.nonce,
                ebook_id: $form.find('input[name="ebook_id"]').val(),
                quantity: $form.find('input[name="quantity"]').val() || 1,
                user_name: $form.find('input[name="user_name"]').val(),
                user_email: $form.find('input[name="user_email"]').val(),
                gateway: gateway
            };

            // Send AJAX request
            $.ajax({
                url: skillscoreEbook.ajaxUrl,
                type: 'POST',
                data: formData,
                timeout: 30000,
                success: function(response) {
                    if (response.success && response.data.redirect_url) {
                        // Show redirect message
                        showMessage('success', 'Redirecting to payment gateway...');
                        
                        // Redirect after 1 second
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 1000);
                    } else {
                        var errorMsg = response.data && response.data.message 
                            ? response.data.message 
                            : 'Payment initiation failed. Please try again.';
                        showMessage('error', errorMsg);
                        $button.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Payment error:', error);
                    showMessage('error', 'Network error. Please check your connection and try again.');
                    $button.prop('disabled', false).html(originalText);
                }
            });
        });

        /**
         * Show message to user
         */
        function showMessage(type, message) {
            var bgColor = type === 'error' ? '#fee2e2' : '#d1fae5';
            var borderColor = type === 'error' ? '#ef4444' : '#10b981';
            var textColor = type === 'error' ? '#dc2626' : '#065f46';
            var icon = type === 'error' ? '⚠️' : '✅';

            var messageHtml = '<div class="skillscore-message" style="background: ' + bgColor + '; border: 2px solid ' + borderColor + '; color: ' + textColor + '; padding: 16px; margin-bottom: 20px; border-radius: 8px; font-weight: 600;">' +
                icon + ' ' + message +
                '</div>';

            // Remove existing messages
            $('.skillscore-message').remove();

            // Insert at top of single ebook view
            $('.skillscore-ebook-single').prepend(messageHtml);

            // Scroll to message
            $('html, body').animate({
                scrollTop: $('.skillscore-message').offset().top - 100
            }, 500);

            // Auto-remove success messages after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $('.skillscore-message').fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        }

        /**
         * Smooth scroll to purchase form
         */
        $('a[href="#purchase"], .btn-buy-now').on('click', function(e) {
            e.preventDefault();
            var $form = $('#ebook-purchase-form');
            if ($form.length) {
                $('html, body').animate({
                    scrollTop: $form.offset().top - 100
                }, 500);
            }
        });

        /**
         * Update quantity price display
         */
        $('input[name="quantity"]').on('input change', function() {
            var quantity = parseInt($(this).val()) || 1;
            var basePrice = parseFloat($(this).data('price')) || 0;
            var totalPrice = quantity * basePrice;

            var $priceDisplay = $('.total-price-display');
            if ($priceDisplay.length) {
                $priceDisplay.text(skillscoreEbook.currencySymbol + totalPrice.toFixed(2));
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
                // Fallback
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(link).select();
                document.execCommand('copy');
                $temp.remove();
                showMessage('success', 'Download link copied to clipboard!');
            }
        });

        /**
         * Payment method selection highlight
         */
        $('.payment-method-option input[type="radio"]').on('change', function() {
            $('.payment-method-option').removeClass('selected');
            $(this).closest('.payment-method-option').addClass('selected');
        });

        /**
         * Add selected class style
         */
        $('<style>')
            .text('.payment-method-option.selected { border-color: #eab308 !important; background: rgba(234, 179, 8, 0.1) !important; }')
            .appendTo('head');

    });

})(jQuery);