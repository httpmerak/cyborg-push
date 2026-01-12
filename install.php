<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Create push subscriptions table
if (!$CI->db->table_exists(db_prefix() . 'cyborg_push_subscriptions')) {
    $CI->db->query("
        CREATE TABLE `" . db_prefix() . "cyborg_push_subscriptions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) DEFAULT NULL COMMENT 'Staff user ID',
            `contact_id` INT(11) DEFAULT NULL COMMENT 'Client contact ID',
            `endpoint` TEXT NOT NULL,
            `p256dh` VARCHAR(255) NOT NULL,
            `auth` VARCHAR(255) NOT NULL,
            `user_agent` VARCHAR(500) DEFAULT NULL,
            `device_type` VARCHAR(50) DEFAULT 'web',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `contact_id` (`contact_id`),
            KEY `is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";
    ");
}

// Create push notifications log table
if (!$CI->db->table_exists(db_prefix() . 'cyborg_push_logs')) {
    $CI->db->query("
        CREATE TABLE `" . db_prefix() . "cyborg_push_logs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `subscription_id` INT(11) DEFAULT NULL,
            `user_id` INT(11) DEFAULT NULL,
            `contact_id` INT(11) DEFAULT NULL,
            `notification_type` VARCHAR(100) DEFAULT NULL,
            `title` VARCHAR(255) DEFAULT NULL,
            `body` TEXT DEFAULT NULL,
            `payload` TEXT DEFAULT NULL,
            `status` ENUM('pending', 'sent', 'failed', 'expired') NOT NULL DEFAULT 'pending',
            `error_message` TEXT DEFAULT NULL,
            `sent_at` DATETIME DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `subscription_id` (`subscription_id`),
            KEY `user_id` (`user_id`),
            KEY `status` (`status`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";
    ");
}

// Add module options
$options = [
    ['name' => 'cyborg_push_enabled', 'value' => '0'],
    ['name' => 'cyborg_push_clients_enabled', 'value' => '0'],
    ['name' => 'cyborg_push_vapid_public_key', 'value' => ''],
    ['name' => 'cyborg_push_vapid_private_key', 'value' => ''],
    ['name' => 'cyborg_push_vapid_subject', 'value' => ''],
    ['name' => 'cyborg_push_default_icon', 'value' => ''],
    ['name' => 'cyborg_push_default_badge', 'value' => ''],
    ['name' => 'cyborg_push_fcm_enabled', 'value' => '0'],
    ['name' => 'cyborg_push_fcm_server_key', 'value' => ''],
    ['name' => 'cyborg_push_fcm_sender_id', 'value' => ''],
    ['name' => 'cyborg_push_onesignal_enabled', 'value' => '0'],
    ['name' => 'cyborg_push_onesignal_app_id', 'value' => ''],
    ['name' => 'cyborg_push_onesignal_api_key', 'value' => ''],
    ['name' => 'cyborg_push_log_retention_days', 'value' => '30'],
    ['name' => 'cyborg_push_disable_pusher', 'value' => '0'],
];

foreach ($options as $option) {
    if (get_option($option['name']) === false) {
        add_option($option['name'], $option['value']);
    }
}
