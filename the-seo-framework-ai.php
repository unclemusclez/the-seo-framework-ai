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

require_once __DIR__ . '/includes/class-ai-suggestions.php';
require_once __DIR__ . '/includes/class-settings.php';

use TSF_AI_Suggestions\AI_Suggestions;
use TSF_AI_Suggestions\Settings;

add_action('plugins_loaded', function () {
    error_log('TSF AI Suggestions: plugins_loaded fired');
    if (!class_exists('The_SEO_Framework\Load')) {
        error_log('TSF AI Suggestions: The SEO Framework not detected');
        return;
    }
    error_log('TSF AI Suggestions: The SEO Framework detected, version: ' . (defined('THE_SEO_FRAMEWORK_VERSION') ? THE_SEO_FRAMEWORK_VERSION : 'unknown'));

    if (defined('THE_SEO_FRAMEWORK_HEADLESS') && THE_SEO_FRAMEWORK_HEADLESS) {
        error_log('TSF AI Suggestions: Headless mode detected, skipping GUI');
        return;
    }

    $ai_suggestions = new AI_Suggestions();
    $settings = new Settings($ai_suggestions);
    $settings->init();
}, 10);