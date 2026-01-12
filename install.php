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

// =============================================================================
// PATCH: Modificar general_helper.php para chamar Cyborg Push diretamente
// Isso é necessário porque a API não carrega os módulos normalmente
// =============================================================================

$helper_file = APPPATH . 'helpers/general_helper.php';
$helper_content = file_get_contents($helper_file);

// Verificar se o patch já foi aplicado
$patch_marker = '// Cyborg Push - Enviar notificações push nativas';
if (strpos($helper_content, $patch_marker) === false) {
    
    // O código a ser inserido após hooks()->do_action('before_pusher_trigger_notification', $users);
    $cyborg_push_code = '
    // Cyborg Push - Enviar notificações push nativas
    // Isso garante que funcione mesmo quando chamado via API (onde os módulos não são carregados)
    if (!empty($users) && is_array($users) && get_option(\'cyborg_push_enabled\') == \'1\') {
        $CI = &get_instance();
        
        // Carregar helper e library do Cyborg Push se existirem
        $module_path = APPPATH . \'../modules/cyborg_push/\';
        if (is_dir($module_path)) {
            // Carregar helper se não estiver carregado
            if (!function_exists(\'cyborg_push_send_notification_to_user\')) {
                $helper_path = $module_path . \'helpers/cyborg_push_helper.php\';
                if (file_exists($helper_path)) {
                    require_once($helper_path);
                }
            }
            
            // Carregar e usar a biblioteca diretamente
            $CI->load->model(\'cyborg_push/Cyborg_push_model\', \'cyborg_push_model\');
            $CI->load->library(\'cyborg_push/Cyborg_push_sender\', null, \'cyborg_push_sender\');
            
            foreach ($users as $user_id) {
                $CI->cyborg_push_sender->send_to_user($user_id);
            }
        }
    }

';
    
    // Encontrar o ponto de inserção (após o hook)
    $search = "hooks()->do_action('before_pusher_trigger_notification', \$users);";
    $replace = $search . "\n" . $cyborg_push_code;
    
    $new_content = str_replace($search, $replace, $helper_content);
    
    // Salvar o arquivo modificado
    if ($new_content !== $helper_content) {
        // Criar backup
        $backup_file = $helper_file . '.bak.' . date('YmdHis');
        copy($helper_file, $backup_file);
        
        // Aplicar patch
        file_put_contents($helper_file, $new_content);
        
        log_activity('Cyborg Push: Patch applied to general_helper.php (backup: ' . basename($backup_file) . ')');
    }
}

// =============================================================================
// Copiar Service Worker para a raiz do site
// =============================================================================

$sw_source = __DIR__ . '/assets/js/sw.js';
$sw_dest = FCPATH . 'cyborg-push-sw.js';

if (file_exists($sw_source) && !file_exists($sw_dest)) {
    // Ler conteúdo do SW
    $sw_content = file_get_contents($sw_source);
    
    // Escrever na raiz
    file_put_contents($sw_dest, $sw_content);
    
    log_activity('Cyborg Push: Service Worker copied to site root');
}
