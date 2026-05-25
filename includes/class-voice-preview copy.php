<?php
/**
 * Voice Preview Handler
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Ebook_Voice_Preview {

    /**
     * Get audio preview.
     */
    public function get_audio_preview() {
        check_ajax_referer('skillscore_ebook_nonce', 'nonce');

        $ebook_id = intval($_POST['ebook_id']);

        if (!$ebook_id || get_post_type($ebook_id) !== 'ebook') {
            wp_send_json_error(array('message' => __('Invalid ebook.', 'skillscore-ebook')));
        }

        // Check if audio preview is enabled
        $enable_audio = get_post_meta($ebook_id, '_ebook_enable_audio', true);
        if (!$enable_audio) {
            wp_send_json_error(array('message' => __('Audio preview not available.', 'skillscore-ebook')));
        }

        // Check for cached audio
        $cached_audio = $this->get_cached_audio($ebook_id);
        if ($cached_audio) {
            wp_send_json_success(array('audio_url' => $cached_audio));
            return;
        }

        // Get excerpt text for preview
        $post = get_post($ebook_id);
        $preview_text = $this->get_preview_text($post);

        if (empty($preview_text)) {
            wp_send_json_error(array('message' => __('No preview text available.', 'skillscore-ebook')));
        }

        // Generate audio (or use global voice sample)
        $audio_url = $this->generate_audio($ebook_id, $preview_text);

        if ($audio_url) {
            wp_send_json_success(array('audio_url' => $audio_url));
        } else {
            wp_send_json_error(array('message' => __('Failed to generate audio preview.', 'skillscore-ebook')));
        }
    }

    /**
     * Get preview text from ebook.
     */
    private function get_preview_text($post) {
        if (!empty($post->post_excerpt)) {
            return wp_strip_all_tags($post->post_excerpt);
        }

        // Get first 500 characters from content
        $content = wp_strip_all_tags($post->post_content);
        return substr($content, 0, 500);
    }

    /**
     * Get cached audio preview.
     */
    private function get_cached_audio($ebook_id) {
        $upload_dir = wp_upload_dir();
        $audio_dir = $upload_dir['basedir'] . '/skillscore-audio';
        $audio_file = $audio_dir . '/preview-' . $ebook_id . '.mp3';

        if (file_exists($audio_file)) {
            $audio_url = $upload_dir['baseurl'] . '/skillscore-audio/preview-' . $ebook_id . '.mp3';
            return $audio_url;
        }

        return false;
    }

    /**
     * Generate audio preview.
     */
    private function generate_audio($ebook_id, $text) {
        // Check if global voice sample is configured
        $use_global_voice = get_option('skillscore_ebook_use_global_voice', false);

        if ($use_global_voice) {
            return $this->use_global_voice_sample();
        }

        // Check for TTS engine configuration
        $tts_engine = get_option('skillscore_ebook_tts_engine', 'none');

        switch ($tts_engine) {
            case 'piper':
                return $this->generate_with_piper($ebook_id, $text);
            case 'coqui':
                return $this->generate_with_coqui($ebook_id, $text);
            case 'web_speech':
                return $this->use_browser_tts($ebook_id, $text);
            default:
                return $this->use_global_voice_sample();
        }
    }

    /**
     * Use global voice sample for all ebooks.
     */
    private function use_global_voice_sample() {
        $global_voice_url = get_option('skillscore_ebook_global_voice_url', '');

        if (!empty($global_voice_url)) {
            return $global_voice_url;
        }

        return false;
    }

    /**
     * Generate audio with Piper TTS.
     */
    private function generate_with_piper($ebook_id, $text) {
        $piper_executable = get_option('skillscore_ebook_piper_path', '/usr/local/bin/piper');
        $piper_model = get_option('skillscore_ebook_piper_model', 'en_US-lessac-medium');

        if (!file_exists($piper_executable)) {
            return false;
        }

        $upload_dir = wp_upload_dir();
        $audio_dir = $upload_dir['basedir'] . '/skillscore-audio';
        $audio_file = $audio_dir . '/preview-' . $ebook_id . '.wav';

        // Create temporary text file
        $text_file = $audio_dir . '/temp-' . $ebook_id . '.txt';
        file_put_contents($text_file, $text);

        // Execute Piper TTS
        $command = sprintf(
            '%s --model %s --output_file %s < %s 2>&1',
            escapeshellarg($piper_executable),
            escapeshellarg($piper_model),
            escapeshellarg($audio_file),
            escapeshellarg($text_file)
        );

        exec($command, $output, $return_var);

        // Clean up temp file
        if (file_exists($text_file)) {
            unlink($text_file);
        }

        if ($return_var === 0 && file_exists($audio_file)) {
            // Convert WAV to MP3 if ffmpeg is available
            $mp3_file = $audio_dir . '/preview-' . $ebook_id . '.mp3';
            if ($this->convert_to_mp3($audio_file, $mp3_file)) {
                unlink($audio_file);
                return $upload_dir['baseurl'] . '/skillscore-audio/preview-' . $ebook_id . '.mp3';
            }

            return $upload_dir['baseurl'] . '/skillscore-audio/preview-' . $ebook_id . '.wav';
        }

        return false;
    }

    /**
     * Generate audio with Coqui TTS.
     */
    private function generate_with_coqui($ebook_id, $text) {
        $coqui_api_url = get_option('skillscore_ebook_coqui_api_url', 'http://localhost:5002/api/tts');

        $upload_dir = wp_upload_dir();
        $audio_dir = $upload_dir['basedir'] . '/skillscore-audio';
        $audio_file = $audio_dir . '/preview-' . $ebook_id . '.wav';

        $response = wp_remote_post($coqui_api_url, array(
            'body' => json_encode(array('text' => $text)),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $audio_data = wp_remote_retrieve_body($response);
        if (!empty($audio_data)) {
            file_put_contents($audio_file, $audio_data);

            // Convert to MP3
            $mp3_file = $audio_dir . '/preview-' . $ebook_id . '.mp3';
            if ($this->convert_to_mp3($audio_file, $mp3_file)) {
                unlink($audio_file);
                return $upload_dir['baseurl'] . '/skillscore-audio/preview-' . $ebook_id . '.mp3';
            }

            return $upload_dir['baseurl'] . '/skillscore-audio/preview-' . $ebook_id . '.wav';
        }

        return false;
    }

    /**
     * Provide text for browser-based TTS.
     */
    private function use_browser_tts($ebook_id, $text) {
        // Store text for client-side TTS
        update_post_meta($ebook_id, '_ebook_preview_text', $text);
        return 'browser_tts';
    }

    /**
     * Convert audio to MP3.
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
     * Clear audio cache for an ebook.
     */
    public function clear_audio_cache($ebook_id) {
        $upload_dir = wp_upload_dir();
        $audio_dir = $upload_dir['basedir'] . '/skillscore-audio';

        $patterns = array(
            $audio_dir . '/preview-' . $ebook_id . '.mp3',
            $audio_dir . '/preview-' . $ebook_id . '.wav',
        );

        foreach ($patterns as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Upload global voice sample.
     */
    public function upload_global_voice_sample($file) {
        $upload_dir = wp_upload_dir();
        $audio_dir = $upload_dir['basedir'] . '/skillscore-audio';

        if (!file_exists($audio_dir)) {
            wp_mkdir_p($audio_dir);
        }

        $allowed_types = array('mp3', 'wav', 'ogg');
        $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_type, $allowed_types)) {
            return false;
        }

        $filename = 'global-voice-sample.' . $file_type;
        $file_path = $audio_dir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $voice_url = $upload_dir['baseurl'] . '/skillscore-audio/' . $filename;
            update_option('skillscore_ebook_global_voice_url', $voice_url);
            update_option('skillscore_ebook_use_global_voice', true);
            return $voice_url;
        }

        return false;
    }
}
