<?php
namespace App\Api;

use App\Models\User;
use App\Utils\Logger;
use App\Middleware\AuthMiddleware;
use Exception;

class AuthController {
    private $userModel;
    private $logger;

    public function __construct() {
        $this->userModel = new User();
        $this->logger = new Logger();
    }

    /**
     * Handle user login
     */
    public function login() {
        try {
            // Get request data
            $data = $this->getRequestData();
            
            // Validate required fields
            if (empty($data['email']) || empty($data['password'])) {
                throw new Exception('Email and password are required');
            }

            // Find user by email
            $user = $this->userModel->findByEmail($data['email']);
            
            if (!$user) {
                throw new Exception('Invalid credentials');
            }

            // Verify password
            if (!$this->userModel->verifyPassword($data['password'], $user['password'])) {
                throw new Exception('Invalid credentials');
            }

            // Check if user is active
            if ($user['status'] !== 'active') {
                throw new Exception('Account is not active');
            }

            // Generate JWT token
            $token = AuthMiddleware::generateToken([
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ]);

            // Update last login timestamp
            $this->userModel->updateLastLogin($user['id']);

            // Log successful login
            $this->logger->info('User logged in successfully', [
                'user_id' => $user['id'],
                'email' => $user['email']
            ]);

            return [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];

        } catch (Exception $e) {
            $this->logger->error('Login failed: ' . $e->getMessage());
            throw new Exception('Login failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle user registration
     */
    public function register() {
        try {
            // Get request data
            $data = $this->getRequestData();

            // Check if email already exists
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser) {
                throw new Exception('Email already registered');
            }

            // Create new user
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'user', // Default role
                'status' => 'active'
            ];

            $user = $this->userModel->createUser($userData);

            // Generate JWT token
            $token = AuthMiddleware::generateToken([
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ]);

            // Log successful registration
            $this->logger->info('New user registered', [
                'user_id' => $user['id'],
                'email' => $user['email']
            ]);

            return [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];

        } catch (Exception $e) {
            $this->logger->error('Registration failed: ' . $e->getMessage());
            throw new Exception('Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle password reset request
     */
    public function forgotPassword() {
        try {
            $data = $this->getRequestData();
            
            if (empty($data['email'])) {
                throw new Exception('Email is required');
            }

            // Find user by email
            $user = $this->userModel->findByEmail($data['email']);
            
            if (!$user) {
                throw new Exception('User not found');
            }

            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $resetExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Update user with reset token
            $this->userModel->update($user['id'], [
                'reset_token' => $resetToken,
                'reset_token_expires' => $resetExpiry
            ]);

            // Send reset email (implement email sending logic)
            // TODO: Implement email sending

            $this->logger->info('Password reset requested', [
                'user_id' => $user['id'],
                'email' => $user['email']
            ]);

            return [
                'message' => 'Password reset instructions sent to your email'
            ];

        } catch (Exception $e) {
            $this->logger->error('Password reset request failed: ' . $e->getMessage());
            throw new Exception('Password reset request failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle password reset
     */
    public function resetPassword() {
        try {
            $data = $this->getRequestData();
            
            if (empty($data['token']) || empty($data['password'])) {
                throw new Exception('Token and new password are required');
            }

            // Find user by reset token
            $user = $this->userModel->findOneBy('reset_token', $data['token']);
            
            if (!$user) {
                throw new Exception('Invalid reset token');
            }

            // Check if token is expired
            if (strtotime($user['reset_token_expires']) < time()) {
                throw new Exception('Reset token has expired');
            }

            // Update password
            $this->userModel->update($user['id'], [
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'reset_token' => null,
                'reset_token_expires' => null
            ]);

            $this->logger->info('Password reset successful', [
                'user_id' => $user['id'],
                'email' => $user['email']
            ]);

            return [
                'message' => 'Password reset successful'
            ];

        } catch (Exception $e) {
            $this->logger->error('Password reset failed: ' . $e->getMessage());
            throw new Exception('Password reset failed: ' . $e->getMessage());
        }
    }

    /**
     * Get request data
     */
    private function getRequestData() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON payload');
            }
            return $data;
        }
        
        return $_POST;
    }
}
