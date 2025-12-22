<?php
/**
 * Encryption and Decryption for secure data storage
 */

class Encryption {
    private static $config;
    
    private static function init() {
        if (!self::$config) {
            self::$config = require __DIR__ . '/../../config/config.php';
        }
    }

    /**
     * Encrypt data
     */
    public static function encrypt($data) {
        self::init();
        
        $key = self::$config['encryption_key'];
        
        // Generate initialization vector
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        
        // Encrypt the data
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        
        // Combine IV and encrypted data
        return base64_encode($iv . '::' . $encrypted);
    }

    /**
     * Decrypt data
     */
    public static function decrypt($encryptedData) {
        self::init();
        
        $key = self::$config['encryption_key'];
        
        // Decode the data
        $decoded = base64_decode($encryptedData);
        
        // Split IV and encrypted data
        $parts = explode('::', $decoded, 2);
        if (count($parts) !== 2) {
            return false;
        }
        
        list($iv, $encrypted) = $parts;
        
        // Decrypt the data
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    }

    /**
     * Generate a random encryption key
     */
    public static function generateKey($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
}
