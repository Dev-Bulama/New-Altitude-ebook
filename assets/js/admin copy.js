/**
 * SkillScore Ebook Commerce - Admin JavaScript
 * Handles file uploads including ebook files and audio previews
 *
 * @package SkillScore_Ebook
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Ebook File Upload
         */
        let ebookFileFrame;

        $('#upload-ebook-file-btn').on('click', function(e) {
            e.preventDefault();

            // If the media frame already exists, reopen it
            if (ebookFileFrame) {
                ebookFileFrame.open();
                return;
            }

            // Create the media frame
            ebookFileFrame = wp.media({
                title: 'Select Ebook File',
                button: {
                    text: 'Use this file'
                },
                library: {
                    type: ['application/pdf', 'application/epub+zip', 'application/x-mobipocket-ebook']
                },
                multiple: false
            });

            // When a file is selected
            ebookFileFrame.on('select', function() {
                const attachment = ebookFileFrame.state().get('selection').first().toJSON();
                
                // Update hidden fields
                $('#ebook_file_url').val(attachment.url);
                $('#ebook_file_id').val(attachment.id);

                // Update display
                const displayHtml = 
                    '<div style="background: #f0f0f1; padding: 15px; border-radius: 4px; border-left: 4px solid #2271b1;">' +
                    '<p style="margin: 0 0 10px 0;"><strong>Current File:</strong></p>' +
                    '<p style="margin: 0; font-family: monospace; font-size: 12px; word-break: break-all;">📄 ' + attachment.filename + '</p>' +
                    '</div>';
                
                $('#ebook-file-display').html(displayHtml);
                
                // Update button text
                $('#upload-ebook-file-btn').text('Replace Ebook File');
                
                // Show remove button if not visible
                if ($('#remove-ebook-file-btn').length === 0) {
                    $('#upload-ebook-file-btn').after(
                        '<button type="button" class="button button-secondary" id="remove-ebook-file-btn" style="margin-left: 10px;">Remove File</button>'
                    );
                }
            });

            // Open the media frame
            ebookFileFrame.open();
        });

        // Remove ebook file
        $(document).on('click', '#remove-ebook-file-btn', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to remove this file?')) {
                $('#ebook_file_url').val('');
                $('#ebook_file_id').val('');
                
                const displayHtml = 
                    '<div style="background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 4px solid #ffc107;">' +
                    '<p style="margin: 0; color: #856404;">⚠️ No file uploaded yet</p>' +
                    '</div>';
                
                $('#ebook-file-display').html(displayHtml);
                $('#upload-ebook-file-btn').text('Upload Ebook File');
                $(this).remove();
            }
        });

        /**
         * Audio Preview Upload - NEW
         */
        let audioPreviewFrame;

        $('#upload-audio-preview-btn').on('click', function(e) {
            e.preventDefault();

            // If the media frame already exists, reopen it
            if (audioPreviewFrame) {
                audioPreviewFrame.open();
                return;
            }

            // Create the media frame
            audioPreviewFrame = wp.media({
                title: 'Select Audio Preview',
                button: {
                    text: 'Use this audio'
                },
                library: {
                    type: ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg']
                },
                multiple: false
            });

            // When an audio file is selected
            audioPreviewFrame.on('select', function() {
                const attachment = audioPreviewFrame.state().get('selection').first().toJSON();
                
                // Update hidden fields
                $('#ebook_audio_preview_url').val(attachment.url);
                $('#ebook_audio_preview_id').val(attachment.id);

                // Update display with audio player
                const displayHtml = 
                    '<div style="background: #d1e7dd; padding: 15px; border-radius: 4px; border-left: 4px solid #198754;">' +
                    '<p style="margin: 0 0 10px 0;"><strong>Current Audio:</strong></p>' +
                    '<p style="margin: 0 0 10px 0; font-family: monospace; font-size: 12px; word-break: break-all;">🎧 ' + attachment.filename + '</p>' +
                    '<audio controls style="width: 100%; max-width: 400px;">' +
                    '<source src="' + attachment.url + '" type="' + attachment.mime + '">' +
                    '</audio>' +
                    '</div>';
                
                $('#ebook-audio-display').html(displayHtml);
                
                // Update button text
                $('#upload-audio-preview-btn').text('Replace Audio Preview');
                
                // Show remove button if not visible
                if ($('#remove-audio-preview-btn').length === 0) {
                    $('#upload-audio-preview-btn').after(
                        '<button type="button" class="button button-secondary" id="remove-audio-preview-btn" style="margin-left: 10px;">Remove Audio</button>'
                    );
                }

                // Show success message
                showNotice('success', 'Audio preview uploaded successfully!');
            });

            // Open the media frame
            audioPreviewFrame.open();
        });

        // Remove audio preview
        $(document).on('click', '#remove-audio-preview-btn', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to remove this audio preview?')) {
                $('#ebook_audio_preview_url').val('');
                $('#ebook_audio_preview_id').val('');
                
                const displayHtml = 
                    '<div style="background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 4px solid #ffc107;">' +
                    '<p style="margin: 0; color: #856404;">ℹ️ No audio file uploaded. Auto-generation or browser TTS will be used.</p>' +
                    '</div>';
                
                $('#ebook-audio-display').html(displayHtml);
                $('#upload-audio-preview-btn').text('Upload Audio Preview');
                $(this).remove();

                showNotice('info', 'Audio preview removed. Auto-generation will be used.');
            }
        });

        /**
         * Show admin notice
         */
        function showNotice(type, message) {
            const noticeClass = type === 'success' ? 'notice-success' : 
                               type === 'error' ? 'notice-error' : 'notice-info';
            
            const notice = $(
                '<div class="notice ' + noticeClass + ' is-dismissible">' +
                '<p>' + message + '</p>' +
                '</div>'
            );

            $('.wrap h1').after(notice);

            // Auto-remove after 3 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }

        /**
         * Voice Sample Upload on Settings Page
         */
        let voiceSampleFrame;

        $('#upload-voice-sample-btn').on('click', function(e) {
            e.preventDefault();

            if (voiceSampleFrame) {
                voiceSampleFrame.open();
                return;
            }

            voiceSampleFrame = wp.media({
                title: 'Select Voice Sample',
                button: {
                    text: 'Use this sample'
                },
                library: {
                    type: ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg']
                },
                multiple: false
            });

            voiceSampleFrame.on('select', function() {
                const attachment = voiceSampleFrame.state().get('selection').first().toJSON();
                $('#skillscore_ebook_global_voice_url').val(attachment.url);
                $('#voice-sample-preview').html(
                    '<audio controls style="width: 100%; max-width: 400px; margin-top: 10px;">' +
                    '<source src="' + attachment.url + '" type="' + attachment.mime + '">' +
                    '</audio>'
                );
            });

            voiceSampleFrame.open();
        });

    });

})(jQuery);