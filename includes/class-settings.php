<?php
namespace TSF_AI_Suggestions;

class Settings {

    private $ai_suggestions;

    public function __construct(AI_Suggestions $ai_suggestions) {
        $this->ai_suggestions = $ai_suggestions;
    }

    public function init() {
        add_action('the_seo_framework_after_admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('the_seo_framework_metabox_after_fields', [$this, 'add_suggestion_button'], 10, 2);
        $this->apply_filters();
    }

    public function register_settings() {
        $tsf = tsf();

        $tsf->add_option_filter('tsf_ai_suggestions_settings', [$this, 'sanitize_settings']);
        $tsf->add_menu_page(
            [
                'page_title' => 'AI Suggestions Settings',
                'menu_title' => 'AI Suggestions',
                'capability' => 'manage_options',
                'menu_slug' => 'tsf-ai-suggestions',
                'callback' => [$this, 'render_settings_page'],
            ],
            'seo-settings'
        );
    }

    public function sanitize_settings($input) {
        $input['endpoint'] = esc_url_raw($input['endpoint']);
        $input['api_key'] = sanitize_text_field($input['api_key']);
        $input['max_tokens'] = absint($input['max_tokens']);
        $input['temperature'] = max(0, min(2, floatval($input['temperature'])));
        $input['enable_description'] = isset($input['enable_description']) ? 1 : 0;
        $input['enable_title'] = isset($input['enable_title']) ? 1 : 0;
        $input['allow_unverified_ssl'] = isset($input['allow_unverified_ssl']) ? 1 : 0; // New checkbox
        return $input;
    }

    public function render_settings_page() {
        $options = get_option('tsf_ai_suggestions_settings', $this->ai_suggestions->get_settings() + [
            'enable_description' => 0,
            'enable_title' => 0,
            'allow_unverified_ssl' => 0, // Default to off
        ]);
        ?>
        <div class="wrap">
            <h1>AI Suggestions Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('tsf_ai_suggestions_settings_group');
                do_settings_sections('tsf_ai_suggestions_settings_group');
                ?>
                <table class="form-table">
                    <tr>
                        <th><label for="tsf_ai_endpoint">API Endpoint</label></th>
                        <td><input type="url" name="tsf_ai_suggestions_settings[endpoint]" id="tsf_ai_endpoint" value="<?php echo esc_attr($options['endpoint']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="tsf_ai_api_key">API Key (Optional)</label></th>
                        <td><input type="text" name="tsf_ai_suggestions_settings[api_key]" id="tsf_ai_api_key" value="<?php echo esc_attr($options['api_key']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="tsf_ai_max_tokens">Max Tokens</label></th>
                        <td><input type="number" name="tsf_ai_suggestions_settings[max_tokens]" id="tsf_ai_max_tokens" value="<?php echo esc_attr($options['max_tokens']); ?>" min="1" /></td>
                    </tr>
                    <tr>
                        <th><label for="tsf_ai_temperature">Temperature</label></th>
                        <td><input type="number" step="0.1" name="tsf_ai_suggestions_settings[temperature]" id="tsf_ai_temperature" value="<?php echo esc_attr($options['temperature']); ?>" min="0" max="2" /></td>
                    </tr>
                    <tr>
                        <th><label for="tsf_ai_enable_description">Enable Description Suggestions</label></th>
                        <td><input type="checkbox" name="tsf_ai_suggestions_settings[enable_description]" id="tsf_ai_enable_description" value="1" <?php checked($options['enable_description'], 1); ?> /></td>
                    </tr>
                    <tr>
                        <th><label for="tsf_ai_enable_title">Enable Title Suggestions</label></th>
                        <td><input type="checkbox" name="tsf_ai_suggestions_settings[enable_title]" id="tsf_ai_enable_title" value="1" <?php checked($options['enable_title'], 1); ?> /></td>
                    </tr>
                    <tr>
                        <th><label for="tsf_ai_allow_unverified_ssl">Allow Unverified SSL</label></th>
                        <td><input type="checkbox" name="tsf_ai_suggestions_settings[allow_unverified_ssl]" id="tsf_ai_allow_unverified_ssl" value="1" <?php checked($options['allow_unverified_ssl'], 1); ?> /> <small>(Enable if using a self-signed SSL certificate)</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
        register_setting('tsf_ai_suggestions_settings_group', 'tsf_ai_suggestions_settings', [$this, 'sanitize_settings']);
    }

    private function apply_filters() {
        $options = get_option('tsf_ai_suggestions_settings', [
            'enable_description' => 0,
            'enable_title' => 0,
        ]);

        if ($options['enable_description']) {
            add_filter('the_seo_framework_description_excerpt', function ($excerpt, $args) {
                $ai = new AI_Suggestions();
                return $ai->process_content($excerpt);
            }, 10, 2);
        }

        if ($options['enable_title']) {
            add_filter('the_seo_framework_title_from_generation', function ($title, $args) {
                $ai = new AI_Suggestions();
                return $ai->process_content($title);
            }, 10, 2);
        }
    }

    public function enqueue_scripts($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        wp_enqueue_script(
            'tsf-ai-suggestions',
            plugin_dir_url(__FILE__) . '../assets/js/ai-suggestions.js',
            ['jquery'],
            '1.0.0',
            true
        );
        wp_localize_script(
            'tsf-ai-suggestions',
            'tsfAiSettings',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tsf_ai_suggestions_nonce'),
            ]
        );
    }

    public function add_suggestion_button($post_id, $context) {
        ?>
        <div class="tsf-ai-suggestions">
            <button type="button" class="button button-primary" id="tsf-ai-suggest">Get AI Suggestions</button>
            <div id="tsf-ai-suggestion-result"></div>
        </div>
        <?php
    }
}