<?php
namespace TSF_AI_Suggestions;

class Settings {

    private $ai_suggestions;

    public function __construct(AI_Suggestions $ai_suggestions) {
        $this->ai_suggestions = $ai_suggestions;
    }

    public function init() {
        error_log('TSF AI Suggestions: Settings::init called');
        add_action('admin_menu', [$this, 'register_settings'], 11);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('the_seo_framework_metabox_after', [$this, 'add_suggestion_button'], 10);
        add_action('the_seo_framework_after_post_edit_metabox', [$this, 'add_suggestion_button'], 10);
        add_action('the_seo_framework_after_term_edit_metabox', [$this, 'add_suggestion_button'], 10);
        $this->apply_filters();
    }

    public function register_settings() {
        error_log('TSF AI Suggestions: register_settings called');
        $capability = defined('TSF_EXTENSION_MANAGER_MAIN_ADMIN_ROLE') ? TSF_EXTENSION_MANAGER_MAIN_ADMIN_ROLE : 'manage_options';
        if (!current_user_can($capability)) {
            error_log('TSF AI Suggestions: User lacks capability: ' . $capability);
            return;
        }

        // Replace tsf()->add_option_filter with filter hook
        add_filter('the_seo_framework_settings_update_sanitizers', function ($sanitizers) {
            $sanitizers['tsf_ai_suggestions_settings'] = [$this, 'sanitize_settings'];
            return $sanitizers;
        });

        // Use WordPress add_submenu_page instead of tsf()->add_menu_page
        $page = add_submenu_page(
            'theseoframework-settings', // TSF’s main menu slug from logs
            'AI Suggestions Settings',
            'AI Suggestions',
            $capability,
            'tsf-ai-suggestions',
            [$this, 'render_settings_page']
        );
        error_log('TSF AI Suggestions: Menu page added under theseoframework-settings, result: ' . ($page ? $page : 'failed'));
    }

    public function sanitize_settings($input) {
        $input['endpoint'] = esc_url_raw($input['endpoint']);
        $input['api_key'] = sanitize_text_field($input['api_key']);
        $input['max_tokens'] = absint($input['max_tokens']);
        $input['temperature'] = max(0, min(2, floatval($input['temperature'])));
        $input['enable_description'] = isset($input['enable_description']) ? 1 : 0;
        $input['enable_title'] = isset($input['enable_title']) ? 1 : 0;
        $input['allow_unverified_ssl'] = isset($input['allow_unverified_ssl']) ? 1 : 0;
        return $input;
    }

    public function render_settings_page() {
        $options = get_option('tsf_ai_suggestions_settings', $this->ai_suggestions->get_settings() + [
            'enable_description' => 0,
            'enable_title' => 0,
            'allow_unverified_ssl' => 0,
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

    public function enqueue_scripts($hook) {
        error_log("TSF AI Suggestions: enqueue_scripts called with hook: $hook");
        if (!in_array($hook, ['post.php', 'post-new.php', 'edit-tags.php', 'term.php'], true)) {
            return;
        }

        $script_url = plugin_dir_url(__DIR__) . 'assets/js/ai-suggestions.js';
        error_log("TSF AI Suggestions: Enqueueing script at: $script_url");

        wp_enqueue_script(
            'tsf-ai-suggestions',
            $script_url,
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
        error_log('TSF AI Suggestions: Scripts enqueued');
    }

    public function add_suggestion_button() {
        global $hook_suffix;
        error_log("TSF AI Suggestions: add_suggestion_button called on hook: $hook_suffix");
        ?>
        <div class="tsf-ai-suggestions">
            <button type="button" class="button button-primary" id="tsf-ai-suggest">Get AI Suggestions</button>
            <div id="tsf-ai-suggestion-result"></div>
        </div>
        <?php
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
}