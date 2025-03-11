<?php
namespace App\Middleware;

use App\Utils\Logger;
use Exception;

class InputValidationMiddleware {
    private $logger;
    private $rules = [
        'auth/login' => [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6']
        ],
        'auth/register' => [
            'name' => ['required', 'min:2'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
            'confirm_password' => ['required', 'match:password']
        ],
        'audits/create' => [
            'contract_name' => ['required', 'min:3'],
            'contract_address' => ['required', 'ethereum_address'],
            'contract_network' => ['required'],
            'contract_source' => ['required']
        ],
        'reports/create' => [
            'audit_id' => ['required', 'numeric'],
            'findings' => ['required'],
            'risk_level' => ['required', 'in:low,medium,high,critical'],
            'recommendations' => ['required']
        ],
        'compliance/create' => [
            'standard_name' => ['required', 'min:3'],
            'version' => ['required'],
            'category' => ['required'],
            'requirements' => ['required']
        ]
    ];

    public function __construct() {
        $this->logger = new Logger();
    }

    /**
     * Handle input validation
     */
    public function handle() {
        try {
            $path = $this->getRequestPath();
            $method = $_SERVER['REQUEST_METHOD'];
            
            // Skip validation for GET requests
            if ($method === 'GET') {
                return true;
            }

            // Get validation rules for current path
            $rules = $this->getRulesForPath($path);
            
            if (empty($rules)) {
                return true; // No validation rules defined for this path
            }

            // Get request data
            $data = $this->getRequestData();
            
            // Validate input
            $this->validateData($data, $rules);
            
            // Sanitize input
            $_POST = $this->sanitizeData($data);
            
            return true;

        } catch (Exception $e) {
            $this->logger->error('Validation failed: ' . $e->getMessage());
            
            http_response_code(422);
            echo json_encode([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => [$e->getMessage()]
            ]);
            
            exit();
        }
    }

    /**
     * Get current request path
     */
    private function getRequestPath() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = explode('/', trim($uri, '/'));
        
        // Remove base path segments if any
        $config = require __DIR__ . '/../config/config.php';
        $basePathSegments = explode('/', trim($config['app']['base_path'] ?? '', '/'));
        $uri = array_slice($uri, count($basePathSegments));
        
        return implode('/', $uri);
    }

    /**
     * Get validation rules for current path
     */
    private function getRulesForPath($path) {
        return $this->rules[$path] ?? [];
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

    /**
     * Validate data against rules
     */
    private function validateData($data, $rules) {
        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $rule, $data[$field] ?? null, $data);
            }
        }
    }

    /**
     * Apply validation rule
     */
    private function applyRule($field, $rule, $value, $allData) {
        if (strpos($rule, ':') !== false) {
            list($rule, $parameter) = explode(':', $rule);
        }

        switch ($rule) {
            case 'required':
                if (empty($value)) {
                    throw new Exception("The {$field} field is required");
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("The {$field} must be a valid email address");
                }
                break;

            case 'min':
                if (strlen($value) < $parameter) {
                    throw new Exception("The {$field} must be at least {$parameter} characters");
                }
                break;

            case 'match':
                if ($value !== ($allData[$parameter] ?? null)) {
                    throw new Exception("The {$field} must match {$parameter}");
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    throw new Exception("The {$field} must be numeric");
                }
                break;

            case 'ethereum_address':
                if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $value)) {
                    throw new Exception("The {$field} must be a valid Ethereum address");
                }
                break;

            case 'in':
                $allowedValues = explode(',', $parameter);
                if (!in_array($value, $allowedValues)) {
                    throw new Exception("The {$field} must be one of: " . $parameter);
                }
                break;
        }
    }

    /**
     * Sanitize input data
     */
    private function sanitizeData($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                $sanitized[$key] = $this->sanitizeValue($value);
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize a single value
     */
    private function sanitizeValue($value) {
        if (is_string($value)) {
            // Remove any HTML tags
            $value = strip_tags($value);
            
            // Convert special characters to HTML entities
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            
            // Remove any potential script injections
            $value = str_replace(['javascript:', 'data:', 'vbscript:'], '', $value);
            
            // Trim whitespace
            $value = trim($value);
        }
        
        return $value;
    }

    /**
     * Add custom validation rules
     */
    public function addRules($path, array $rules) {
        $this->rules[$path] = $rules;
    }
}
