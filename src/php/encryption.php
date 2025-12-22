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
     * Convert hex key to binary for AES-256
     */
    private static function convertHexKeyToBinary($key) {
        // Type check
        if (!is_string($key)) {
            throw new Exception('Encryption key must be a string, got ' . gettype($key));
        }
        
        // The key is stored as 64-char hex string, but AES-256 needs 32 bytes
        if (ctype_xdigit($key) && strlen($key) === 64) {
            $binary = hex2bin($key);
            if ($binary === false) {
                throw new Exception('Failed to convert hex key to binary');
            }
            return $binary;
        }
        
        // For backward compatibility, also accept 32-byte binary keys directly
        if (strlen($key) === 32) {
            // Basic validation: check that key has some entropy (not all zeros or repeated chars)
            if ($key === str_repeat("\0", 32) || $key === str_repeat($key[0], 32)) {
                throw new Exception('Encryption key has insufficient entropy (all zeros or repeated character)');
            }
            return $key;
        }
        
        // Invalid key format - this is a critical configuration error
        throw new Exception('Invalid encryption key format. Expected 64-character hex string or 32-byte binary key, got ' . strlen($key) . ' characters/bytes.');
    }

    /**
     * Encrypt data
     */
    public static function encrypt($data) {
        self::init();
        
        $key = self::convertHexKeyToBinary(self::$config['encryption_key']);
        
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
        
        $key = self::convertHexKeyToBinary(self::$config['encryption_key']);
        
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
