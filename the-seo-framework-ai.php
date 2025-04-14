<?php
/*
Plugin Name: The SEO Framework AI Suggestions
Description: Adds AI-powered content suggestions to The SEO Framework using an OpenAI-compatible endpoint.
Version: 1.0.0
Author: Devin J. Dawson
Requires Plugins: the-seo-framework
*/

// Prevent direct access.
if (!defined('ABSPATH')) exit;

// Verify includes
$ai_file = __DIR__ . '/includes/class-ai-suggestions.php';
$settings_file = __DIR__ . '/includes/class-settings.php';

if (!file_exists($ai_file)) {
    return;
}
if (!file_exists($settings_file)) {
    return;
}

require_once $ai_file;
require_once $settings_file;

use TSF_AI_Suggestions\AI_Suggestions;
use TSF_AI_Suggestions\Settings;

add_action('plugins_loaded', function () {
    if (!class_exists('The_SEO_Framework\Load')) {
        return;
    }

    if (defined('THE_SEO_FRAMEWORK_HEADLESS') && THE_SEO_FRAMEWORK_HEADLESS) {
        return;
    }

    $ai_suggestions = new AI_Suggestions();
    $settings = new Settings($ai_suggestions);
    $settings->init();
}, 10);