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
    private $system_prompt; // New property

    public function __construct() {
        $options = get_option('tsf_ai_suggestions_settings', []);
        $this->endpoint = $options['endpoint'] ?? 'https://openai.com/v1/completions';
        $this->api_key = $options['api_key'] ?? '';
        $this->max_tokens = $options['max_tokens'] ?? 500;
        $this->temperature = $options['temperature'] ?? 0.7;
        $this->allow_unverified_ssl = $options['allow_unverified_ssl'] ?? 0;
        $this->system_prompt = $options['system_prompt'] ?? 'Improve this text:'; // New setting
        $this->init_ajax();
    }

    public function process_content($content) {
        $suggested_content = $this->get_ai_suggestion($content);
        if (!$suggested_content) {
            return $content;
        }
        return $this->calculate_diff($content, $suggested_content);
    }

    private function get_ai_suggestion($original) {
        $prompt = $this->system_prompt . " " . $original; // Use system prompt
        $data = [
            'prompt' => $prompt,
            'max_tokens' => (int)$this->max_tokens,
            'temperature' => (float)$this->temperature,
        ];

        $response = $this->make_api_request($data);
        if ($response && isset($response['choices'][0]['text'])) {
            return trim($response['choices'][0]['text']);
        }
        return null;
    }

    private function make_api_request($data) {
        $args = [
            'body' => json_encode($data),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $this->api_key ? "Bearer $this->api_key" : '',
            ],
            'timeout' => 30,
            'sslverify' => !$this->allow_unverified_ssl,
        ];

        $response = wp_remote_post($this->endpoint, $args);
        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
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
        @$dom->loadHTML("<html><body>$diff</body></html>", LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
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
            'system_prompt' => $this->system_prompt, // Include in settings
        ];
    }

    public function ajax_get_suggestion() {
        check_ajax_referer('tsf_ai_suggestions_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
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
