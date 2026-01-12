<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Cyborg_push_vapid
{
    public function generate_keys()
    {
        // Always use OpenSSL since we're not using Composer
        return $this->generate_keys_openssl();
    }

    protected function generate_keys_openssl()
    {
        if (!function_exists('openssl_pkey_new')) {
            log_message('error', 'Cyborg Push VAPID: openssl_pkey_new not available');
            return false;
        }
        
        try {
            // Create EC key with P-256 curve
            $config = [
                'curve_name' => 'prime256v1',
                'private_key_type' => OPENSSL_KEYTYPE_EC
            ];
            
            $key = openssl_pkey_new($config);
            if (!$key) {
                $error = openssl_error_string();
                log_message('error', 'Cyborg Push VAPID: Failed to generate key: ' . $error);
                return false;
            }
            
            $details = openssl_pkey_get_details($key);
            if (!$details || !isset($details['ec'])) {
                log_message('error', 'Cyborg Push VAPID: Failed to get key details');
                return false;
            }
            
            // Get raw EC components
            $x = $details['ec']['x'];
            $y = $details['ec']['y'];
            $d = $details['ec']['d'];
            
            // Log sizes for debugging
            log_message('debug', 'Cyborg Push VAPID: Key sizes - x:' . strlen($x) . ', y:' . strlen($y) . ', d:' . strlen($d));
            
            // Normalize to exactly 32 bytes
            $x = $this->normalizeKey($x, 32);
            $y = $this->normalizeKey($y, 32);
            $d = $this->normalizeKey($d, 32);
            
            // Public key = 0x04 || x || y (uncompressed point format)
            $publicKeyRaw = "\x04" . $x . $y;
            
            // Verify sizes
            if (strlen($publicKeyRaw) !== 65) {
                log_message('error', 'Cyborg Push VAPID: Invalid public key size: ' . strlen($publicKeyRaw));
                return false;
            }
            
            if (strlen($d) !== 32) {
                log_message('error', 'Cyborg Push VAPID: Invalid private key size: ' . strlen($d));
                return false;
            }
            
            $publicKey = $this->base64url_encode($publicKeyRaw);
            $privateKey = $this->base64url_encode($d);
            
            log_message('debug', 'Cyborg Push VAPID: Generated keys - public length:' . strlen($publicKey) . ', private length:' . strlen($privateKey));
            
            return [
                'publicKey'  => $publicKey,
                'privateKey' => $privateKey
            ];
        } catch (Exception $e) {
            log_message('error', 'Cyborg Push VAPID: Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Normalize key to exact byte length
     */
    protected function normalizeKey($key, $length)
    {
        // Remove leading zero bytes
        $key = ltrim($key, "\x00");
        
        // Pad to required length
        return str_pad($key, $length, "\x00", STR_PAD_LEFT);
    }

    protected function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
