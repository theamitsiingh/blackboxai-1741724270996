<?php
namespace App\Api;

use App\Models\Audit;
use App\Models\Report;
use App\Models\Compliance;
use App\Utils\Logger;
use App\Middleware\AuthMiddleware;
use Exception;

class AuditController {
    private $auditModel;
    private $reportModel;
    private $complianceModel;
    private $logger;

    public function __construct() {
        $this->auditModel = new Audit();
        $this->reportModel = new Report();
        $this->complianceModel = new Compliance();
        $this->logger = new Logger();
    }

    /**
     * Create new audit request
     */
    public function create() {
        try {
            $data = $this->getRequestData();
            $currentUser = AuthMiddleware::getCurrentUser();

            // Add user ID to audit data
            $data['user_id'] = $currentUser['id'];
            
            // Create audit request
            $audit = $this->auditModel->createAudit($data);

            // Log audit creation
            $this->logger->info('New audit request created', [
                'audit_id' => $audit['id'],
                'user_id' => $currentUser['id']
            ]);

            return $audit;

        } catch (Exception $e) {
            $this->logger->error('Failed to create audit: ' . $e->getMessage());
            throw new Exception('Failed to create audit: ' . $e->getMessage());
        }
    }

    /**
     * Get audit details
     */
    public function get($id) {
        try {
            $currentUser = AuthMiddleware::getCurrentUser();
            $audit = $this->auditModel->getAuditWithDetails($id);

            if (!$audit) {
                throw new Exception('Audit not found');
            }

            // Check if user has access to this audit
            if ($currentUser['role'] !== 'admin' && $audit['user_id'] !== $currentUser['id']) {
                throw new Exception('Access denied');
            }

            // Get compliance checks
            $audit['compliance_checks'] = $this->complianceModel->checkAuditCompliance($id);
            
            // Get reports if any
            $audit['reports'] = $this->reportModel->getAuditReports($id);

            return $audit;

        } catch (Exception $e) {
            $this->logger->error('Failed to get audit: ' . $e->getMessage());
            throw new Exception('Failed to get audit: ' . $e->getMessage());
        }
    }

    /**
     * Update audit status
     */
    public function updateStatus($id) {
        try {
            $data = $this->getRequestData();
            $currentUser = AuthMiddleware::getCurrentUser();

            // Only admin can update audit status
            if ($currentUser['role'] !== 'admin') {
                throw new Exception('Only admin can update audit status');
            }

            $audit = $this->auditModel->updateStatus($id, $data['status'], $data['notes'] ?? null);

            $this->logger->info('Audit status updated', [
                'audit_id' => $id,
                'status' => $data['status'],
                'updated_by' => $currentUser['id']
            ]);

            return $audit;

        } catch (Exception $e) {
            $this->logger->error('Failed to update audit status: ' . $e->getMessage());
            throw new Exception('Failed to update audit status: ' . $e->getMessage());
        }
    }

    /**
     * Assign audit to admin/auditor
     */
    public function assign($id) {
        try {
            $data = $this->getRequestData();
            $currentUser = AuthMiddleware::getCurrentUser();

            // Only admin can assign audits
            if ($currentUser['role'] !== 'admin') {
                throw new Exception('Only admin can assign audits');
            }

            $audit = $this->auditModel->assignAudit($id, $data['assigned_to']);

            $this->logger->info('Audit assigned', [
                'audit_id' => $id,
                'assigned_to' => $data['assigned_to'],
                'assigned_by' => $currentUser['id']
            ]);

            return $audit;

        } catch (Exception $e) {
            $this->logger->error('Failed to assign audit: ' . $e->getMessage());
            throw new Exception('Failed to assign audit: ' . $e->getMessage());
        }
    }

    /**
     * List audits
     */
    public function list() {
        try {
            $currentUser = AuthMiddleware::getCurrentUser();
            $filters = $_GET;

            if ($currentUser['role'] === 'admin') {
                // Admins can see all audits with optional filters
                if (!empty($filters['status'])) {
                    $audits = $this->auditModel->getByStatus($filters['status']);
                } elseif (!empty($filters['assigned_to'])) {
                    $audits = $this->auditModel->getAssignedAudits($filters['assigned_to']);
                } else {
                    $audits = $this->auditModel->all();
                }
            } else {
                // Regular users can only see their own audits
                $audits = $this->auditModel->findBy('user_id', $currentUser['id']);
            }

            return $audits;

        } catch (Exception $e) {
            $this->logger->error('Failed to list audits: ' . $e->getMessage());
            throw new Exception('Failed to list audits: ' . $e->getMessage());
        }
    }

    /**
     * Get audit statistics
     */
    public function getStats() {
        try {
            AuthMiddleware::checkRole('admin');

            return [
                'overall_stats' => $this->auditModel->getAuditStats(),
                'pending_count' => $this->auditModel->getPendingCount(),
                'recent_audits' => $this->auditModel->getRecentAudits(5)
            ];

        } catch (Exception $e) {
            $this->logger->error('Failed to get audit statistics: ' . $e->getMessage());
            throw new Exception('Failed to get audit statistics: ' . $e->getMessage());
        }
    }

    /**
     * Search audits
     */
    public function search() {
        try {
            $currentUser = AuthMiddleware::getCurrentUser();
            $searchTerm = $_GET['q'] ?? '';

            if (empty($searchTerm)) {
                throw new Exception('Search term is required');
            }

            $results = $this->auditModel->searchAudits($searchTerm);

            // Filter results based on user role
            if ($currentUser['role'] !== 'admin') {
                $results = array_filter($results, function($audit) use ($currentUser) {
                    return $audit['user_id'] === $currentUser['id'];
                });
            }

            return array_values($results);

        } catch (Exception $e) {
            $this->logger->error('Failed to search audits: ' . $e->getMessage());
            throw new Exception('Failed to search audits: ' . $e->getMessage());
        }
    }

    /**
     * Get audit timeline
     */
    public function getTimeline($id) {
        try {
            $currentUser = AuthMiddleware::getCurrentUser();
            $audit = $this->auditModel->find($id);

            if (!$audit) {
                throw new Exception('Audit not found');
            }

            // Check access
            if ($currentUser['role'] !== 'admin' && $audit['user_id'] !== $currentUser['id']) {
                throw new Exception('Access denied');
            }

            return $this->auditModel->getAuditTimeline($id);

        } catch (Exception $e) {
            $this->logger->error('Failed to get audit timeline: ' . $e->getMessage());
            throw new Exception('Failed to get audit timeline: ' . $e->getMessage());
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
