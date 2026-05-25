/**
 * SkillScore Ebook Commerce - Admin JavaScript - SUPER FIXED
 * Multiple fallbacks for upload functionality
 *
 * @package SkillScore_Ebook
 */

(function($) {
    'use strict';

    // Debug flag
    var debug = true;
    
    function log(message, data) {
        if (debug) {
            console.log('[SkillScore Debug] ' + message, data || '');
        }
    }

    log('Script file loaded');

    // Wait for DOM ready
    $(document).ready(function() {
        log('DOM ready');

        // Check jQuery
        if (typeof $ === 'undefined') {
            alert('jQuery not loaded! Contact support.');
            return;
        }
        log('jQuery loaded', $.fn.jquery);

        // Check WordPress
        if (typeof wp === 'undefined') {
            alert('WordPress not loaded! Contact support.');
            return;
        }
        log('WordPress object available');

        // Check wp.media
        if (typeof wp.media === 'undefined') {
            console.error('❌ WordPress Media Library NOT loaded!');
            console.error('This means wp_enqueue_media() is not being called.');
            console.error('Check class-ebook-cpt.php line ~420');
            
            // Show admin notice
            showErrorNotice('WordPress Media Library not loaded. Cannot upload files. Please contact support.');
            return;
        }
        log('✅ WordPress Media Library loaded');

        // Check if we're on ebook page
        var isEbookPage = $('body').hasClass('post-type-ebook') || $('#post-type-ebook').length > 0;
        log('Is ebook page?', isEbookPage);

        // Initialize upload handlers
        initEbookFileUpload();
        initAudioPreviewUpload();
        initVoiceSampleUpload();
        initStockHandlers();

        log('✅ All handlers initialized');
    });

    /**
     * Ebook File Upload Handler
     */
    function initEbookFileUpload() {
        log('Initializing ebook file upload...');

        var $button = $('#upload-ebook-file-btn');
        if ($button.length === 0) {
            log('⚠️ Upload ebook file button not found on page');
            return;
        }
        log('✅ Upload button found', $button);

        var ebookFileFrame;

        // Use multiple event binding methods
        $button.off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            log('🖱️ Ebook file upload button CLICKED');

            // Verify wp.media exists at click time
            if (typeof wp.media === 'undefined') {
                alert('WordPress Media Library not available. Please refresh the page and try again.');
                return;
            }

            // If frame exists, reopen
            if (ebookFileFrame) {
                log('Reopening existing media frame');
                ebookFileFrame.open();
                return;
            }

            log('Creating new media frame...');

            // Create media frame
            try {
                ebookFileFrame = wp.media({
                    title: 'Select Ebook File',
                    button: {
                        text: 'Use this file'
                    },
                    multiple: false
                });

                log('✅ Media frame created');

                // When file selected
                ebookFileFrame.on('select', function() {
                    log('File selected from media library');
                    
                    var attachment = ebookFileFrame.state().get('selection').first().toJSON();
                    log('Attachment data:', attachment);

                    // Update hidden fields
                    $('#ebook_file_url').val(attachment.url);
                    $('#ebook_file_id').val(attachment.id);
                    log('Hidden fields updated');

                    // Update display
                    var fileName = attachment.filename || attachment.title || 'Unknown file';
                    var displayHtml = 
                        '<div style="background: #d4edda; padding: 15px; border-radius: 4px; border-left: 4px solid #28a745;">' +
                        '<p style="margin: 0 0 10px 0;"><strong>✓ Current File:</strong></p>' +
                        '<p style="margin: 0 0 5px 0; font-family: monospace; font-size: 12px; word-break: break-all;">📄 ' + fileName + '</p>' +
                        '<p style="margin: 0; font-size: 11px; color: #666;">ID: ' + attachment.id + ' | URL: ' + attachment.url + '</p>' +
                        '</div>';
                    
                    $('#ebook-file-display').html(displayHtml);
                    log('Display updated');

                    // Update button
                    $('#upload-ebook-file-btn').text('Replace Ebook File');

                    // Show remove button
                    if ($('#remove-ebook-file-btn').length === 0) {
                        $('#upload-ebook-file-btn').after(
                            ' <button type="button" class="button button-secondary" id="remove-ebook-file-btn">Remove File</button>'
                        );
                    }

                    showSuccessNotice('✓ File uploaded successfully! Don\'t forget to click Update/Publish.');
                });

                // Open frame
                log('Opening media frame...');
                ebookFileFrame.open();
                log('✅ Media frame opened');

            } catch (error) {
                console.error('❌ Error creating media frame:', error);
                alert('Error opening media library: ' + error.message);
            }
        });

        // Also bind to document (fallback)
        $(document).off('click', '#upload-ebook-file-btn').on('click', '#upload-ebook-file-btn', function(e) {
            log('Document delegation triggered for ebook upload');
        });

        log('✅ Ebook file upload handler bound');
    }

    /**
     * Remove Ebook File
     */
    $(document).on('click', '#remove-ebook-file-btn', function(e) {
        e.preventDefault();
        log('Remove ebook file clicked');

        if (!confirm('Remove this file?')) {
            return;
        }

        $('#ebook_file_url').val('');
        $('#ebook_file_id').val('');
        
        var displayHtml = 
            '<div style="background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 4px solid #ffc107;">' +
            '<p style="margin: 0; color: #856404;">⚠️ No file uploaded yet</p>' +
            '</div>';
        
        $('#ebook-file-display').html(displayHtml);
        $('#upload-ebook-file-btn').text('Upload Ebook File');
        $(this).remove();

        showSuccessNotice('File removed. Don\'t forget to click Update/Publish.');
    });

    /**
     * Audio Preview Upload Handler
     */
    function initAudioPreviewUpload() {
        log('Initializing audio preview upload...');

        var $button = $('#upload-audio-preview-btn');
        if ($button.length === 0) {
            log('⚠️ Upload audio button not found on page');
            return;
        }
        log('✅ Audio upload button found');

        var audioPreviewFrame;

        $button.off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            log('🖱️ Audio preview upload button CLICKED');

            if (typeof wp.media === 'undefined') {
                alert('WordPress Media Library not available. Please refresh the page.');
                return;
            }

            if (audioPreviewFrame) {
                log('Reopening audio frame');
                audioPreviewFrame.open();
                return;
            }

            log('Creating audio media frame...');

            try {
                audioPreviewFrame = wp.media({
                    title: 'Select Audio Preview',
                    button: {
                        text: 'Use this audio'
                    },
                    library: {
                        type: 'audio'
                    },
                    multiple: false
                });

                log('✅ Audio frame created');

                audioPreviewFrame.on('select', function() {
                    log('Audio selected');
                    
                    var attachment = audioPreviewFrame.state().get('selection').first().toJSON();
                    log('Audio attachment:', attachment);

                    $('#ebook_audio_preview_url').val(attachment.url);
                    $('#ebook_audio_preview_id').val(attachment.id);

                    var fileName = attachment.filename || attachment.title || 'Unknown audio';
                    var displayHtml = 
                        '<div style="background: #d4edda; padding: 15px; border-radius: 4px; border-left: 4px solid #28a745;">' +
                        '<p style="margin: 0 0 10px 0;"><strong>✓ Current Audio:</strong></p>' +
                        '<p style="margin: 0 0 5px 0; font-family: monospace; font-size: 12px;">🎧 ' + fileName + '</p>' +
                        '<p style="margin: 0 0 10px 0; font-size: 11px; color: #666;">ID: ' + attachment.id + '</p>' +
                        '<audio controls style="width: 100%; max-width: 400px;">' +
                        '<source src="' + attachment.url + '" type="' + attachment.mime + '">' +
                        '</audio>' +
                        '</div>';
                    
                    $('#ebook-audio-display').html(displayHtml);

                    $('#upload-audio-preview-btn').text('Replace Audio Preview');

                    if ($('#remove-audio-preview-btn').length === 0) {
                        $('#upload-audio-preview-btn').after(
                            ' <button type="button" class="button button-secondary" id="remove-audio-preview-btn">Remove Audio</button>'
                        );
                    }

                    showSuccessNotice('✓ Audio uploaded! Don\'t forget to click Update/Publish.');
                });

                audioPreviewFrame.open();
                log('✅ Audio frame opened');

            } catch (error) {
                console.error('❌ Error with audio upload:', error);
                alert('Error: ' + error.message);
            }
        });

        $(document).off('click', '#upload-audio-preview-btn').on('click', '#upload-audio-preview-btn', function(e) {
            log('Document delegation for audio');
        });

        log('✅ Audio handler bound');
    }

    /**
     * Remove Audio Preview
     */
    $(document).on('click', '#remove-audio-preview-btn', function(e) {
        e.preventDefault();
        log('Remove audio clicked');

        if (!confirm('Remove this audio?')) {
            return;
        }

        $('#ebook_audio_preview_url').val('');
        $('#ebook_audio_preview_id').val('');
        
        var displayHtml = 
            '<div style="background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 4px solid #ffc107;">' +
            '<p style="margin: 0; color: #856404;">ℹ️ No audio uploaded. Auto-generation will be used.</p>' +
            '</div>';
        
        $('#ebook-audio-display').html(displayHtml);
        $('#upload-audio-preview-btn').text('Upload Audio Preview');
        $(this).remove();

        showSuccessNotice('Audio removed.');
    });

    /**
     * Voice Sample Upload (Settings Page)
     */
    function initVoiceSampleUpload() {
        var $button = $('#upload-voice-sample-btn');
        if ($button.length === 0) {
            return;
        }

        var voiceSampleFrame;

        $button.on('click', function(e) {
            e.preventDefault();
            log('Voice sample upload clicked');

            if (voiceSampleFrame) {
                voiceSampleFrame.open();
                return;
            }

            voiceSampleFrame = wp.media({
                title: 'Select Voice Sample',
                button: { text: 'Use this sample' },
                library: { type: 'audio' },
                multiple: false
            });

            voiceSampleFrame.on('select', function() {
                var attachment = voiceSampleFrame.state().get('selection').first().toJSON();
                $('#skillscore_ebook_global_voice_url').val(attachment.url);
                $('#voice-sample-preview').html(
                    '<p style="color: #28a745; margin-top: 10px;">✓ ' + attachment.filename + '</p>' +
                    '<audio controls style="width: 100%; max-width: 400px; margin-top: 10px;">' +
                    '<source src="' + attachment.url + '" type="' + attachment.mime + '">' +
                    '</audio>'
                );
            });

            voiceSampleFrame.open();
        });
    }

    /**
     * Stock Management Handlers
     */
    function initStockHandlers() {
        $('input[name="ebook_unlimited"]').on('change', function() {
            if ($(this).is(':checked')) {
                $('#ebook_quantity').prop('disabled', true).css('opacity', '0.5');
            } else {
                $('#ebook_quantity').prop('disabled', false).css('opacity', '1');
            }
        }).trigger('change');
    }

    /**
     * Show Success Notice
     */
    function showSuccessNotice(message) {
        showNotice('success', message);
    }

    /**
     * Show Error Notice
     */
    function showErrorNotice(message) {
        showNotice('error', message);
    }

    /**
     * Show Notice
     */
    function showNotice(type, message) {
        var className = type === 'success' ? 'notice-success' : 'notice-error';
        var icon = type === 'success' ? '✓' : '✗';
        
        var $notice = $(
            '<div class="notice ' + className + ' is-dismissible" style="padding: 12px; margin: 15px 0;">' +
            '<p><strong>' + icon + ' ' + message + '</strong></p>' +
            '</div>'
        );

        // Remove old notices
        $('.notice.is-dismissible').fadeOut(300, function() { $(this).remove(); });

        // Add new notice
        if ($('.wrap > h1').length) {
            $('.wrap > h1').after($notice);
        } else {
            $('.wrap').prepend($notice);
        }

        // Auto dismiss
        setTimeout(function() {
            $notice.fadeOut(300, function() { $(this).remove(); });
        }, 5000);

        log('Notice shown: ' + message);
    }

    /**
     * Debug: Save button monitoring
     */
    $('#publish, #save-post').on('click', function() {
        log('=== SAVE BUTTON CLICKED ===');
        log('Ebook file URL:', $('#ebook_file_url').val());
        log('Ebook file ID:', $('#ebook_file_id').val());
        log('Audio URL:', $('#ebook_audio_preview_url').val());
        log('Audio ID:', $('#ebook_audio_preview_id').val());
        log('===========================');
    });

    // Global error handler
    window.onerror = function(msg, url, lineNo, columnNo, error) {
        console.error('JavaScript Error:', msg, 'at', url, lineNo + ':' + columnNo);
        return false;
    };

})(jQuery);