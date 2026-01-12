<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cyborg Push WebPush Library
 * 
 * Implementação nativa de Web Push sem dependências externas
 * Usa apenas OpenSSL e cURL (funções nativas do PHP)
 */
class Cyborg_push_webpush
{
    protected $vapidPublicKey;
    protected $vapidPrivateKey;
    protected $vapidSubject;
    
    public function __construct()
    {
        $this->vapidPublicKey = get_option('cyborg_push_vapid_public_key');
        $this->vapidPrivateKey = get_option('cyborg_push_vapid_private_key');
        $this->vapidSubject = get_option('cyborg_push_vapid_subject') ?: site_url();
    }
    
    /**
     * Envia notificação push para um subscription
     * 
     * @param array $subscription Dados do subscription (endpoint, p256dh, auth)
     * @param array $payload Dados da notificação
     * @return array ['success' => bool, 'message' => string, 'expired' => bool]
     */
    public function send($subscription, $payload)
    {
        if (empty($this->vapidPublicKey) || empty($this->vapidPrivateKey)) {
            return ['success' => false, 'message' => 'VAPID keys not configured', 'expired' => false];
        }
        
        $endpoint = $subscription['endpoint'];
        $userPublicKey = $subscription['p256dh'];
        $userAuthToken = $subscription['auth'];
        
        // Preparar payload JSON
        $payloadJson = json_encode($payload);
        
        try {
            // Criptografar payload
            $encrypted = $this->encryptPayload($payloadJson, $userPublicKey, $userAuthToken);
            
            if (!$encrypted) {
                return ['success' => false, 'message' => 'Failed to encrypt payload', 'expired' => false];
            }
            
            // Gerar headers VAPID
            $vapidHeaders = $this->createVapidHeaders($endpoint);
            
            if (!$vapidHeaders) {
                return ['success' => false, 'message' => 'Failed to create VAPID headers', 'expired' => false];
            }
            
            // Enviar requisição
            $result = $this->sendRequest($endpoint, $encrypted, $vapidHeaders);
            
            return $result;
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'expired' => false];
        }
    }
    
    /**
     * Criptografa o payload usando ECDH e AES-GCM
     */
    protected function encryptPayload($payload, $userPublicKey, $userAuthToken)
    {
        // Decodificar chaves do usuário
        $userPublicKeyBytes = $this->base64UrlDecode($userPublicKey);
        $userAuthBytes = $this->base64UrlDecode($userAuthToken);
        
        if (strlen($userPublicKeyBytes) !== 65 || strlen($userAuthBytes) !== 16) {
            return false;
        }
        
        // Gerar par de chaves efêmeras
        $localKeyPair = $this->generateLocalKeyPair();
        if (!$localKeyPair) {
            return false;
        }
        
        $localPublicKey = $localKeyPair['public'];
        $localPrivateKey = $localKeyPair['private'];
        
        // Derivar shared secret usando ECDH
        $sharedSecret = $this->computeSharedSecret($localPrivateKey, $userPublicKeyBytes);
        if (!$sharedSecret) {
            return false;
        }
        
        // Derivar chaves de criptografia
        $salt = random_bytes(16);
        
        // Info para HKDF
        $keyInfo = "WebPush: info\x00" . $userPublicKeyBytes . $localPublicKey;
        $nonceInfo = "Content-Encoding: nonce\x00";
        $cekInfo = "Content-Encoding: aes128gcm\x00";
        
        // PRK (Pseudo-Random Key)
        $prk = $this->hkdfExtract($userAuthBytes, $sharedSecret);
        
        // IKM para segunda derivação
        $ikm = $this->hkdfExpand($prk, $keyInfo, 32);
        
        // PRK final
        $prk2 = $this->hkdfExtract($salt, $ikm);
        
        // Derivar CEK (Content Encryption Key) e Nonce
        $cek = $this->hkdfExpand($prk2, $cekInfo, 16);
        $nonce = $this->hkdfExpand($prk2, $nonceInfo, 12);
        
        // Adicionar padding ao payload
        $paddedPayload = "\x00\x00" . $payload;
        
        // Criptografar com AES-128-GCM
        $tag = '';
        $ciphertext = openssl_encrypt(
            $paddedPayload,
            'aes-128-gcm',
            $cek,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            16
        );
        
        if ($ciphertext === false) {
            return false;
        }
        
        // Construir corpo da requisição (aes128gcm format)
        // Header: salt (16) + rs (4) + idlen (1) + keyid (65)
        $rs = pack('N', 4096); // Record size
        $idlen = chr(65); // Length of public key
        
        $body = $salt . $rs . $idlen . $localPublicKey . $ciphertext . $tag;
        
        return [
            'body' => $body,
            'localPublicKey' => $localPublicKey
        ];
    }
    
    /**
     * Gera par de chaves ECDH local
     */
    protected function generateLocalKeyPair()
    {
        $config = [
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC
        ];
        
        $key = openssl_pkey_new($config);
        if (!$key) {
            return false;
        }
        
        $details = openssl_pkey_get_details($key);
        if (!$details) {
            return false;
        }
        
        // Exportar chave privada
        openssl_pkey_export($key, $privateKeyPem);
        
        // Construir chave pública no formato uncompressed (0x04 || x || y)
        $x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
        $publicKey = "\x04" . $x . $y;
        
        return [
            'public' => $publicKey,
            'private' => $privateKeyPem,
            'details' => $details
        ];
    }
    
    /**
     * Computa shared secret usando ECDH
     */
    protected function computeSharedSecret($localPrivateKeyPem, $remotePublicKeyBytes)
    {
        // Converter chave pública remota para PEM
        $remotePem = $this->publicKeyToPem($remotePublicKeyBytes);
        if (!$remotePem) {
            return false;
        }
        
        $remoteKey = openssl_pkey_get_public($remotePem);
        if (!$remoteKey) {
            return false;
        }
        
        $localKey = openssl_pkey_get_private($localPrivateKeyPem);
        if (!$localKey) {
            return false;
        }
        
        // Derivar shared secret
        $sharedSecret = openssl_pkey_derive($remoteKey, $localKey);
        
        return $sharedSecret;
    }
    
    /**
     * Converte chave pública raw para PEM
     */
    protected function publicKeyToPem($publicKeyBytes)
    {
        // ASN.1 header para EC public key (prime256v1)
        $header = hex2bin(
            '3059301306072a8648ce3d020106082a8648ce3d030107034200'
        );
        
        $der = $header . $publicKeyBytes;
        $pem = "-----BEGIN PUBLIC KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END PUBLIC KEY-----\n";
        
        return $pem;
    }
    
    /**
     * HKDF Extract
     */
    protected function hkdfExtract($salt, $ikm)
    {
        return hash_hmac('sha256', $ikm, $salt, true);
    }
    
    /**
     * HKDF Expand
     */
    protected function hkdfExpand($prk, $info, $length)
    {
        $hash = '';
        $output = '';
        $counter = 1;
        
        while (strlen($output) < $length) {
            $hash = hash_hmac('sha256', $hash . $info . chr($counter), $prk, true);
            $output .= $hash;
            $counter++;
        }
        
        return substr($output, 0, $length);
    }
    
    /**
     * Cria headers VAPID (Authorization e Crypto-Key)
     */
    protected function createVapidHeaders($endpoint)
    {
        // Extrair audience do endpoint
        $parsedUrl = parse_url($endpoint);
        $audience = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        
        // Criar JWT
        $header = [
            'typ' => 'JWT',
            'alg' => 'ES256'
        ];
        
        $payload = [
            'aud' => $audience,
            'exp' => time() + 86400, // 24 horas
            'sub' => $this->vapidSubject
        ];
        
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $dataToSign = $headerEncoded . '.' . $payloadEncoded;
        
        // Assinar com chave privada VAPID
        $signature = $this->signWithVapidKey($dataToSign);
        if (!$signature) {
            return false;
        }
        
        $jwt = $dataToSign . '.' . $this->base64UrlEncode($signature);
        
        return [
            'Authorization' => 'vapid t=' . $jwt . ', k=' . $this->vapidPublicKey,
            'Crypto-Key' => 'p256ecdsa=' . $this->vapidPublicKey
        ];
    }
    
    /**
     * Assina dados com chave privada VAPID
     */
    protected function signWithVapidKey($data)
    {
        // First, try to decode as base64 PEM (new format)
        $decodedPem = @base64_decode($this->vapidPrivateKey, true);
        
        if ($decodedPem && strpos($decodedPem, '-----BEGIN') !== false) {
            // It's a PEM stored as base64
            $pem = $decodedPem;
            log_message('error', 'Cyborg Push: Using stored PEM format');
        } else {
            // Old format - try to reconstruct PEM from raw bytes
            $privateKeyBytes = $this->base64UrlDecode($this->vapidPrivateKey);
            
            log_message('error', 'Cyborg Push: Private key length after decode: ' . strlen($privateKeyBytes));
            
            // Normalize to 32 bytes
            if (strlen($privateKeyBytes) < 32) {
                $privateKeyBytes = str_pad($privateKeyBytes, 32, "\x00", STR_PAD_LEFT);
            } elseif (strlen($privateKeyBytes) > 32) {
                $privateKeyBytes = substr($privateKeyBytes, -32);
            }
            
            // Try to construct PEM
            $pem = $this->privateKeyToPemAlternative($privateKeyBytes);
        }
        
        $key = @openssl_pkey_get_private($pem);
        if (!$key) {
            $error = openssl_error_string();
            log_message('error', 'Cyborg Push: OpenSSL error getting private key: ' . $error);
            return false;
        }
        
        // Sign
        $signature = '';
        $result = openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);
        
        if (!$result) {
            $error = openssl_error_string();
            log_message('error', 'Cyborg Push: OpenSSL sign error: ' . $error);
            return false;
        }
        
        // Convert DER to raw (r || s)
        return $this->derToRaw($signature);
    }
    
    /**
     * Alternative PEM format for EC private key
     */
    protected function privateKeyToPemAlternative($privateKeyBytes)
    {
        // Try with explicit public key derivation
        // This creates a more complete SEC1 format
        $prefix = hex2bin('30770201010420');
        $suffix = hex2bin('a00a06082a8648ce3d030107');
        
        $der = $prefix . $privateKeyBytes . $suffix;
        
        $pem = "-----BEGIN EC PRIVATE KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END EC PRIVATE KEY-----\n";
        
        return $pem;
    }
    
    /**
     * Converte chave privada raw para PEM
     */
    protected function privateKeyToPem($privateKeyBytes)
    {
        // ASN.1 DER encoding para EC private key
        $oid = hex2bin('06082a8648ce3d030107'); // OID for prime256v1
        
        // Construir estrutura ASN.1
        $der = "\x30" . chr(119) // SEQUENCE
             . "\x02\x01\x01" // INTEGER version = 1
             . "\x04\x20" . $privateKeyBytes // OCTET STRING private key
             . "\xa0" . chr(10) . $oid // [0] curve OID
             ;
        
        $pem = "-----BEGIN EC PRIVATE KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END EC PRIVATE KEY-----\n";
        
        return $pem;
    }
    
    /**
     * Converte assinatura DER para formato raw (r || s)
     */
    protected function derToRaw($der)
    {
        // Parse DER SEQUENCE
        $pos = 0;
        
        if (ord($der[$pos++]) !== 0x30) return false;
        
        $length = ord($der[$pos++]);
        if ($length & 0x80) {
            $pos += ($length & 0x7f);
        }
        
        // Parse r
        if (ord($der[$pos++]) !== 0x02) return false;
        $rLen = ord($der[$pos++]);
        $r = substr($der, $pos, $rLen);
        $pos += $rLen;
        
        // Parse s
        if (ord($der[$pos++]) !== 0x02) return false;
        $sLen = ord($der[$pos++]);
        $s = substr($der, $pos, $sLen);
        
        // Remover leading zeros e pad para 32 bytes
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");
        $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
        $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);
        
        return $r . $s;
    }
    
    /**
     * Envia requisição HTTP para o push service
     */
    protected function sendRequest($endpoint, $encrypted, $vapidHeaders)
    {
        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'Content-Length: ' . strlen($encrypted['body']),
            'TTL: 86400',
            'Authorization: ' . $vapidHeaders['Authorization'],
        ];
        
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $encrypted['body'],
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Verificar resposta
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'Sent', 'expired' => false];
        }
        
        // Subscription expirada
        if ($httpCode === 404 || $httpCode === 410) {
            return ['success' => false, 'message' => 'Subscription expired', 'expired' => true];
        }
        
        // Outros erros
        $message = "HTTP $httpCode";
        if ($error) {
            $message .= ": $error";
        }
        if ($response) {
            $message .= " - $response";
        }
        
        return ['success' => false, 'message' => $message, 'expired' => false];
    }
    
    /**
     * Base64 URL Encode
     */
    protected function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL Decode
     */
    protected function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }
}
