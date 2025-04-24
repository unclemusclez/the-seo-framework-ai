<?php
namespace TSF_AI_Suggestions;

class Settings {
    private $ai_suggestions;

    public function __construct(AI_Suggestions $ai_suggestions) {
        $this->ai_suggestions = $ai_suggestions;
    }

    public function init() {
        add_action('admin_menu', [$this, 'register_settings'], 11);
        add_action('admin_init', [$this, 'register_settings_fields']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('the_seo_framework_metabox_after', [$this, 'add_suggestion_button'], 10);
        add_action('the_seo_framework_after_post_edit_metabox', [$this, 'add_suggestion_button'], 10);
        add_action('the_seo_framework_after_term_edit_metabox', [$this, 'add_suggestion_button'], 10);
        $this->apply_filters();
    }

    public function register_settings() {
        $capability = defined('TSF_EXTENSION_MANAGER_MAIN_ADMIN_ROLE') ? TSF_EXTENSION_MANAGER_MAIN_ADMIN_ROLE : 'manage_options';
        if (!current_user_can($capability)) {
            return;
        }

        add_submenu_page(
            'theseoframework-settings',
            'AI Suggestions Settings',
            'AI Suggestions',
            $capability,
            'tsf-ai-suggestions',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings_fields() {
        register_setting(
            'tsf_ai_suggestions_settings_group',
            'tsf_ai_suggestions_settings',
            [$this, 'sanitize_settings']
        );
    }

    public function sanitize_settings($input) {
        $output = [];
        $output['endpoint'] = esc_url_raw($input['endpoint'] ?? '');
        $output['api_key'] = sanitize_text_field($input['api_key'] ?? '');
        $output['max_tokens'] = absint($input['max_tokens'] ?? 500);
        $output['temperature'] = max(0, min(2, floatval($input['temperature'] ?? 0.7)));
        $output['enable_description'] = isset($input['enable_description']) ? 1 : 0;
        $output['enable_title'] = isset($input['enable_title']) ? 1 : 0;
        $output['enable_ai_description_generation'] = isset($input['enable_ai_description_generation']) ? 1 : 0;
        $output['override_custom_descriptions'] = isset($input['override_custom_descriptions']) ? 1 : 0; // New setting
        $output['allow_unverified_ssl'] = isset($input['allow_unverified_ssl']) ? 1 : 0;
        $output['system_prompt'] = sanitize_text_field($input['system_prompt'] ?? 'Improve this text:');
        return $output;
    }
    public function render_settings_page() {
        $options = get_option('tsf_ai_suggestions_settings', $this->ai_suggestions->get_settings() + [
            'enable_description' => 0,
            'enable_title' => 0,
            'enable_ai_description_generation' => 0, // New setting
            'allow_unverified_ssl' => 0,
            'system_prompt' => 'Improve this text:',
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
                        <th><label for="tsf_ai_enable_ai_description_generation">Enable AI Description Generation</label></th>
                        <td>
                            <input type="checkbox" name="tsf_ai_suggestions_settings[enable_ai_description_generation]" id="tsf_ai_enable_ai_description_generation" value="1" <?php checked($options['enable_ai_description_generation'], 1); ?> />
                            <p class="description">When enabled, AI-generated descriptions replace The SEO Framework's automated descriptions.</p>
                        </td>
                    </tr>
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
                        <th><label for="tsf_ai_system_prompt">System Prompt</label></th>
                        <td>
                            <input type="text" name="tsf_ai_suggestions_settings[system_prompt]" id="tsf_ai_system_prompt" value="<?php echo esc_attr($options['system_prompt']); ?>" class="regular-text" />
                            <p class="description">Enter a custom prompt to guide the AI (e.g., "Rewrite in a formal tone").</p>
                        </td>
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
                        <td>
                            <input type="checkbox" name="tsf_ai_suggestions_settings[allow_unverified_ssl]" id="tsf_ai_allow_unverified_ssl" value="1" <?php checked($options['allow_unverified_ssl'], 1); ?> />
                            <small>(Enable if using a self-signed SSL certificate)</small>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="tsf_ai_override_custom_descriptions">Override Custom Descriptions with AI</label></th>
                        <td>
                            <input type="checkbox" name="tsf_ai_suggestions_settings[override_custom_descriptions]" id="tsf_ai_override_custom_descriptions" value="1" <?php checked($options['override_custom_descriptions'], 1); ?> />
                            <p class="description">When enabled, AI suggestions replace custom descriptions set in The SEO Framework.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts($hook) {
        $allowed_hooks = ['post.php', 'post-new.php', 'edit-tags.php', 'term.php', 'toplevel_page_theseoframework-settings'];
        if (!in_array($hook, $allowed_hooks, true)) {
            return;
        }

        $script_url = plugin_dir_url(__DIR__) . 'assets/js/ai-suggestions.js';
        wp_enqueue_script(
            'tsf-ai-suggestions',
            $script_url,
            ['jquery'],
            '1.0.0',
            true
        );

        $style_url = plugin_dir_url(__DIR__) . 'assets/css/ai-suggestions.css';
        wp_enqueue_style(
            'tsf-ai-suggestions',
            $style_url,
            [],
            '1.0.0'
        );

        $options = get_option('tsf_ai_suggestions_settings', []);
        wp_localize_script(
            'tsf-ai-suggestions',
            'tsfAiSettings',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tsf_ai_suggestions_nonce'),
                'isAiGenerationEnabled' => !empty($options['enable_ai_description_generation']),
            ]
        );
    }

    public function add_suggestion_button() {
        if (!in_array(get_current_screen()->base, ['post', 'term', 'edit-tags'], true)) {
            return;
        }
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
            'enable_ai_description_generation' => 0,
            'override_custom_descriptions' => 0,
        ]);

        if ($options['enable_ai_description_generation']) {
            add_filter('the_seo_framework_custom_field_description', function ($desc, $args) use ($options) {
                if (is_admin() && !wp_doing_ajax()) {
                    return $desc;
                }

                // If a custom description exists and override is disabled, return it
                if ($desc && !$options['override_custom_descriptions']) {
                    return $desc;
                }

                $input = $desc ?: \The_SEO_Framework\Meta\Description\Excerpt::get_excerpt($args);
                if (!$input) {
                    return $desc;
                }

                return $this->ai_suggestions->process_content($input);
            }, 10, 2);

            add_filter('the_seo_framework_description_excerpt', function ($excerpt, $args, $type) use ($options) {
                if (is_admin() && !wp_doing_ajax()) {
                    return $excerpt;
                }
                if (!$options['enable_description']) {
                    return $excerpt;
                }
                return $this->ai_suggestions->process_content($excerpt);
            }, 10, 3);
        }

        if ($options['enable_title']) {
            add_filter('the_seo_framework_title_from_generation', function ($title, $args) {
                if (is_admin() && !wp_doing_ajax()) {
                    return $title;
                }
                return $this->ai_suggestions->process_content($title);
            }, 10, 2);
        }
    }
}