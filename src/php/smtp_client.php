<?php
/**
 * Native PHP 8 SMTP Client
 * Simple SMTP implementation using PHP's built-in socket functions
 * No external dependencies required
 */

class SMTPClient {
    private $socket;
    private $host;
    private $port;
    private $secure;
    private $username;
    private $password;
    private $timeout = 30;
    private $lastResponse = '';
    
    /**
     * Constructor
     * 
     * @param string $host SMTP server hostname
     * @param int $port SMTP server port (25, 587, 465)
     * @param string $secure Encryption type ('tls', 'ssl', or '')
     * @param string $username SMTP username (optional)
     * @param string $password SMTP password (optional)
     */
    public function __construct(string $host, int $port = 587, string $secure = 'tls', string $username = '', string $password = '') {
        $this->host = $host;
        $this->port = $port;
        $this->secure = strtolower($secure);
        $this->username = $username;
        $this->password = $password;
    }
    
    /**
     * Send email
     * 
     * @param string $from From email address
     * @param string $fromName From name
     * @param string $to To email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML or plain text)
     * @param bool $isHtml Whether body is HTML
     * @param array $attachments Array of attachments ['filename' => content]
     * @return bool Success status
     */
    public function sendEmail(string $from, string $fromName, string $to, string $subject, string $body, bool $isHtml = true, array $attachments = []): bool {
        try {
            $this->connect();
            $this->authenticate();
            
            // MAIL FROM
            $this->sendCommand("MAIL FROM:<{$from}>", 250);
            
            // RCPT TO
            $this->sendCommand("RCPT TO:<{$to}>", 250);
            
            // DATA
            $this->sendCommand("DATA", 354);
            
            // Headers
            $boundary = '----=_Part_' . md5(uniqid('', true));
            $headers = $this->buildHeaders($from, $fromName, $to, $subject, $boundary, $isHtml, $attachments);
            
            // Body
            $emailBody = $this->buildBody($body, $isHtml, $boundary, $attachments);
            
            // Send email content
            $this->sendData($headers . "\r\n" . $emailBody . "\r\n.");
            
            // QUIT
            $this->sendCommand("QUIT", 221);
            
            $this->disconnect();
            return true;
            
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            $this->disconnect();
            return false;
        }
    }
    
    /**
     * Connect to SMTP server
     */
    private function connect(): void {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            ]
        ]);
        
        // Use SSL/TLS wrapper for port 465
        if ($this->secure === 'ssl') {
            $this->socket = @stream_socket_client(
                "ssl://{$this->host}:{$this->port}",
                $errno,
                $errstr,
                $this->timeout,
                STREAM_CLIENT_CONNECT,
                $context
            );
        } else {
            // Regular connection for STARTTLS
            $this->socket = @stream_socket_client(
                "tcp://{$this->host}:{$this->port}",
                $errno,
                $errstr,
                $this->timeout,
                STREAM_CLIENT_CONNECT
            );
        }
        
        if (!$this->socket) {
            throw new Exception("Failed to connect to SMTP server: {$errstr} ({$errno})");
        }
        
        stream_set_timeout($this->socket, $this->timeout);
        
        // Read greeting
        $this->readResponse(220);
        
        // EHLO
        $this->sendCommand("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), 250);
        
        // STARTTLS for non-SSL connections
        if ($this->secure === 'tls' && $this->port != 465) {
            $this->sendCommand("STARTTLS", 220);
            
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Failed to enable TLS encryption");
            }
            
            // Send EHLO again after STARTTLS
            $this->sendCommand("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), 250);
        }
    }
    
    /**
     * Authenticate with SMTP server
     */
    private function authenticate(): void {
        if (empty($this->username) || empty($this->password)) {
            return; // No authentication required
        }
        
        $this->sendCommand("AUTH LOGIN", 334);
        $this->sendCommand(base64_encode($this->username), 334);
        $this->sendCommand(base64_encode($this->password), 235);
    }
    
    /**
     * Send SMTP command and verify response
     */
    private function sendCommand(string $command, int $expectedCode): void {
        fwrite($this->socket, $command . "\r\n");
        $this->readResponse($expectedCode);
    }
    
    /**
     * Send data without expecting immediate response
     */
    private function sendData(string $data): void {
        fwrite($this->socket, $data . "\r\n");
        $this->readResponse(250);
    }
    
    /**
     * Read server response
     */
    private function readResponse(int $expectedCode): string {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            // Check if this is the last line (code without dash)
            if (preg_match('/^(\d{3}) /', $line, $matches)) {
                $code = (int)$matches[1];
                $this->lastResponse = $response;
                
                if ($code !== $expectedCode) {
                    throw new Exception("SMTP Error: Expected {$expectedCode}, got {$code}. Response: {$response}");
                }
                return $response;
            }
        }
        throw new Exception("Failed to read SMTP response");
    }
    
    /**
     * Build email headers
     */
    private function buildHeaders(string $from, string $fromName, string $to, string $subject, string $boundary, bool $isHtml, array $attachments): string {
        $headers = [];
        $headers[] = "From: " . (!empty($fromName) ? "=?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>" : $from);
        $headers[] = "To: {$to}";
        $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
        $headers[] = "Date: " . date('r');
        $headers[] = "MIME-Version: 1.0";
        
        if (!empty($attachments)) {
            $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";
        } elseif ($isHtml) {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
            $headers[] = "Content-Transfer-Encoding: base64";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
            $headers[] = "Content-Transfer-Encoding: base64";
        }
        
        return implode("\r\n", $headers);
    }
    
    /**
     * Build email body with attachments
     */
    private function buildBody(string $body, bool $isHtml, string $boundary, array $attachments): string {
        if (empty($attachments)) {
            return chunk_split(base64_encode($body));
        }
        
        $parts = [];
        
        // Main body
        $parts[] = "--{$boundary}";
        $parts[] = "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8";
        $parts[] = "Content-Transfer-Encoding: base64";
        $parts[] = "";
        $parts[] = chunk_split(base64_encode($body));
        
        // Attachments
        foreach ($attachments as $filename => $content) {
            $parts[] = "--{$boundary}";
            $parts[] = "Content-Type: application/octet-stream; name=\"{$filename}\"";
            $parts[] = "Content-Transfer-Encoding: base64";
            $parts[] = "Content-Disposition: attachment; filename=\"{$filename}\"";
            $parts[] = "";
            $parts[] = chunk_split(base64_encode($content));
        }
        
        $parts[] = "--{$boundary}--";
        
        return implode("\r\n", $parts);
    }
    
    /**
     * Disconnect from server
     */
    private function disconnect(): void {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }
    }
    
    /**
     * Get last server response
     */
    public function getLastResponse(): string {
        return $this->lastResponse;
    }
}
