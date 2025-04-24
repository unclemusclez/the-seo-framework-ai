<?php
namespace TSF_AI_Suggestions;

use DOMDocument;
use DOMXPath;

class AI_Suggestions {

    private $endpoint;
    private $api_key;
    private $max_tokens;
    private $temperature;
    private $allow_unverified_ssl;
    private $system_prompt;

    public function __construct() {
        $options = get_option('tsf_ai_suggestions_settings', []);
        $this->endpoint = $options['endpoint'] ?? 'https://api.openai.com/v1/chat/completions';
        $this->api_key = $options['api_key'] ?? '';
        $this->max_tokens = $options['max_tokens'] ?? 500;
        $this->temperature = $options['temperature'] ?? 0.7;
        $this->allow_unverified_ssl = $options['allow_unverified_ssl'] ?? 0;
        $this->system_prompt = $options['system_prompt'] ?? 'Improve this text:';
        $this->init_ajax();
    }

    public function process_content($content) {
        // Preprocess to normalize spaces (optional, if AI struggles with TSF’s output)
        $content = preg_replace('/\s+/', ' ', trim($content));
        
        $suggested_content = $this->get_ai_suggestion($content);
        if (!$suggested_content) {
            return $content;
        }
        return $this->calculate_diff($content, $suggested_content);
    }
    private function get_ai_suggestion($original) {
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => $this->system_prompt],
                ['role' => 'user', 'content' => $original],
            ],
            'max_tokens' => (int)$this->max_tokens,
            'temperature' => (float)$this->temperature,
        ];

        $response = $this->make_api_request($data);
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        }
        return null;
    }

    private function make_api_request($data) {
        $headers = ['Content-Type' => 'application/json'];
        if ($this->api_key) {
            $headers['Authorization'] = "Bearer $this->api_key";
        }

        $args = [
            'body' => json_encode($data),
            'headers' => $headers,
            'timeout' => 30,
            'sslverify' => !$this->allow_unverified_ssl,
        ];

        $response = wp_remote_post($this->endpoint, $args);
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('TSF AI Suggestions: API request failed: ' . $response->get_error_message());
            }
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($decoded['choices'][0]['message']['content'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('TSF AI Suggestions: Invalid API response: ' . $body);
            }
            return null;
        }

        return $decoded;
    }

    private function calculate_diff($original, $suggested) {
        if (!class_exists('Text_Diff')) {
            require_once ABSPATH . '/wp-includes/wp-diff.php';
        }

        $left_lines = explode("\n", $original);
        $right_lines = explode("\n", $suggested);

        $text_diff = new \Text_Diff($left_lines, $right_lines);
        $renderer = class_exists('WPSEO_HTML_Diff_Renderer') ? new \WPSEO_HTML_Diff_Renderer() : new \Text_Diff_Renderer();
        $diff = $renderer->render($text_diff);

        $diff = str_replace(["\n", "\\n"], '', $diff);
        return $this->serialize_diff($diff);
    }

    private function serialize_diff($diff) {
        $dom = new DOMDocument();
        if (!$dom->loadHTML("<html><body>$diff</body></html>", LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('TSF AI Suggestions: Failed to parse HTML diff');
            }
            return $diff;
        }

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//body/*');

        $output = '';
        foreach ($nodes as $node) {
            $output .= $dom->saveHTML($node);
        }

        return html_entity_decode(trim($output), ENT_QUOTES, get_bloginfo('charset'));
    }

    public function get_settings() {
        return [
            'endpoint' => $this->endpoint,
            'api_key' => $this->api_key,
            'max_tokens' => $this->max_tokens,
            'temperature' => $this->temperature,
            'allow_unverified_ssl' => $this->allow_unverified_ssl,
            'system_prompt' => $this->system_prompt,
        ];
    }

    public function ajax_get_suggestion() {
        check_ajax_referer('tsf_ai_suggestions_nonce', 'nonce');
        if (!current_user_can('manage_options')) { // More restrictive capability
            wp_send_json_error('Permission denied');
        }

        $content = sanitize_text_field($_POST['content'] ?? '');
        if (!$content) {
            wp_send_json_error('No content provided');
        }

        $suggestion = $this->process_content($content);
        wp_send_json_success(['suggestion' => $suggestion]);
    }

    public function init_ajax() {
        add_action('wp_ajax_tsf_ai_get_suggestion', [$this, 'ajax_get_suggestion']);
    }
}