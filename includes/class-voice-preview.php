<?php
/**
 * Voice Preview Handler - WITH MANUAL UPLOAD SUPPORT
 * Checks for manually uploaded audio preview first
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Ebook_Voice_Preview {

    /**
     * Get audio preview - CHECKS MANUAL UPLOAD FIRST
     */
    public function get_audio_preview() {
        check_ajax_referer('skillscore_ebook_nonce', 'nonce');

        $ebook_id = intval($_POST['ebook_id']);

        if (!$ebook_id || get_post_type($ebook_id) !== 'ebook') {
            wp_send_json_error(array('message' => __('Invalid ebook.', 'skillscore-ebook')));
        }

        // Check if audio preview is enabled for this ebook
        $enable_audio = get_post_meta($ebook_id, '_ebook_enable_audio', true);
        if (!$enable_audio) {
            wp_send_json_error(array('message' => __('Audio preview not enabled for this ebook.', 'skillscore-ebook')));
        }

        // PRIORITY 1: Check for manually uploaded audio file
        $manual_audio_url = get_post_meta($ebook_id, '_ebook_audio_preview_url', true);
        if (!empty($manual_audio_url) && filter_var($manual_audio_url, FILTER_VALIDATE_URL)) {
            wp_send_json_success(array(
                'audio_url' => $manual_audio_url,
                'source' => 'manual_upload',
                'message' => 'Using custom uploaded audio preview'
            ));
            return;
        }

        // PRIORITY 2: Check for cached auto-generated audio
        $cached_audio = $this->get_cached_audio($ebook_id);
        if ($cached_audio) {
            wp_send_json_success(array(
                'audio_url' => $cached_audio,
                'source' => 'cache'
            ));
            return;
        }

        // PRIORITY 3: Check for global voice sample
        $use_global_voice = get_option('skillscore_ebook_use_global_voice', false);
        if ($use_global_voice) {
            $global_voice = $this->use_global_voice_sample();
            if ($global_voice) {
                wp_send_json_success(array(
                    'audio_url' => $global_voice,
                    'source' => 'global_voice'
                ));
                return;
            }
        }

        // PRIORITY 4: Generate audio or use browser TTS
        $post = get_post($ebook_id);
        $preview_text = $this->get_preview_text($post);

        if (empty($preview_text)) {
            wp_send_json_error(array('message' => __('No preview text available. Please add an excerpt or content to this ebook.', 'skillscore-ebook')));
        }

        $result = $this->generate_audio($ebook_id, $preview_text);

        if ($result['success']) {
            wp_send_json_success(array(
                'audio_url' => $result['audio_url'],
                'source' => $result['source'],
                'text' => isset($result['text']) ? $result['text'] : null
            ));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }

    /**
     * Get preview text from ebook
     */
    private function get_preview_text($post) {
        // Prioritize excerpt
        if (!empty($post->post_excerpt)) {
            return wp_strip_all_tags($post->post_excerpt);
        }

        // Fall back to first 500 characters of content
        if (!empty($post->post_content)) {
            $content = wp_strip_all_tags($post->post_content);
            return substr($content, 0, 500);
        }

        return '';
    }

    /**
     * Get cached audio preview
     */
    private function get_cached_audio($ebook_id) {
        $upload_dir = wp_upload_dir();
        
        // Check for MP3
        $mp3_file = $upload_dir['basedir'] . '/skillscore-audio/preview-' . $ebook_id . '.mp3';
        if (file_exists($mp3_file)) {
            return $upload_dir['baseurl'] . '/skillscore-audio/preview-' . $ebook_id . '.mp3';
        }

        // Check for WAV
        $wav_file = $upload_dir['basedir'] . '/skillscore-audio/preview-' . $ebook_id . '.wav';
        if (file_exists($wav_file)) {
            return $upload_dir['baseurl'] . '/skillscore-audio/preview-' . $ebook_id . '.wav';
        }

        return false;
    }

    /**
     * Generate audio preview
     */
    private function generate_audio($ebook_id, $text) {
        // Get TTS engine setting
        $tts_engine = get_option('skillscore_ebook_tts_engine', 'web_speech');

        switch ($tts_engine) {
            case 'piper':
                return $this->generate_with_piper($ebook_id, $text);
                
            case 'coqui':
                return $this->generate_with_coqui($ebook_id, $text);
                
            case 'web_speech':
            default:
                // Always fall back to browser TTS
                return $this->use_browser_tts($ebook_id, $text);
        }
    }

    /**
     * Use global voice sample
     */
    private function use_global_voice_sample() {
        $global_voice_url = get_option('skillscore_ebook_global_voice_url', '');

        if (!empty($global_voice_url) && filter_var($global_voice_url, FILTER_VALIDATE_URL)) {
            return $global_voice_url;
        }

        return false;
    }

    /**
     * Generate audio with Piper TTS
     */
    private function generate_with_piper($ebook_id, $text) {
        $piper_executable = get_option('skillscore_ebook_piper_path', '/usr/local/bin/piper');
        $piper_model = get_option('skillscore_ebook_piper_model', 'en_US-lessac-medium');

        if (!file_exists($piper_executable)) {
            // Fall back to browser TTS
            return $this->use_browser_tts($ebook_id, $text);
        }

        $upload_dir = wp_upload_dir();
        $audio_dir = $upload_dir['basedir'] . '/skillscore-audio';
        
        if (!file_exists($audio_dir)) {
            wp_mkdir_p($audio_dir);
        }

        $audio_file = $audio_dir . '/preview-' . $ebook_id . '.wav';
        $text_file = $audio_dir . '/temp-' . $ebook_id . '.txt';

        file_put_contents($text_file, $text);

        $command = sprintf(
            '%s --model %s --output_file %s < %s 2>&1',
            escapeshellarg($piper_executable),
            escapeshellarg($piper_model),
            escapeshellarg($audio_file),
            escapeshellarg($text_file)
        );

        exec($command, $output, $return_var);

        if (file_exists($text_file)) {
            unlink($text_file);
        }

        if ($return_var === 0 && file_exists($audio_file)) {
            $mp3_file = $audio_dir . '/preview-' . $ebook_id . '.mp3';
            if ($this->convert_to_mp3($audio_file, $mp3_file)) {
                unlink($audio_file);
                return array(
                    'success' => true,
                    'audio_url' => $upload_dir['baseurl'] . '/skillscore-audio/preview-' . $ebook_id . '.mp3',
                    'source' => 'piper_tts'
                );
            }

            return array(
                'success' => true,
                'audio_url' => $upload_dir['baseurl'] . '/skillscore-audio/preview-' . $ebook_id . '.wav',
                'source' => 'piper_tts'
            );
        }

        // Fall back to browser TTS
        return $this->use_browser_tts($ebook_id, $text);
    }

    /**
     * Generate audio with Coqui TTS
     */
    private function generate_with_coqui($ebook_id, $text) {
        $coqui_api_url = get_option('skillscore_ebook_coqui_api_url', 'http://localhost:5002/api/tts');

        $upload_dir = wp_upload_dir();
        $audio_dir = $upload_dir['basedir'] . '/skillscore-audio';
        
        if (!file_exists($audio_dir)) {
            wp_mkdir_p($audio_dir);
        }

        $audio_file = $audio_dir . '/preview-' . $ebook_id . '.wav';

        $response = wp_remote_post($coqui_api_url, array(
            'body' => json_encode(array('text' => $text)),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 60,
        ));

        if (!is_wp_error($response)) {
            $audio_data = wp_remote_retrieve_body($response);
            if (!empty($audio_data)) {
                file_put_contents($audio_file, $audio_data);

                $mp3_file = $audio_dir . '/preview-' . $ebook_id . '.mp3';
                if ($this->convert_to_mp3($audio_file, $mp3_file)) {
                    unlink($audio_file);
                    return array(
                        'success' => true,
                        'audio_url' => $upload_dir['baseurl'] . '/skillscore-audio/preview-' . $ebook_id . '.mp3',
                        'source' => 'coqui_tts'
                    );
                }

                return array(
                    'success' => true,
                    'audio_url' => $upload_dir['baseurl'] . '/skillscore-audio/preview-' . $ebook_id . '.wav',
                    'source' => 'coqui_tts'
                );
            }
        }

        // Fall back to browser TTS
        return $this->use_browser_tts($ebook_id, $text);
    }

    /**
     * Use browser TTS - ALWAYS succeeds
     */
    private function use_browser_tts($ebook_id, $text) {
        update_post_meta($ebook_id, '_ebook_preview_text', $text);
        
        return array(
            'success' => true,
            'audio_url' => 'browser_tts',
            'source' => 'browser_tts',
            'text' => substr($text, 0, 500)
        );
    }

    /**
     * Convert audio to MP3
     */
    private function convert_to_mp3($input_file, $output_file) {
        $ffmpeg = get_option('skillscore_ebook_ffmpeg_path', '/usr/bin/ffmpeg');

        if (!file_exists($ffmpeg)) {
            return false;
        }

        $command = sprintf(
            '%s -i %s -codec:a libmp3lame -qscale:a 2 %s 2>&1',
            escapeshellarg($ffmpeg),
            escapeshellarg($input_file),
            escapeshellarg($output_file)
        );

        exec($command, $output, $return_var);

        return $return_var === 0 && file_exists($output_file);
    }

    /**
     * Clear audio cache for an ebook
     */
    public function clear_audio_cache($ebook_id) {
        $upload_dir = wp_upload_dir();
        $audio_dir = $upload_dir['basedir'] . '/skillscore-audio';

        $files = array(
            $audio_dir . '/preview-' . $ebook_id . '.mp3',
            $audio_dir . '/preview-' . $ebook_id . '.wav',
            $audio_dir . '/temp-' . $ebook_id . '.txt',
        );

        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Clear manual audio preview
     */
    public function clear_manual_audio_preview($ebook_id) {
        delete_post_meta($ebook_id, '_ebook_audio_preview_url');
        delete_post_meta($ebook_id, '_ebook_audio_preview_id');
    }
}