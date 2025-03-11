<?php
namespace App\Api;

use App\Models\Report;
use App\Models\Audit;
use App\Utils\Logger;
use App\Middleware\AuthMiddleware;
use Exception;

class ReportController {
    private $reportModel;
    private $auditModel;
    private $logger;

    public function __construct() {
        $this->reportModel = new Report();
        $this->auditModel = new Audit();
        $this->logger = new Logger();
    }

    /**
     * Create new audit report
     */
    public function create() {
        try {
            $data = $this->getRequestData();
            $currentUser = AuthMiddleware::getCurrentUser();

            // Verify audit exists
            $audit = $this->auditModel->find($data['audit_id']);
            if (!$audit) {
                throw new Exception('Audit not found');
            }

            // Only admin or assigned auditor can create reports
            if ($currentUser['role'] !== 'admin' && $audit['assigned_to'] !== $currentUser['id']) {
                throw new Exception('Access denied');
            }

            // Add report metadata
            $data['generated_by'] = $currentUser['id'];
            $data['status'] = 'draft';

            // Create report
            $report = $this->reportModel->createReport($data);

            // Log report creation
            $this->logger->info('New audit report created', [
                'report_id' => $report['id'],
                'audit_id' => $data['audit_id'],
                'created_by' => $currentUser['id']
            ]);

            return $report;

        } catch (Exception $e) {
            $this->logger->error('Failed to create report: ' . $e->getMessage());
            throw new Exception('Failed to create report: ' . $e->getMessage());
        }
    }

    /**
     * Get report details
     */
    public function get($id) {
        try {
            $currentUser = AuthMiddleware::getCurrentUser();
            $report = $this->reportModel->getReportWithDetails($id);

            if (!$report) {
                throw new Exception('Report not found');
            }

            // Check access rights
            $audit = $this->auditModel->find($report['audit_id']);
            if ($currentUser['role'] !== 'admin' && 
                $audit['user_id'] !== $currentUser['id'] && 
                $audit['assigned_to'] !== $currentUser['id']) {
                throw new Exception('Access denied');
            }

            return $report;

        } catch (Exception $e) {
            $this->logger->error('Failed to get report: ' . $e->getMessage());
            throw new Exception('Failed to get report: ' . $e->getMessage());
        }
    }

    /**
     * Update report status
     */
    public function updateStatus($id) {
        try {
            $data = $this->getRequestData();
            $currentUser = AuthMiddleware::getCurrentUser();

            // Only admin can update report status
            if ($currentUser['role'] !== 'admin') {
                throw new Exception('Only admin can update report status');
            }

            $report = $this->reportModel->updateStatus($id, $data['status']);

            $this->logger->info('Report status updated', [
                'report_id' => $id,
                'status' => $data['status'],
                'updated_by' => $currentUser['id']
            ]);

            return $report;

        } catch (Exception $e) {
            $this->logger->error('Failed to update report status: ' . $e->getMessage());
            throw new Exception('Failed to update report status: ' . $e->getMessage());
        }
    }

    /**
     * List reports
     */
    public function list() {
        try {
            $currentUser = AuthMiddleware::getCurrentUser();
            $filters = $_GET;

            if ($currentUser['role'] === 'admin') {
                // Admins can see all reports with optional filters
                if (!empty($filters['risk_level'])) {
                    $reports = $this->reportModel->getByRiskLevel($filters['risk_level']);
                } else {
                    $reports = $this->reportModel->all();
                }
            } else {
                // Get audits where user is either owner or assigned auditor
                $sql = "SELECT r.* FROM reports r 
                        JOIN audits a ON r.audit_id = a.id 
                        WHERE a.user_id = :user_id 
                        OR a.assigned_to = :user_id";
                $reports = $this->reportModel->query($sql, ['user_id' => $currentUser['id']])->fetchAll();
            }

            return $reports;

        } catch (Exception $e) {
            $this->logger->error('Failed to list reports: ' . $e->getMessage());
            throw new Exception('Failed to list reports: ' . $e->getMessage());
        }
    }

    /**
     * Get report statistics
     */
    public function getStats() {
        try {
            AuthMiddleware::checkRole('admin');

            return [
                'risk_level_stats' => $this->reportModel->getReportStats(),
                'recent_reports' => $this->reportModel->getRecentReports(5)
            ];

        } catch (Exception $e) {
            $this->logger->error('Failed to get report statistics: ' . $e->getMessage());
            throw new Exception('Failed to get report statistics: ' . $e->getMessage());
        }
    }

    /**
     * Search reports
     */
    public function search() {
        try {
            $currentUser = AuthMiddleware::getCurrentUser();
            $searchTerm = $_GET['q'] ?? '';

            if (empty($searchTerm)) {
                throw new Exception('Search term is required');
            }

            $results = $this->reportModel->searchReports($searchTerm);

            // Filter results based on user role
            if ($currentUser['role'] !== 'admin') {
                $results = array_filter($results, function($report) use ($currentUser) {
                    $audit = $this->auditModel->find($report['audit_id']);
                    return $audit['user_id'] === $currentUser['id'] || 
                           $audit['assigned_to'] === $currentUser['id'];
                });
            }

            return array_values($results);

        } catch (Exception $e) {
            $this->logger->error('Failed to search reports: ' . $e->getMessage());
            throw new Exception('Failed to search reports: ' . $e->getMessage());
        }
    }

    /**
     * Export report
     */
    public function export($id) {
        try {
            $currentUser = AuthMiddleware::getCurrentUser();
            $report = $this->reportModel->getReportWithDetails($id);

            if (!$report) {
                throw new Exception('Report not found');
            }

            // Check access rights
            $audit = $this->auditModel->find($report['audit_id']);
            if ($currentUser['role'] !== 'admin' && 
                $audit['user_id'] !== $currentUser['id'] && 
                $audit['assigned_to'] !== $currentUser['id']) {
                throw new Exception('Access denied');
            }

            return $this->reportModel->exportToJson($id);

        } catch (Exception $e) {
            $this->logger->error('Failed to export report: ' . $e->getMessage());
            throw new Exception('Failed to export report: ' . $e->getMessage());
        }
    }

    /**
     * Add feedback to report
     */
    public function addFeedback($id) {
        try {
            $data = $this->getRequestData();
            $currentUser = AuthMiddleware::getCurrentUser();

            if (empty($data['feedback'])) {
                throw new Exception('Feedback is required');
            }

            // Check access rights
            $report = $this->reportModel->find($id);
            if (!$report) {
                throw new Exception('Report not found');
            }

            $audit = $this->auditModel->find($report['audit_id']);
            if ($currentUser['role'] !== 'admin' && $audit['user_id'] !== $currentUser['id']) {
                throw new Exception('Access denied');
            }

            $this->reportModel->addFeedback($id, $data['feedback'], $currentUser['id']);

            $this->logger->info('Report feedback added', [
                'report_id' => $id,
                'user_id' => $currentUser['id']
            ]);

            return ['message' => 'Feedback added successfully'];

        } catch (Exception $e) {
            $this->logger->error('Failed to add feedback: ' . $e->getMessage());
            throw new Exception('Failed to add feedback: ' . $e->getMessage());
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
