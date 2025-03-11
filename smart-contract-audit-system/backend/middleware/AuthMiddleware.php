<?php
namespace App\Middleware;

use App\Utils\Logger;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthMiddleware {
    private $logger;
    private $excludedPaths = [
        '/auth/login',
        '/auth/register',
        '/auth/forgot-password'
    ];

    public function __construct() {
        $this->logger = new Logger();
    }

    /**
     * Handle authentication
     */
    public function handle() {
        try {
            // Check if path is excluded from authentication
            $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if ($this->isExcludedPath($currentPath)) {
                return true;
            }

            // Get authorization header
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';

            if (empty($authHeader)) {
                throw new Exception('Authorization header is missing');
            }

            // Extract token from Bearer header
            if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                throw new Exception('Invalid authorization header format');
            }

            $token = $matches[1];
            $decoded = $this->validateToken($token);

            // Add user data to request for later use
            $_REQUEST['user'] = [
                'id' => $decoded->user_id,
                'email' => $decoded->email,
                'role' => $decoded->role
            ];

            return true;

        } catch (Exception $e) {
            $this->logger->error('Authentication failed: ' . $e->getMessage());
            
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Authentication failed: ' . $e->getMessage()
            ]);
            
            exit();
        }
    }

    /**
     * Validate JWT token
     */
    private function validateToken($token) {
        try {
            $config = require __DIR__ . '/../config/config.php';
            $secret = $config['jwt']['secret'];

            if (empty($secret)) {
                throw new Exception('JWT secret is not configured');
            }

            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            // Check if token is expired
            if ($decoded->exp < time()) {
                throw new Exception('Token has expired');
            }

            return $decoded;

        } catch (Exception $e) {
            throw new Exception('Token validation failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if current path is excluded from authentication
     */
    private function isExcludedPath($currentPath) {
        foreach ($this->excludedPaths as $path) {
            if (strpos($currentPath, $path) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate JWT token
     */
    public static function generateToken($userData) {
        $config = require __DIR__ . '/../config/config.php';
        $secret = $config['jwt']['secret'];
        $expiration = $config['jwt']['expiration'];

        if (empty($secret)) {
            throw new Exception('JWT secret is not configured');
        }

        $issuedAt = time();
        $expire = $issuedAt + $expiration;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'user_id' => $userData['id'],
            'email' => $userData['email'],
            'role' => $userData['role']
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Check if user has required role
     */
    public static function checkRole($requiredRole) {
        $userRole = $_REQUEST['user']['role'] ?? null;

        if (!$userRole) {
            throw new Exception('User role not found');
        }

        // Admin has access to everything
        if ($userRole === 'admin') {
            return true;
        }

        // Check if user has required role
        if ($userRole !== $requiredRole) {
            throw new Exception('Insufficient permissions');
        }

        return true;
    }

    /**
     * Get current user data
     */
    public static function getCurrentUser() {
        return $_REQUEST['user'] ?? null;
    }

    /**
     * Check if user owns the resource
     */
    public static function checkResourceOwnership($resourceUserId) {
        $currentUser = self::getCurrentUser();

        if (!$currentUser) {
            throw new Exception('User not authenticated');
        }

        // Admin can access any resource
        if ($currentUser['role'] === 'admin') {
            return true;
        }

        // Check if user owns the resource
        if ($currentUser['id'] !== $resourceUserId) {
            throw new Exception('Access denied: You do not own this resource');
        }

        return true;
    }
}
