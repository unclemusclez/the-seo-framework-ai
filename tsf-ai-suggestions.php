<?php
/*
Plugin Name: TSF AI Suggestions
Description: Adds AI-powered content suggestions to The SEO Framework using an OpenAI-compatible endpoint.
Version: 1.0.0
Author: Your Name
Requires Plugins: the-seo-framework
*/

// Prevent direct access.
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/includes/class-ai-suggestions.php';
require_once __DIR__ . '/includes/class-settings.php';

use TSF_AI_Suggestions\AI_Suggestions;
use TSF_AI_Suggestions\Settings;

add_action('plugins_loaded', function () {
    if (!class_exists('The_SEO_Framework\Load')) {
        return; // TSF not active.
    }

    $ai_suggestions = new AI_Suggestions();
    $settings = new Settings($ai_suggestions);
    $settings->init();
});