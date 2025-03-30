// Inside the AI_Suggestions class.
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

// Call this in the constructor:
public function __construct() {
    $options = get_option('tsf_ai_suggestions_settings', []);
    $this->endpoint = $options['endpoint'] ?? 'http://localhost:8080/v1/completions';
    $this->api_key = $options['api_key'] ?? '';
    $this->max_tokens = $options['max_tokens'] ?? 500;
    $this->temperature = $options['temperature'] ?? 0.7;
    $this->init_ajax();
}