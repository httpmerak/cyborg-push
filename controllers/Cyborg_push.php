<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Cyborg_push extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cyborg_push/cyborg_push_model');
    }

    /**
     * Module index - redirect to integrations settings
     */
    public function index()
    {
        redirect(admin_url('settings?group=integrations'));
    }

    /**
     * Generate VAPID keys
     */
    public function generate_vapid()
    {
        if (!is_admin()) {
            access_denied('Cyborg Push Settings');
        }
        
        $this->load->library('cyborg_push/Cyborg_push_vapid');
        $keys = $this->cyborg_push_vapid->generate_keys();
        
        if ($keys) {
            update_option('cyborg_push_vapid_public_key', $keys['publicKey']);
            update_option('cyborg_push_vapid_private_key', $keys['privateKey']);
            set_alert('success', _l('cyborg_push_vapid_generated'));
        } else {
            set_alert('danger', _l('cyborg_push_vapid_generation_failed'));
        }
        
        redirect(admin_url('settings?group=integrations'));
    }

    /**
     * Settings page
     */
    public function settings()
    {
        if (!is_admin()) {
            access_denied('Cyborg Push Settings');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            
            // Handle VAPID key generation
            if (isset($data['generate_vapid_keys']) && $data['generate_vapid_keys'] == '1') {
                $this->load->library('cyborg_push/Cyborg_push_vapid');
                $keys = $this->cyborg_push_vapid->generate_keys();
                
                if ($keys) {
                    update_option('cyborg_push_vapid_public_key', $keys['publicKey']);
                    update_option('cyborg_push_vapid_private_key', $keys['privateKey']);
                    set_alert('success', _l('cyborg_push_vapid_generated'));
                } else {
                    set_alert('danger', _l('cyborg_push_vapid_generation_failed'));
                }
                
                redirect(admin_url('cyborg_push/settings'));
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
                if (isset($data[$setting])) {
                    update_option($setting, $data[$setting]);
                } else {
                    // Checkboxes that are not set
                    if (in_array($setting, [
                        'cyborg_push_enabled',
                        'cyborg_push_disable_pusher'
                    ])) {
                        update_option($setting, '0');
                    }
                }
            }
            
            set_alert('success', _l('settings_updated'));
            redirect(admin_url('cyborg_push/settings'));
        }

        $data['title'] = _l('cyborg_push_settings');
        $this->load->view('cyborg_push/settings', $data);
    }

    /**
     * Push subscriptions management
     */
    public function subscriptions()
    {
        if (!is_admin()) {
            access_denied('Cyborg Push Subscriptions');
        }

        $data['title'] = _l('cyborg_push_subscriptions');
        $data['subscriptions'] = $this->cyborg_push_model->get_subscriptions();
        $this->load->view('cyborg_push/subscriptions', $data);
    }

    /**
     * Notification logs
     */
    public function logs()
    {
        if (!is_admin()) {
            access_denied('Cyborg Push Logs');
        }

        $data['title'] = _l('cyborg_push_logs');
        $data['logs'] = $this->cyborg_push_model->get_logs();
        $this->load->view('cyborg_push/logs', $data);
    }

    /**
     * Subscribe endpoint (AJAX)
     */
    public function subscribe()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $subscription = $this->input->post();
        
        if (empty($subscription['endpoint'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid subscription data']);
            return;
        }

        $user_id = get_staff_user_id();
        
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'User not authenticated']);
            return;
        }

        $result = $this->cyborg_push_model->save_subscription([
            'user_id'    => $user_id,
            'contact_id' => null,
            'endpoint'   => $subscription['endpoint'],
            'p256dh'     => $subscription['keys']['p256dh'] ?? '',
            'auth'       => $subscription['keys']['auth'] ?? '',
            'user_agent' => $this->input->user_agent()
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Subscription saved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save subscription']);
        }
    }

    /**
     * Unsubscribe endpoint (AJAX)
     */
    public function unsubscribe()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $endpoint = $this->input->post('endpoint');
        
        if (empty($endpoint)) {
            echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
            return;
        }

        $result = $this->cyborg_push_model->remove_subscription($endpoint);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Subscription removed']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove subscription']);
        }
    }

    /**
     * Delete subscription
     */
    public function delete_subscription($id)
    {
        if (!is_admin()) {
            access_denied('Cyborg Push');
        }

        $this->cyborg_push_model->delete_subscription($id);
        set_alert('success', _l('deleted'));
        redirect(admin_url('cyborg_push/subscriptions'));
    }

    /**
     * Send test notification
     */
    public function test_notification()
    {
        if (!is_admin()) {
            access_denied('Cyborg Push');
        }

        $user_id = get_staff_user_id();
        
        $this->load->library('cyborg_push/Cyborg_push_sender');
        $result = $this->cyborg_push_sender->send_to_user($user_id, [
            'title' => 'Cyborg Push Test',
            'body'  => 'If you see this notification, Cyborg Push is working correctly!',
            'data'  => ['type' => 'test', 'timestamp' => time()]
        ]);

        if ($result) {
            set_alert('success', _l('cyborg_push_test_sent'));
        } else {
            set_alert('danger', _l('cyborg_push_test_failed'));
        }

        redirect(admin_url('cyborg_push/settings'));
    }

    /**
     * Clear old logs
     */
    public function clear_logs()
    {
        if (!is_admin()) {
            access_denied('Cyborg Push');
        }

        $days = get_option('cyborg_push_log_retention_days');
        $this->cyborg_push_model->clear_old_logs($days);
        
        set_alert('success', _l('cyborg_push_logs_cleared'));
        redirect(admin_url('cyborg_push/logs'));
    }
}
