/**
 * SkillScore Ebook Commerce - Public JS
 * Handles audio preview only. Purchase form, bulk inquiry, and order bump
 * logic is handled by inline script in ebook-single.php for template isolation.
 *
 * @package SkillScore_Ebook
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Audio Preview Handler
         */
        $('#load-audio-preview').on('click', function(e) {
            e.preventDefault();

            var $button  = $(this);
            var ebookId  = $button.data('ebook-id');
            var origHtml = $button.html();

            $button.prop('disabled', true).html('Loading audio...');

            $.ajax({
                url:     skillscoreEbook.ajaxUrl,
                type:    'POST',
                data: {
                    action:   'skillscore_get_audio_preview',
                    nonce:    skillscoreEbook.nonce,
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
                        $button.prop('disabled', false).html(origHtml);
                    }
                },
                error: function() {
                    showAudioError('Network error loading audio preview.');
                    $button.prop('disabled', false).html(origHtml);
                }
            });
        });

        function loadAudioFile(audioUrl) {
            var html = '<div style="margin-top:1rem;">' +
                '<audio controls style="width:100%;max-width:500px;border-radius:8px;">' +
                '<source src="' + audioUrl + '" type="audio/mpeg">' +
                '<source src="' + audioUrl + '" type="audio/wav">' +
                '<source src="' + audioUrl + '" type="audio/ogg">' +
                'Your browser does not support the audio element.' +
                '</audio>' +
                '<p style="margin-top:10px;font-size:.875rem;color:#9ca3af;">Audio preview loaded</p>' +
                '</div>';
            $('#audio-preview-player').html(html).removeClass('hidden');
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
            utterance.rate   = 0.9;
            utterance.pitch  = 1.0;
            utterance.volume = 1.0;

            var playerHtml = '<div style="background:#1f2937;padding:20px;border-radius:8px;margin-top:1rem;">' +
                '<div style="display:flex;gap:10px;margin-bottom:15px;">' +
                '<button id="tts-play" style="flex:1;padding:8px;background:#EAB308;color:#000;border:none;border-radius:6px;cursor:pointer;">▶ Play</button>' +
                '<button id="tts-pause" style="flex:1;padding:8px;background:#2A2A2A;color:#fff;border:none;border-radius:6px;cursor:pointer;" disabled>⏸ Pause</button>' +
                '<button id="tts-stop" style="flex:1;padding:8px;background:#2A2A2A;color:#fff;border:none;border-radius:6px;cursor:pointer;" disabled>⏹ Stop</button>' +
                '</div>' +
                '<p style="font-size:.875rem;color:#9ca3af;margin:0;">Browser TTS</p>' +
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
                showAudioError('Browser TTS error: ' + event.error);
            };
        }

        function showAudioError(message) {
            $('#audio-preview-player').html(
                '<div style="background:#fee2e2;border:2px solid #ef4444;padding:15px;border-radius:8px;margin-top:1rem;">' +
                '<p style="color:#dc2626;font-weight:600;margin:0;">⚠ ' + message + '</p></div>'
            ).removeClass('hidden');
        }

    });

})(jQuery);
