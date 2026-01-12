<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Check if Cyborg Push module is enabled
 * 
 * @return bool
 */
function is_cyborg_push_enabled()
{
    return get_option('cyborg_push_enabled') == '1';
}

/**
 * Check if Cyborg Push is enabled for clients
 * 
 * @return bool
 */
function is_cyborg_push_clients_enabled()
{
    return is_cyborg_push_enabled() && get_option('cyborg_push_clients_enabled') == '1';
}

/**
 * Get module assets URL
 * 
 * @param string $path
 * @return string
 */
function cyborg_push_assets_url($path = '')
{
    return module_dir_url(CYBORG_PUSH_MODULE_NAME, 'assets/' . $path);
}

/**
 * Send push notification to specific user
 * 
 * @param int $user_id Staff user ID
 * @param string $title Notification title
 * @param string $body Notification body
 * @param array $data Additional data
 * @return bool
 */
function cyborg_push_notify_user($user_id, $title, $body, $data = [])
{
    if (!is_cyborg_push_enabled()) {
        return false;
    }
    
    $CI = &get_instance();
    $CI->load->library(CYBORG_PUSH_MODULE_NAME . '/Cyborg_push_sender');
    
    return $CI->cyborg_push_sender->send_to_user($user_id, [
        'title' => $title,
        'body'  => $body,
        'data'  => $data
    ]);
}

/**
 * Send push notification to specific contact
 * 
 * @param int $contact_id Contact ID
 * @param string $title Notification title
 * @param string $body Notification body
 * @param array $data Additional data
 * @return bool
 */
function cyborg_push_notify_contact($contact_id, $title, $body, $data = [])
{
    if (!is_cyborg_push_clients_enabled()) {
        return false;
    }
    
    $CI = &get_instance();
    $CI->load->library(CYBORG_PUSH_MODULE_NAME . '/Cyborg_push_sender');
    
    return $CI->cyborg_push_sender->send_to_contact($contact_id, [
        'title' => $title,
        'body'  => $body,
        'data'  => $data
    ]);
}

/**
 * Send push notification to multiple users
 * 
 * @param array $user_ids Array of staff user IDs
 * @param string $title Notification title
 * @param string $body Notification body
 * @param array $data Additional data
 * @return array Results for each user
 */
function cyborg_push_notify_users($user_ids, $title, $body, $data = [])
{
    $results = [];
    
    foreach ($user_ids as $user_id) {
        $results[$user_id] = cyborg_push_notify_user($user_id, $title, $body, $data);
    }
    
    return $results;
}

/**
 * Get user's push subscriptions
 * 
 * @param int $user_id Staff user ID
 * @return array
 */
function cyborg_push_get_user_subscriptions($user_id)
{
    $CI = &get_instance();
    $CI->db->where('user_id', $user_id);
    $CI->db->where('is_active', 1);
    return $CI->db->get(db_prefix() . 'cyborg_push_subscriptions')->result_array();
}

/**
 * Get contact's push subscriptions
 * 
 * @param int $contact_id Contact ID
 * @return array
 */
function cyborg_push_get_contact_subscriptions($contact_id)
{
    $CI = &get_instance();
    $CI->db->where('contact_id', $contact_id);
    $CI->db->where('is_active', 1);
    return $CI->db->get(db_prefix() . 'cyborg_push_subscriptions')->result_array();
}

/**
 * Generate VAPID keys
 * 
 * @return array|false Array with publicKey and privateKey or false on failure
 */
function cyborg_push_generate_vapid_keys()
{
    try {
        // Check if web-push library is available
        if (!class_exists('Minishlink\WebPush\VAPID')) {
            return false;
        }
        
        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
        
        return [
            'publicKey'  => $keys['publicKey'],
            'privateKey' => $keys['privateKey']
        ];
    } catch (Exception $e) {
        log_message('error', 'Cyborg Push - Error generating VAPID keys: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if web push library is installed
 * 
 * @return bool
 */
function cyborg_push_is_webpush_available()
{
    return class_exists('Minishlink\WebPush\WebPush');
}

/**
 * Log push notification
 * 
 * @param array $data Log data
 * @return int|false Insert ID or false
 */
function cyborg_push_log($data)
{
    $CI = &get_instance();
    
    $log = [
        'subscription_id'   => $data['subscription_id'] ?? null,
        'user_id'           => $data['user_id'] ?? null,
        'contact_id'        => $data['contact_id'] ?? null,
        'notification_type' => $data['notification_type'] ?? 'general',
        'title'             => $data['title'] ?? '',
        'body'              => $data['body'] ?? '',
        'payload'           => isset($data['payload']) ? json_encode($data['payload']) : null,
        'status'            => $data['status'] ?? 'pending',
        'error_message'     => $data['error_message'] ?? null,
        'sent_at'           => $data['sent_at'] ?? null,
        'created_at'        => date('Y-m-d H:i:s')
    ];
    
    $CI->db->insert(db_prefix() . 'cyborg_push_logs', $log);
    
    return $CI->db->insert_id();
}
