<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Cyborg_push_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all subscriptions
     * 
     * @param array $where Optional where conditions
     * @return array
     */
    public function get_subscriptions($where = [])
    {
        if (!empty($where)) {
            $this->db->where($where);
        }
        
        $this->db->select('s.*, staff.firstname as staff_firstname, staff.lastname as staff_lastname, contacts.firstname as contact_firstname, contacts.lastname as contact_lastname');
        $this->db->from(db_prefix() . 'cyborg_push_subscriptions s');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = s.user_id', 'left');
        $this->db->join(db_prefix() . 'contacts contacts', 'contacts.id = s.contact_id', 'left');
        $this->db->order_by('s.created_at', 'DESC');
        
        $results = $this->db->get()->result_array();
        
        // Process user_name and user_type in PHP instead of SQL
        foreach ($results as &$row) {
            if (!empty($row['user_id'])) {
                $row['user_name'] = trim($row['staff_firstname'] . ' ' . $row['staff_lastname']);
                $row['user_type'] = 'staff';
            } elseif (!empty($row['contact_id'])) {
                $row['user_name'] = trim($row['contact_firstname'] . ' ' . $row['contact_lastname']);
                $row['user_type'] = 'contact';
            } else {
                $row['user_name'] = 'Unknown';
                $row['user_type'] = 'unknown';
            }
        }
        
        return $results;
    }

    /**
     * Get subscription by ID
     * 
     * @param int $id
     * @return array|null
     */
    public function get_subscription($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'cyborg_push_subscriptions')->row_array();
    }

    /**
     * Get subscriptions by user ID
     * 
     * @param int $user_id Staff user ID
     * @return array
     */
    public function get_user_subscriptions($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->where('is_active', 1);
        return $this->db->get(db_prefix() . 'cyborg_push_subscriptions')->result_array();
    }

    /**
     * Get subscriptions by contact ID
     * 
     * @param int $contact_id Contact ID
     * @return array
     */
    public function get_contact_subscriptions($contact_id)
    {
        $this->db->where('contact_id', $contact_id);
        $this->db->where('is_active', 1);
        return $this->db->get(db_prefix() . 'cyborg_push_subscriptions')->result_array();
    }

    /**
     * Save push subscription
     * 
     * @param array $data Subscription data
     * @return int|bool Insert ID or false
     */
    public function save_subscription($data)
    {
        // Check if subscription already exists
        $this->db->where('endpoint', $data['endpoint']);
        $existing = $this->db->get(db_prefix() . 'cyborg_push_subscriptions')->row();
        
        if ($existing) {
            // Update existing subscription
            $this->db->where('id', $existing->id);
            $this->db->update(db_prefix() . 'cyborg_push_subscriptions', [
                'user_id'    => $data['user_id'],
                'contact_id' => $data['contact_id'],
                'p256dh'     => $data['p256dh'],
                'auth'       => $data['auth'],
                'user_agent' => $data['user_agent'] ?? null,
                'is_active'  => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return $existing->id;
        }
        
        // Insert new subscription
        $insert_data = [
            'user_id'     => $data['user_id'],
            'contact_id'  => $data['contact_id'],
            'endpoint'    => $data['endpoint'],
            'p256dh'      => $data['p256dh'],
            'auth'        => $data['auth'],
            'user_agent'  => $data['user_agent'] ?? null,
            'device_type' => $this->detect_device_type($data['user_agent'] ?? ''),
            'is_active'   => 1,
            'created_at'  => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert(db_prefix() . 'cyborg_push_subscriptions', $insert_data);
        
        return $this->db->insert_id();
    }

    /**
     * Remove subscription by endpoint
     * 
     * @param string $endpoint
     * @return bool
     */
    public function remove_subscription($endpoint)
    {
        $this->db->where('endpoint', $endpoint);
        $this->db->update(db_prefix() . 'cyborg_push_subscriptions', [
            'is_active'  => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->db->affected_rows() > 0;
    }

    /**
     * Delete subscription
     * 
     * @param int $id
     * @return bool
     */
    public function delete_subscription($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'cyborg_push_subscriptions');
        
        return $this->db->affected_rows() > 0;
    }

    /**
     * Mark subscription as expired
     * 
     * @param int $id
     * @return bool
     */
    public function mark_subscription_expired($id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'cyborg_push_subscriptions', [
            'is_active'  => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->db->affected_rows() > 0;
    }

    /**
     * Get notification logs
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_logs($limit = 100, $offset = 0)
    {
        $this->db->select('l.*, staff.firstname as staff_firstname, staff.lastname as staff_lastname, contacts.firstname as contact_firstname, contacts.lastname as contact_lastname');
        $this->db->from(db_prefix() . 'cyborg_push_logs l');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = l.user_id', 'left');
        $this->db->join(db_prefix() . 'contacts contacts', 'contacts.id = l.contact_id', 'left');
        $this->db->order_by('l.created_at', 'DESC');
        $this->db->limit($limit, $offset);
        
        $results = $this->db->get()->result_array();
        
        // Process user_name in PHP instead of SQL
        foreach ($results as &$row) {
            if (!empty($row['user_id'])) {
                $row['user_name'] = trim($row['staff_firstname'] . ' ' . $row['staff_lastname']);
            } elseif (!empty($row['contact_id'])) {
                $row['user_name'] = trim($row['contact_firstname'] . ' ' . $row['contact_lastname']);
            } else {
                $row['user_name'] = 'Unknown';
            }
        }
        
        return $results;
    }

    /**
     * Add log entry
     * 
     * @param array $data
     * @return int|bool
     */
    public function add_log($data)
    {
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
        
        $this->db->insert(db_prefix() . 'cyborg_push_logs', $log);
        
        return $this->db->insert_id();
    }

    /**
     * Update log status
     * 
     * @param int $id
     * @param string $status
     * @param string $error_message
     * @return bool
     */
    public function update_log_status($id, $status, $error_message = null)
    {
        $update = [
            'status' => $status
        ];
        
        if ($status === 'sent') {
            $update['sent_at'] = date('Y-m-d H:i:s');
        }
        
        if ($error_message) {
            $update['error_message'] = $error_message;
        }
        
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'cyborg_push_logs', $update);
        
        return $this->db->affected_rows() > 0;
    }

    /**
     * Clear old logs
     * 
     * @param int $days Days to keep
     * @return int Number of deleted rows
     */
    public function clear_old_logs($days = 30)
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $this->db->where('created_at <', $date);
        $this->db->delete(db_prefix() . 'cyborg_push_logs');
        
        return $this->db->affected_rows();
    }

    /**
     * Get statistics
     * 
     * @return array
     */
    public function get_statistics()
    {
        $stats = [];
        
        // Total active subscriptions
        $this->db->where('is_active', 1);
        $stats['total_subscriptions'] = $this->db->count_all_results(db_prefix() . 'cyborg_push_subscriptions');
        
        // Staff subscriptions
        $this->db->where('is_active', 1);
        $this->db->where('user_id IS NOT NULL');
        $stats['staff_subscriptions'] = $this->db->count_all_results(db_prefix() . 'cyborg_push_subscriptions');
        
        // Contact subscriptions
        $this->db->where('is_active', 1);
        $this->db->where('contact_id IS NOT NULL');
        $stats['contact_subscriptions'] = $this->db->count_all_results(db_prefix() . 'cyborg_push_subscriptions');
        
        // Notifications sent today
        $this->db->where('DATE(created_at)', date('Y-m-d'));
        $this->db->where('status', 'sent');
        $stats['sent_today'] = $this->db->count_all_results(db_prefix() . 'cyborg_push_logs');
        
        // Failed notifications today
        $this->db->where('DATE(created_at)', date('Y-m-d'));
        $this->db->where('status', 'failed');
        $stats['failed_today'] = $this->db->count_all_results(db_prefix() . 'cyborg_push_logs');
        
        return $stats;
    }

    /**
     * Detect device type from user agent
     * 
     * @param string $user_agent
     * @return string
     */
    private function detect_device_type($user_agent)
    {
        $user_agent = strtolower($user_agent);
        
        if (strpos($user_agent, 'mobile') !== false || strpos($user_agent, 'android') !== false) {
            return 'mobile';
        }
        
        if (strpos($user_agent, 'tablet') !== false || strpos($user_agent, 'ipad') !== false) {
            return 'tablet';
        }
        
        return 'desktop';
    }
}
