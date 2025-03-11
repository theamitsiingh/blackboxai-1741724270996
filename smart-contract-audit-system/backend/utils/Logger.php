<?php
namespace App\Utils;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class Logger {
    private $logger;
    private static $instance = null;

    public function __construct() {
        $config = require __DIR__ . '/../config/config.php';
        $logConfig = $config['logging'];

        // Create logger instance
        $this->logger = new MonologLogger('smart-contract-audit');

        // Set up formatter
        $dateFormat = "Y-m-d H:i:s";
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat);

        // Add rotating file handler
        $handler = new RotatingFileHandler(
            $logConfig['path'],
            0,
            $this->getLogLevel($logConfig['level']),
            true,
            0644
        );
        $handler->setFormatter($formatter);
        $this->logger->pushHandler($handler);

        // Add stream handler for development
        if ($config['app']['env'] === 'development') {
            $streamHandler = new StreamHandler('php://stdout', $this->getLogLevel($logConfig['level']));
            $streamHandler->setFormatter($formatter);
            $this->logger->pushHandler($streamHandler);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function getLogLevel($level) {
        $levels = [
            'debug' => MonologLogger::DEBUG,
            'info' => MonologLogger::INFO,
            'notice' => MonologLogger::NOTICE,
            'warning' => MonologLogger::WARNING,
            'error' => MonologLogger::ERROR,
            'critical' => MonologLogger::CRITICAL,
            'alert' => MonologLogger::ALERT,
            'emergency' => MonologLogger::EMERGENCY,
        ];

        return $levels[strtolower($level)] ?? MonologLogger::DEBUG;
    }

    public function emergency($message, array $context = []) {
        $this->logger->emergency($message, $context);
    }

    public function alert($message, array $context = []) {
        $this->logger->alert($message, $context);
    }

    public function critical($message, array $context = []) {
        $this->logger->critical($message, $context);
    }

    public function error($message, array $context = []) {
        $this->logger->error($message, $context);
    }

    public function warning($message, array $context = []) {
        $this->logger->warning($message, $context);
    }

    public function notice($message, array $context = []) {
        $this->logger->notice($message, $context);
    }

    public function info($message, array $context = []) {
        $this->logger->info($message, $context);
    }

    public function debug($message, array $context = []) {
        $this->logger->debug($message, $context);
    }

    public function log($level, $message, array $context = []) {
        $this->logger->log($level, $message, $context);
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
