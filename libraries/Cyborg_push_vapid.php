<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Cyborg_push_vapid
{
    public function generate_keys()
    {
        if (class_exists('Minishlink\WebPush\VAPID')) {
            try {
                $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
                return ['publicKey' => $keys['publicKey'], 'privateKey' => $keys['privateKey']];
            } catch (Exception $e) {
                log_message('error', 'Cyborg Push VAPID: ' . $e->getMessage());
            }
        }
        return $this->generate_keys_openssl();
    }

    protected function generate_keys_openssl()
    {
        if (!function_exists('openssl_pkey_new')) {
            return false;
        }
        try {
            $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
            if (!$key) return false;
            
            $details = openssl_pkey_get_details($key);
            if (!$details) return false;
            
            $x = str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT);
            $y = str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);
            $d = str_pad($details['ec']['d'], 32, "\0", STR_PAD_LEFT);
            
            return [
                'publicKey'  => $this->base64url_encode("\x04" . $x . $y),
                'privateKey' => $this->base64url_encode($d)
            ];
        } catch (Exception $e) {
            return false;
        }
    }

    protected function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
