<?php
/**
 * Encryption and Decryption for secure data storage
 */

class Encryption {
    private static $config;
    
    // AES-256-CBC key length constants
    const HEX_KEY_LENGTH = 64;      // 64 hex characters = 32 bytes
    const BINARY_KEY_LENGTH = 32;   // 32 bytes = 256 bits
    
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
        if (ctype_xdigit($key) && strlen($key) === self::HEX_KEY_LENGTH) {
            $binary = hex2bin($key);
            if ($binary === false) {
                throw new Exception('Failed to convert hex key to binary');
            }
            return $binary;
        }
        
        // For backward compatibility, also accept 32-byte binary keys directly
        if (strlen($key) === self::BINARY_KEY_LENGTH) {
            // Basic validation: check that key has some entropy (not all zeros or repeated chars)
            if ($key === str_repeat("\0", self::BINARY_KEY_LENGTH) || $key === str_repeat($key[0], self::BINARY_KEY_LENGTH)) {
                throw new Exception('Encryption key has insufficient entropy (all zeros or repeated character)');
            }
            return $key;
        }
        
        // Invalid key format - this is a critical configuration error
        throw new Exception('Invalid encryption key format. Expected ' . self::HEX_KEY_LENGTH . '-character hex string or ' . self::BINARY_KEY_LENGTH . '-byte binary key, got ' . strlen($key) . ' characters/bytes.');
    }

    /**
     * Encrypt data
     */
    public static function encrypt($data) {
        self::init();
        
        $key = self::convertHexKeyToBinary(self::$config['encryption_key']);
        
        // Generate initialization vector
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        
        // Encrypt the data (OPENSSL_RAW_DATA returns raw binary, not base64)
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        
        // Check for encryption failure
        if ($encrypted === false) {
            $error = openssl_error_string();
            throw new Exception('Encryption failed: ' . ($error !== false ? $error : 'Unknown OpenSSL error'));
        }
        
        // Combine IV and encrypted data using base64 encoding for each part
        // This prevents the '::' separator from appearing in the binary data itself
        return base64_encode($iv) . '::' . base64_encode($encrypted);
    }

    /**
     * Decrypt data
     */
    public static function decrypt($encryptedData) {
        self::init();
        
        $key = self::convertHexKeyToBinary(self::$config['encryption_key']);
        
        // Detect format based on characteristics
        // New format: Contains '::' in base64 string, both parts are valid base64
        // Old format: Single base64 string, after decode contains '::' in binary data
        
        if (strpos($encryptedData, '::') !== false) {
            $parts = explode('::', $encryptedData, 2);
            if (count($parts) === 2) {
                // Check if both parts are valid base64 (new format characteristic)
                // Base64 strings should match pattern: [A-Za-z0-9+/]+ with 0-2 padding chars
                if (preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $parts[0]) && 
                    preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $parts[1])) {
                    
                    // New format: both IV and encrypted data are base64 encoded separately
                    $iv = base64_decode($parts[0]);
                    $encrypted = base64_decode($parts[1]);
                    
                    // Validate that decoding worked and IV has correct length
                    if ($iv !== false && $encrypted !== false && strlen($iv) === 16) {
                        $result = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
                        if ($result !== false) {
                            return $result;
                        }
                    }
                }
            }
        }
        
        // Try old format for backwards compatibility (base64(IV . '::' . encrypted))
        $decoded = base64_decode($encryptedData);
        if ($decoded !== false) {
            $parts = explode('::', $decoded, 2);
            if (count($parts) === 2) {
                list($iv, $encrypted) = $parts;
                
                // Validate IV length for old format
                if (strlen($iv) === 16) {
                    // Decrypt the data (OPENSSL_RAW_DATA indicates encrypted data is raw binary)
                    $result = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
                    if ($result !== false) {
                        return $result;
                    }
                }
            }
        }
        
        // Both formats failed
        return false;
    }

    /**
     * Generate a random encryption key
     */
    public static function generateKey($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
}
