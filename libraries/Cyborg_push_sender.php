<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cyborg Push Sender Library
 * 
 * Handles sending push notifications via Web Push (VAPID), FCM, and OneSignal
 */
class Cyborg_push_sender
{
    protected $CI;
    protected $webPush;
    
    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('cyborg_push/cyborg_push_model');
    }

    /**
     * Send notification to a staff user
     * 
     * @param int $user_id Staff user ID
     * @param array $notification Optional custom notification data
     * @return bool
     */
    public function send_to_user($user_id, $notification = [])
    {
        $subscriptions = $this->CI->cyborg_push_model->get_user_subscriptions($user_id);
        
        if (empty($subscriptions)) {
            return false;
        }
        
        // Get last notification for this user if no custom notification provided
        if (empty($notification)) {
            $notification = $this->get_last_notification_for_user($user_id);
        }
        
        $success = false;
        
        foreach ($subscriptions as $subscription) {
            $result = $this->send_web_push($subscription, $notification);
            if ($result) {
                $success = true;
            }
        }
        
        // Also send via FCM if enabled
        if (get_option('cyborg_push_fcm_enabled') == '1') {
            $this->send_fcm($user_id, null, $notification);
        }
        
        // Also send via OneSignal if enabled
        if (get_option('cyborg_push_onesignal_enabled') == '1') {
            $this->send_onesignal($user_id, null, $notification);
        }
        
        return $success;
    }

    /**
     * Send notification to a contact
     * 
     * @param int $contact_id Contact ID
     * @param array $notification Custom notification data
     * @return bool
     */
    public function send_to_contact($contact_id, $notification = [])
    {
        if (get_option('cyborg_push_clients_enabled') != '1') {
            return false;
        }
        
        $subscriptions = $this->CI->cyborg_push_model->get_contact_subscriptions($contact_id);
        
        if (empty($subscriptions)) {
            return false;
        }
        
        $success = false;
        
        foreach ($subscriptions as $subscription) {
            $result = $this->send_web_push($subscription, $notification);
            if ($result) {
                $success = true;
            }
        }
        
        return $success;
    }

    /**
     * Send Web Push notification using VAPID
     * 
     * @param array $subscription Subscription data
     * @param array $notification Notification data
     * @return bool
     */
    protected function send_web_push($subscription, $notification)
    {
        $vapid_public = get_option('cyborg_push_vapid_public_key');
        $vapid_private = get_option('cyborg_push_vapid_private_key');
        $vapid_subject = get_option('cyborg_push_vapid_subject');
        
        if (empty($vapid_public) || empty($vapid_private)) {
            log_message('error', 'Cyborg Push: VAPID keys not configured');
            return false;
        }
        
        // Log the notification attempt
        $log_id = $this->CI->cyborg_push_model->add_log([
            'subscription_id'   => $subscription['id'],
            'user_id'           => $subscription['user_id'],
            'contact_id'        => $subscription['contact_id'],
            'notification_type' => $notification['type'] ?? 'general',
            'title'             => $notification['title'] ?? '',
            'body'              => $notification['body'] ?? '',
            'payload'           => $notification,
            'status'            => 'pending'
        ]);
        
        try {
            // Build payload
            $payload = json_encode([
                'title'     => $notification['title'] ?? _l('cyborg_push_new_notification'),
                'body'      => $notification['body'] ?? '',
                'icon'      => $notification['icon'] ?? get_option('cyborg_push_default_icon'),
                'badge'     => $notification['badge'] ?? get_option('cyborg_push_default_badge'),
                'data'      => $notification['data'] ?? [],
                'timestamp' => time() * 1000,
                'tag'       => $notification['tag'] ?? 'cyborg-push-' . time()
            ]);
            
            // Check if web-push library is available
            if (!class_exists('Minishlink\WebPush\WebPush')) {
                // Fallback to cURL
                return $this->send_web_push_curl($subscription, $payload, $log_id);
            }
            
            // Use web-push library
            $auth = [
                'VAPID' => [
                    'subject'    => $vapid_subject ?: site_url(),
                    'publicKey'  => $vapid_public,
                    'privateKey' => $vapid_private
                ]
            ];
            
            $webPush = new \Minishlink\WebPush\WebPush($auth);
            
            $subscription_obj = \Minishlink\WebPush\Subscription::create([
                'endpoint' => $subscription['endpoint'],
                'publicKey' => $subscription['p256dh'],
                'authToken' => $subscription['auth']
            ]);
            
            $report = $webPush->sendOneNotification($subscription_obj, $payload);
            
            if ($report->isSuccess()) {
                $this->CI->cyborg_push_model->update_log_status($log_id, 'sent');
                return true;
            } else {
                $reason = $report->getReason();
                $this->CI->cyborg_push_model->update_log_status($log_id, 'failed', $reason);
                
                // Mark subscription as expired if endpoint is expired
                if ($report->isSubscriptionExpired()) {
                    $this->CI->cyborg_push_model->mark_subscription_expired($subscription['id']);
                }
                
                return false;
            }
            
        } catch (Exception $e) {
            log_message('error', 'Cyborg Push: Error sending notification - ' . $e->getMessage());
            $this->CI->cyborg_push_model->update_log_status($log_id, 'failed', $e->getMessage());
            return false;
        }
    }

    /**
     * Send Web Push using cURL (fallback when library is not available)
     * 
     * @param array $subscription
     * @param string $payload
     * @param int $log_id
     * @return bool
     */
    protected function send_web_push_curl($subscription, $payload, $log_id)
    {
        // This is a simplified implementation
        // For production, you should use the web-push library
        
        $endpoint = $subscription['endpoint'];
        
        $headers = [
            'Content-Type: application/json',
            'TTL: 86400'
        ];
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($http_code >= 200 && $http_code < 300) {
            $this->CI->cyborg_push_model->update_log_status($log_id, 'sent');
            return true;
        } else {
            $this->CI->cyborg_push_model->update_log_status($log_id, 'failed', "HTTP $http_code: $error");
            
            // Mark as expired if gone (410) or not found (404)
            if ($http_code == 410 || $http_code == 404) {
                $this->CI->cyborg_push_model->mark_subscription_expired($subscription['id']);
            }
            
            return false;
        }
    }

    /**
     * Send notification via FCM
     * 
     * @param int|null $user_id
     * @param int|null $contact_id
     * @param array $notification
     * @return bool
     */
    protected function send_fcm($user_id, $contact_id, $notification)
    {
        $server_key = get_option('cyborg_push_fcm_server_key');
        
        if (empty($server_key)) {
            return false;
        }
        
        // FCM implementation
        // TODO: Implement FCM token storage and sending
        
        return false;
    }

    /**
     * Send notification via OneSignal
     * 
     * @param int|null $user_id
     * @param int|null $contact_id
     * @param array $notification
     * @return bool
     */
    protected function send_onesignal($user_id, $contact_id, $notification)
    {
        $app_id = get_option('cyborg_push_onesignal_app_id');
        $api_key = get_option('cyborg_push_onesignal_api_key');
        
        if (empty($app_id) || empty($api_key)) {
            return false;
        }
        
        // Build OneSignal payload
        $fields = [
            'app_id'   => $app_id,
            'headings' => ['en' => $notification['title'] ?? ''],
            'contents' => ['en' => $notification['body'] ?? ''],
            'data'     => $notification['data'] ?? []
        ];
        
        // Target by external user ID
        if ($user_id) {
            $fields['include_external_user_ids'] = ['staff_' . $user_id];
        } elseif ($contact_id) {
            $fields['include_external_user_ids'] = ['contact_' . $contact_id];
        }
        
        $ch = curl_init('https://onesignal.com/api/v1/notifications');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . $api_key
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code >= 200 && $http_code < 300;
    }

    /**
     * Get last notification for user from database
     * 
     * @param int $user_id
     * @return array
     */
    protected function get_last_notification_for_user($user_id)
    {
        $this->CI->db->select('id, description, fromcompany, fromuserid, link, date, isread_inline');
        $this->CI->db->where('touserid', $user_id);
        $this->CI->db->where('isread', 0);
        $this->CI->db->order_by('id', 'DESC');
        $this->CI->db->limit(1);
        
        $notification = $this->CI->db->get(db_prefix() . 'notifications')->row();
        
        if (!$notification) {
            return [
                'title' => get_option('companyname'),
                'body'  => _l('cyborg_push_new_notification'),
                'data'  => []
            ];
        }
        
        // Get sender name
        $sender_name = get_option('companyname');
        if ($notification->fromuserid) {
            $staff = get_staff($notification->fromuserid);
            if ($staff) {
                $sender_name = get_staff_full_name($notification->fromuserid);
            }
        }
        
        return [
            'title' => $sender_name,
            'body'  => strip_tags($notification->description),
            'data'  => [
                'notification_id' => $notification->id,
                'link'            => $notification->link ? admin_url($notification->link) : admin_url()
            ]
        ];
    }
}
