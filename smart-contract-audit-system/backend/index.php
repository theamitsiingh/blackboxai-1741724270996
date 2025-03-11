<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use App\Utils\Logger;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize error handling
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? '0');

// Set headers for API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize logger
$logger = new Logger();

try {
    // Parse the URL
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', trim($uri, '/'));
    
    // Remove the base path segments (adjust based on your setup)
    $basePathSegments = explode('/', trim($_ENV['BASE_PATH'] ?? '', '/'));
    $uri = array_slice($uri, count($basePathSegments));
    
    // Route the request
    if (count($uri) < 2) {
        throw new Exception('Invalid endpoint');
    }
    
    $controller = ucfirst($uri[0]); // e.g., 'auth', 'admin', 'user'
    $action = $uri[1] ?? ''; // e.g., 'login', 'register'
    
    // Construct the controller class name
    $controllerClass = "App\\Api\\{$controller}Controller";
    
    if (!class_exists($controllerClass)) {
        throw new Exception('Controller not found');
    }
    
    // Initialize the controller
    $controller = new $controllerClass();
    
    if (!method_exists($controller, $action)) {
        throw new Exception('Action not found');
    }
    
    // Handle the request
    $response = $controller->$action();
    
    // Send the response
    echo json_encode([
        'status' => 'success',
        'data' => $response
    ]);

} catch (Exception $e) {
    $logger->error($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
