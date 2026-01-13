<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Cyborg Push
Module URI: https://cyborgsolutions.com.br
Description: Sistema de notificações push integrado para substituir Pusher. Suporta Web Push (Service Workers), FCM e OneSignal.
Version: 1.0.1
Author: Cyborg Solutions
Author URI: https://cyborgsolutions.com.br
Requires at least: 3.0
*/

// Debug log para verificar se o módulo está sendo carregado
log_message('error', 'Cyborg Push Module: File loaded. API defined: ' . (defined('API') ? 'YES' : 'NO'));

define('CYBORG_PUSH_MODULE_NAME', 'cyborg_push');
define('CYBORG_PUSH_MODULE_PATH', __DIR__);

// Carregar autoload se existir
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Register activation hook
 */
register_activation_hook(CYBORG_PUSH_MODULE_NAME, 'cyborg_push_activation_hook');

function cyborg_push_activation_hook()
{
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files
 */
register_language_files(CYBORG_PUSH_MODULE_NAME, [CYBORG_PUSH_MODULE_NAME]);

/**
 * Initialize module on app_init
 */
hooks()->add_action('app_init', 'cyborg_push_app_init');

function cyborg_push_app_init()
{
    $CI = &get_instance();
    $CI->load->helper(CYBORG_PUSH_MODULE_NAME . '/cyborg_push');
}

/**
 * Initialize menu items and settings in admin
 */
hooks()->add_action('admin_init', 'cyborg_push_init_menu_items');

function cyborg_push_init_menu_items()
{
    $CI = &get_instance();
    
    // Add settings under Integrations section (like wm_api does)
    if (is_admin() || has_permission('settings', '', 'view')) {
        // Add as a child of the existing "integrations" section
        $CI->app->add_settings_section_child('integrations', 'cyborg_push_settings', [
            'name'     => _l('cyborg_push'),
            'view'     => 'cyborg_push/settings_integration',
            'position' => 15, // After Pusher (position 10)
            'icon'     => 'fa fa-bell',
        ]);
    }
    
    // Menu lateral apenas para Subscriptions e Logs
    if (is_admin()) {
        $CI->app_menu->add_sidebar_menu_item('cyborg-push', [
            'collapse' => true,
            'name'     => _l('cyborg_push'),
            'position' => 45,
            'icon'     => 'fa fa-bell',
        ]);
        
        $CI->app_menu->add_sidebar_children_item('cyborg-push', [
            'slug'     => 'cyborg-push-subscriptions',
            'name'     => _l('cyborg_push_subscriptions'),
            'href'     => admin_url('cyborg_push/subscriptions'),
            'position' => 5,
        ]);
        
        $CI->app_menu->add_sidebar_children_item('cyborg-push', [
            'slug'     => 'cyborg-push-logs',
            'name'     => _l('cyborg_push_logs'),
            'href'     => admin_url('cyborg_push/logs'),
            'position' => 10,
        ]);
    }
}

/**
 * Hook into pusher notifications to send push notifications
 * This is the main integration point that replaces/extends Pusher functionality
 */
hooks()->add_action('before_pusher_trigger_notification', 'cyborg_push_send_notification');

function cyborg_push_send_notification($users)
{
    // Debug log
    log_message('error', 'Cyborg Push: Hook called with users: ' . json_encode($users));
    
    if (empty($users) || !is_array($users)) {
        log_message('error', 'Cyborg Push: No users to notify');
        return;
    }
    
    // Check if module is enabled
    if (get_option('cyborg_push_enabled') != '1') {
        log_message('error', 'Cyborg Push: Module is disabled');
        return;
    }
    
    $CI = &get_instance();
    $CI->load->library(CYBORG_PUSH_MODULE_NAME . '/Cyborg_push_sender');
    
    // Send push notification to each user
    foreach ($users as $user_id) {
        log_message('error', 'Cyborg Push: Sending to user ' . $user_id);
        $CI->cyborg_push_sender->send_to_user($user_id);
    }
}

/**
 * Add assets to admin header
 */
hooks()->add_action('app_admin_head', 'cyborg_push_add_head_components');

function cyborg_push_add_head_components()
{
    if (get_option('cyborg_push_enabled') != '1') {
        return;
    }
    
    $CI = &get_instance();
    
    // Add VAPID public key and service worker registration
    // SW must be in root for proper scope
    echo '<script>
        window.CYBORG_PUSH_CONFIG = {
            vapidPublicKey: "' . get_option('cyborg_push_vapid_public_key') . '",
            swPath: "' . site_url('cyborg-push-sw.js') . '",
            subscribeUrl: "' . admin_url('cyborg_push/subscribe') . '",
            unsubscribeUrl: "' . admin_url('cyborg_push/unsubscribe') . '"
        };
    </script>';
}

/**
 * Add service worker registration script to footer
 */
hooks()->add_action('app_admin_footer', 'cyborg_push_add_footer_components');

function cyborg_push_add_footer_components()
{
    if (get_option('cyborg_push_enabled') != '1') {
        return;
    }
    
    $CI = &get_instance();
    echo '<script src="' . module_dir_url(CYBORG_PUSH_MODULE_NAME, 'assets/js/cyborg-push.js') . '"></script>';
}

/**
 * Save Cyborg Push settings when main settings are saved
 */
hooks()->add_action('settings_group_integrations_saved', 'cyborg_push_save_settings');

function cyborg_push_save_settings()
{
    $CI = &get_instance();
    
    // Handle VAPID key generation
    if ($CI->input->post('generate_vapid_keys') == '1') {
        $CI->load->library('cyborg_push/Cyborg_push_vapid');
        $keys = $CI->cyborg_push_vapid->generate_keys();
        
        if ($keys) {
            update_option('cyborg_push_vapid_public_key', $keys['publicKey']);
            update_option('cyborg_push_vapid_private_key', $keys['privateKey']);
        }
    }
    
    // Save settings
    $settings = [
        'cyborg_push_enabled',
        'cyborg_push_vapid_public_key',
        'cyborg_push_vapid_private_key', 
        'cyborg_push_vapid_subject',
        'cyborg_push_default_icon',
        'cyborg_push_default_badge',
        'cyborg_push_log_retention_days',
        'cyborg_push_disable_pusher',
    ];
    
    foreach ($settings as $setting) {
        $value = $CI->input->post($setting);
        if ($value !== null) {
            update_option($setting, $value);
        } elseif (in_array($setting, ['cyborg_push_enabled', 'cyborg_push_disable_pusher'])) {
            update_option($setting, '0');
        }
    }
}
