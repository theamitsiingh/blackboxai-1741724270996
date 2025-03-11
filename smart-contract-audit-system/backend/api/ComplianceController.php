<?php
namespace App\Api;

use App\Models\Compliance;
use App\Utils\Logger;
use App\Middleware\AuthMiddleware;
use Exception;

class ComplianceController {
    private $complianceModel;
    private $logger;

    public function __construct() {
        $this->complianceModel = new Compliance();
        $this->logger = new Logger();
    }

    /**
     * Create new compliance standard
     */
    public function create() {
        try {
            AuthMiddleware::checkRole('admin');
            
            $data = $this->getRequestData();
            $currentUser = AuthMiddleware::getCurrentUser();

            // Add creator information
            $data['created_by'] = $currentUser['id'];
            
            // Create compliance standard
            $standard = $this->complianceModel->createStandard($data);

            $this->logger->info('New compliance standard created', [
                'standard_id' => $standard['id'],
                'created_by' => $currentUser['id']
            ]);

            return $standard;

        } catch (Exception $e) {
            $this->logger->error('Failed to create compliance standard: ' . $e->getMessage());
            throw new Exception('Failed to create compliance standard: ' . $e->getMessage());
        }
    }

    /**
     * Update compliance standard
     */
    public function update($id) {
        try {
            AuthMiddleware::checkRole('admin');
            
            $data = $this->getRequestData();
            $currentUser = AuthMiddleware::getCurrentUser();

            // Update standard
            $standard = $this->complianceModel->updateStandard($id, $data);

            $this->logger->info('Compliance standard updated', [
                'standard_id' => $id,
                'updated_by' => $currentUser['id']
            ]);

            return $standard;

        } catch (Exception $e) {
            $this->logger->error('Failed to update compliance standard: ' . $e->getMessage());
            throw new Exception('Failed to update compliance standard: ' . $e->getMessage());
        }
    }

    /**
     * Get compliance standard details
     */
    public function get($id) {
        try {
            $standard = $this->complianceModel->getStandardWithDetails($id);

            if (!$standard) {
                throw new Exception('Compliance standard not found');
            }

            return $standard;

        } catch (Exception $e) {
            $this->logger->error('Failed to get compliance standard: ' . $e->getMessage());
            throw new Exception('Failed to get compliance standard: ' . $e->getMessage());
        }
    }

    /**
     * List compliance standards
     */
    public function list() {
        try {
            $filters = $_GET;

            if (!empty($filters['category'])) {
                $standards = $this->complianceModel->getByCategory($filters['category']);
            } elseif (!empty($filters['severity'])) {
                $standards = $this->complianceModel->getBySeverity($filters['severity']);
            } else {
                $standards = $this->complianceModel->getActiveStandards();
            }

            return $standards;

        } catch (Exception $e) {
            $this->logger->error('Failed to list compliance standards: ' . $e->getMessage());
            throw new Exception('Failed to list compliance standards: ' . $e->getMessage());
        }
    }

    /**
     * Check audit compliance
     */
    public function checkAuditCompliance($auditId) {
        try {
            $currentUser = AuthMiddleware::getCurrentUser();
            
            // Check if user has access to this audit
            // This would typically be handled by the AuditController
            
            $complianceResults = $this->complianceModel->checkAuditCompliance($auditId);

            return $complianceResults;

        } catch (Exception $e) {
            $this->logger->error('Failed to check audit compliance: ' . $e->getMessage());
            throw new Exception('Failed to check audit compliance: ' . $e->getMessage());
        }
    }

    /**
     * Record compliance check result
     */
    public function recordComplianceCheck($auditId) {
        try {
            AuthMiddleware::checkRole('admin');
            
            $data = $this->getRequestData();
            
            if (empty($data['compliance_id']) || empty($data['status'])) {
                throw new Exception('Compliance ID and status are required');
            }

            $result = $this->complianceModel->recordComplianceCheck(
                $auditId,
                $data['compliance_id'],
                $data['status'],
                $data['notes'] ?? null
            );

            $this->logger->info('Compliance check recorded', [
                'audit_id' => $auditId,
                'compliance_id' => $data['compliance_id'],
                'status' => $data['status']
            ]);

            return $result;

        } catch (Exception $e) {
            $this->logger->error('Failed to record compliance check: ' . $e->getMessage());
            throw new Exception('Failed to record compliance check: ' . $e->getMessage());
        }
    }

    /**
     * Get compliance statistics
     */
    public function getStats() {
        try {
            AuthMiddleware::checkRole('admin');

            return [
                'category_stats' => $this->complianceModel->getComplianceStats(),
                'upcoming_changes' => $this->complianceModel->getUpcomingChanges()
            ];

        } catch (Exception $e) {
            $this->logger->error('Failed to get compliance statistics: ' . $e->getMessage());
            throw new Exception('Failed to get compliance statistics: ' . $e->getMessage());
        }
    }

    /**
     * Search compliance standards
     */
    public function search() {
        try {
            $searchTerm = $_GET['q'] ?? '';

            if (empty($searchTerm)) {
                throw new Exception('Search term is required');
            }

            return $this->complianceModel->searchStandards($searchTerm);

        } catch (Exception $e) {
            $this->logger->error('Failed to search compliance standards: ' . $e->getMessage());
            throw new Exception('Failed to search compliance standards: ' . $e->getMessage());
        }
    }

    /**
     * Archive compliance standard
     */
    public function archive($id) {
        try {
            AuthMiddleware::checkRole('admin');
            
            $currentUser = AuthMiddleware::getCurrentUser();
            
            $this->complianceModel->archiveStandard($id, $currentUser['id']);

            $this->logger->info('Compliance standard archived', [
                'standard_id' => $id,
                'archived_by' => $currentUser['id']
            ]);

            return ['message' => 'Compliance standard archived successfully'];

        } catch (Exception $e) {
            $this->logger->error('Failed to archive compliance standard: ' . $e->getMessage());
            throw new Exception('Failed to archive compliance standard: ' . $e->getMessage());
        }
    }

    /**
     * Get compliance standard history
     */
    public function getHistory($id) {
        try {
            AuthMiddleware::checkRole('admin');
            
            return $this->complianceModel->getComplianceHistory($id);

        } catch (Exception $e) {
            $this->logger->error('Failed to get compliance history: ' . $e->getMessage());
            throw new Exception('Failed to get compliance history: ' . $e->getMessage());
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
