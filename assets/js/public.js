/**
 * SkillScore Ebook Commerce - COMPLETE WORKING VERSION
 * Properly sends shipping data to server
 *
 * @package SkillScore_Ebook
 */

(function($) {
    'use strict';

    var DEBUG = true;

    function log(message, data) {
        if (DEBUG) {
            console.log('[SkillScore] ' + message, data || '');
        }
    }

    $(document).ready(function() {
        log('Public script loaded - COMPLETE VERSION');

        /**
         * FORMAT CHANGE HANDLER - Show/Hide Shipping Fields
         */
        $('input[name="format"]').on('change', function() {
            var selectedFormat = $(this).val();
            log('Format changed to: ' + selectedFormat);

            var $shippingSection = $('#shipping-address-section');
            var $shippingFields = $shippingSection.find('input');

            if (selectedFormat === 'printed') {
                log('Showing shipping fields');
                $shippingSection.slideDown(300);
                $shippingFields.prop('required', true);
                updateTotalWithShipping();
            } else {
                log('Hiding shipping fields');
                $shippingSection.slideUp(300);
                $shippingFields.prop('required', false);
                updateTotalWithShipping();
            }
        });

        /**
         * Update total price with shipping
         */
        function updateTotalWithShipping() {
            var $quantityInput = $('input[name="quantity"]');
            var quantity = parseInt($quantityInput.val()) || 1;
            var basePrice = parseFloat($quantityInput.data('price')) || 0;
            var selectedFormat = $('input[name="format"]:checked').val() || 'ebook';
            
            var shippingFee = 0;
            if (selectedFormat === 'printed') {
                shippingFee = parseFloat($('#shipping-fee-amount').data('fee')) || 5.00;
            }
            
            var totalPrice = (basePrice * quantity) + (shippingFee * quantity);
            
            log('Price calculation - Base: ' + basePrice + ', Qty: ' + quantity + ', Shipping: ' + shippingFee + ', Total: ' + totalPrice);
            
            var $priceDisplay = $('.total-price-display');
            if ($priceDisplay.length) {
                $priceDisplay.text(skillscoreEbook.currencySymbol + totalPrice.toFixed(2));
            }
        }

        /**
         * Quantity change handler
         */
        $('input[name="quantity"]').on('input change', function() {
            updateTotalWithShipping();
        });

        /**
         * Validate shipping address fields
         */
        function validateShippingAddress($form) {
            var fields = ['shipping_address', 'shipping_city', 'shipping_state', 'shipping_postal', 'shipping_country', 'shipping_phone'];
            var allValid = true;

            fields.forEach(function(fieldName) {
                var value = $form.find('input[name="' + fieldName + '"]').val();
                if (!value || value.trim() === '') {
                    log('Missing field: ' + fieldName);
                    allValid = false;
                }
            });

            return allValid;
        }

        /**
         * PURCHASE FORM HANDLER - COMPLETE WORKING VERSION
         */
        $('#ebook-purchase-form').on('submit', function(e) {
            e.preventDefault();

            log('========== PURCHASE FORM SUBMITTED ==========');

            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var originalText = $button.html();

            // Validate form
            if (!$form[0].checkValidity()) {
                $form[0].reportValidity();
                log('Form validation failed');
                return;
            }

            // Get selected format
            var format = $form.find('input[name="format"]:checked').val() || 'ebook';
            log('Selected format: ' + format);

            // Validate payment gateway selected
            var gateway = $form.find('input[name="gateway"]:checked').val();
            if (!gateway) {
                showMessage('error', 'Please select a payment method.');
                log('No payment gateway selected');
                return;
            }

            // Validate shipping for printed format
            if (format === 'printed') {
                var shippingValid = validateShippingAddress($form);
                if (!shippingValid) {
                    showMessage('error', 'Please fill in all shipping address fields.');
                    log('Shipping validation failed');
                    return;
                }
            }

            // Show loading
            $button.prop('disabled', true).html(
                '<svg class="animate-spin h-5 w-5 mr-2 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">' +
                '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
                '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
                '</svg> Processing Payment...'
            );

            // Collect shipping data FIRST if printed format
            var shippingData = null;
            if (format === 'printed') {
                shippingData = {
                    address: $form.find('input[name="shipping_address"]').val(),
                    city: $form.find('input[name="shipping_city"]').val(),
                    state: $form.find('input[name="shipping_state"]').val(),
                    postal_code: $form.find('input[name="shipping_postal"]').val(),
                    country: $form.find('input[name="shipping_country"]').val(),
                    phone: $form.find('input[name="shipping_phone"]').val()
                };
                
                log('Shipping address data:', JSON.stringify(shippingData));
            }

            // Prepare form data
            var formData = {
                action: 'skillscore_initiate_payment',
                nonce: skillscoreEbook.nonce,
                ebook_id: $form.find('input[name="ebook_id"]').val(),
                quantity: $form.find('input[name="quantity"]').val() || 1,
                user_name: $form.find('input[name="user_name"]').val(),
                user_email: $form.find('input[name="user_email"]').val(),
                gateway: gateway,
                format: format
            };

            // Add shipping data if collected
            if (shippingData !== null) {
                formData.shipping_data = JSON.stringify(shippingData);
                log('✅ Adding shipping_data to request:', formData.shipping_data);
            } else {
                log('No shipping data (format is not printed)');
            }

            log('Form data being sent:', formData);
            log('AJAX URL:', skillscoreEbook.ajaxUrl);

            // Send AJAX request
            $.ajax({
                url: skillscoreEbook.ajaxUrl,
                type: 'POST',
                data: formData,
                timeout: 30000,
                success: function(response) {
                    log('========== SERVER RESPONSE RECEIVED ==========');
                    log('Full response:', response);
                    log('Response type:', typeof response);
                    log('Response.success:', response.success);
                    log('Response.data:', response.data);

                    if (response.success) {
                        log('✅ SUCCESS response');
                        
                        if (response.data && response.data.redirect_url) {
                            log('Redirect URL:', response.data.redirect_url);
                            log('Purchase ID:', response.data.purchase_id);
                            
                            showMessage('success', 'Redirecting to payment gateway...');
                            
                            setTimeout(function() {
                                log('Redirecting now...');
                                window.location.href = response.data.redirect_url;
                            }, 1000);
                        } else {
                            log('✗ Success=true but no redirect_url!');
                            showMessage('error', 'Payment initiated but no redirect URL received.');
                            $button.prop('disabled', false).html(originalText);
                        }
                    } else {
                        log('✗ ERROR response');
                        
                        var errorMsg = 'Payment initiation failed. Please try again.';
                        
                        if (response.data && response.data.message) {
                            errorMsg = response.data.message;
                            log('Error message:', errorMsg);
                        }
                        
                        console.error('========== PAYMENT FAILED ==========');
                        console.error('Error Message:', errorMsg);
                        console.error('Full Response:', response);
                        console.error('====================================');
                        
                        showMessage('error', errorMsg);
                        $button.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    log('========== AJAX ERROR ==========');
                    log('XHR:', xhr);
                    log('Status:', status);
                    log('Error:', error);
                    log('Response Text:', xhr.responseText);
                    log('Status Code:', xhr.status);
                    
                    console.error('========== AJAX REQUEST FAILED ==========');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('XHR Status:', xhr.status);
                    console.error('Response:', xhr.responseText);
                    console.error('========================================');
                    
                    showMessage('error', 'Network error. Please check your connection and try again.');
                    $button.prop('disabled', false).html(originalText);
                },
                complete: function() {
                    log('========== REQUEST COMPLETE ==========');
                }
            });
        });

        /**
         * Audio Preview Handler
         */
        $('#load-audio-preview').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var ebookId = $button.data('ebook-id');
            var originalHtml = $button.html();

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
                timeout: 30000,
                success: function(response) {
                    if (response.success && response.data.audio_url) {
                        if (response.data.audio_url === 'browser_tts') {
                            useBrowserTTS(ebookId, response.data.text);
                            $button.hide();
                        } else {
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
                    showAudioError('Network error loading audio preview.');
                    $button.prop('disabled', false).html(originalHtml);
                }
            });
        });

        function loadAudioFile(audioUrl) {
            var audioHtml = '<div class="audio-loaded-player" style="margin-top: 1rem;">' +
                '<audio controls style="width: 100%; max-width: 500px; border-radius: 8px;">' +
                '<source src="' + audioUrl + '" type="audio/mpeg">' +
                '<source src="' + audioUrl + '" type="audio/wav">' +
                '<source src="' + audioUrl + '" type="audio/ogg">' +
                'Your browser does not support the audio element.' +
                '</audio>' +
                '<p style="margin-top: 10px; font-size: 0.875rem; color: #9ca3af;">🎧 Audio preview loaded</p>' +
                '</div>';

            $('#audio-preview-player').html(audioHtml).removeClass('hidden');
        }

        function useBrowserTTS(ebookId, previewText) {
            if (!('speechSynthesis' in window)) {
                showAudioError('Your browser does not support text-to-speech.');
                return;
            }

            if (!previewText) {
                previewText = $('.preview-text').first().text().substring(0, 500);
            }

            if (!previewText) {
                showAudioError('No preview text available.');
                return;
            }

            var utterance = new SpeechSynthesisUtterance(previewText);
            utterance.rate = 0.9;
            utterance.pitch = 1.0;
            utterance.volume = 1.0;

            var playerHtml = '<div class="browser-tts-player" style="background: #1f2937; padding: 20px; border-radius: 8px; margin-top: 1rem;">' +
                '<div style="display: flex; gap: 10px; margin-bottom: 15px;">' +
                '<button id="tts-play" class="btn-primary" style="flex: 1;">▶ Play</button>' +
                '<button id="tts-pause" class="btn-secondary" style="flex: 1;" disabled>⏸ Pause</button>' +
                '<button id="tts-stop" class="btn-secondary" style="flex: 1;" disabled>⏹ Stop</button>' +
                '</div>' +
                '<p style="font-size: 0.875rem; color: #9ca3af; margin: 0;">🎧 Browser TTS</p>' +
                '</div>';

            $('#audio-preview-player').html(playerHtml).removeClass('hidden');

            $('#tts-play').on('click', function() {
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(utterance);
                $(this).prop('disabled', true);
                $('#tts-pause, #tts-stop').prop('disabled', false);
            });

            $('#tts-pause').on('click', function() {
                if (window.speechSynthesis.speaking && !window.speechSynthesis.paused) {
                    window.speechSynthesis.pause();
                    $(this).text('▶ Resume');
                } else {
                    window.speechSynthesis.resume();
                    $(this).text('⏸ Pause');
                }
            });

            $('#tts-stop').on('click', function() {
                window.speechSynthesis.cancel();
                $('#tts-play').prop('disabled', false);
                $('#tts-pause, #tts-stop').prop('disabled', true);
                $('#tts-pause').text('⏸ Pause');
            });

            utterance.onend = function() {
                $('#tts-play').prop('disabled', false);
                $('#tts-pause, #tts-stop').prop('disabled', true);
                $('#tts-pause').text('⏸ Pause');
            };

            utterance.onerror = function(event) {
                console.error('Speech synthesis error:', event);
                showAudioError('Browser TTS error: ' + event.error);
            };
        }

        function showAudioError(message) {
            var errorHtml = '<div class="audio-error" style="background: #fee2e2; border: 2px solid #ef4444; padding: 15px; border-radius: 8px; margin-top: 1rem;">' +
                '<p style="color: #dc2626; font-weight: 600; margin: 0;">⚠️ ' + message + '</p>' +
                '</div>';
            $('#audio-preview-player').html(errorHtml).removeClass('hidden');
        }

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

            $('.skillscore-message').remove();
            $('.skillscore-ebook-single, .ebook-purchase-section').first().prepend(messageHtml);

            $('html, body').animate({
                scrollTop: $('.skillscore-message').offset().top - 100
            }, 500);

            if (type === 'success') {
                setTimeout(function() {
                    $('.skillscore-message').fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        }

        /**
         * Payment method selection
         */
        $('.payment-method-option input[type="radio"]').on('change', function() {
            $('.payment-method-option').removeClass('selected');
            $(this).closest('.payment-method-option').addClass('selected');
        });

    });

})(jQuery);