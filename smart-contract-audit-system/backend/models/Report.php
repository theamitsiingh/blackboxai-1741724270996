<?php
namespace App\Models;

class Report extends Model {
    protected $table = 'reports';
    protected $fillable = [
        'audit_id',
        'report_type',
        'findings',
        'risk_level',
        'recommendations',
        'summary',
        'detailed_analysis',
        'generated_by',
        'generated_at',
        'status',
        'version'
    ];

    /**
     * Create a new audit report
     */
    public function createReport(array $data) {
        // Set default values
        $data['generated_at'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 'draft';
        $data['version'] = $this->getNextVersion($data['audit_id']);
        
        return $this->create($data);
    }

    /**
     * Get the next version number for an audit's report
     */
    private function getNextVersion($auditId) {
        $sql = "SELECT MAX(version) as max_version FROM {$this->table} WHERE audit_id = :audit_id";
        $stmt = $this->query($sql, ['audit_id' => $auditId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return ($result['max_version'] ?? 0) + 1;
    }

    /**
     * Get report with full details
     */
    public function getReportWithDetails($reportId) {
        $sql = "SELECT r.*, a.contract_name, a.contract_address, 
                u.name as generated_by_name, u.email as generated_by_email
                FROM {$this->table} r
                LEFT JOIN audits a ON r.audit_id = a.id
                LEFT JOIN users u ON r.generated_by = u.id
                WHERE r.id = :report_id";
        
        $stmt = $this->query($sql, ['report_id' => $reportId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get all reports for an audit
     */
    public function getAuditReports($auditId) {
        return $this->findBy('audit_id', $auditId);
    }

    /**
     * Update report status
     */
    public function updateStatus($reportId, $status) {
        return $this->update($reportId, ['status' => $status]);
    }

    /**
     * Get reports by risk level
     */
    public function getByRiskLevel($riskLevel) {
        return $this->findBy('risk_level', $riskLevel);
    }

    /**
     * Get reports statistics
     */
    public function getReportStats() {
        $sql = "SELECT 
                risk_level,
                COUNT(*) as count,
                AVG(CHAR_LENGTH(findings)) as avg_findings_length,
                AVG(CHAR_LENGTH(recommendations)) as avg_recommendations_length
                FROM {$this->table}
                GROUP BY risk_level";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Search reports
     */
    public function searchReports($searchTerm) {
        $sql = "SELECT r.*, a.contract_name 
                FROM {$this->table} r
                LEFT JOIN audits a ON r.audit_id = a.id
                WHERE r.findings LIKE :search 
                OR r.recommendations LIKE :search
                OR a.contract_name LIKE :search";
        
        $stmt = $this->query($sql, ['search' => "%$searchTerm%"]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get recent reports
     */
    public function getRecentReports($limit = 10) {
        $sql = "SELECT r.*, a.contract_name, u.name as generated_by_name 
                FROM {$this->table} r
                LEFT JOIN audits a ON r.audit_id = a.id
                LEFT JOIN users u ON r.generated_by = u.id
                ORDER BY r.generated_at DESC 
                LIMIT :limit";
        
        $stmt = $this->query($sql, ['limit' => $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get report history
     */
    public function getReportHistory($auditId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE audit_id = :audit_id 
                ORDER BY version DESC";
        
        $stmt = $this->query($sql, ['audit_id' => $auditId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Export report to JSON
     */
    public function exportToJson($reportId) {
        $report = $this->getReportWithDetails($reportId);
        return json_encode($report, JSON_PRETTY_PRINT);
    }

    /**
     * Get reports requiring review
     */
    public function getReportsForReview() {
        return $this->findBy('status', 'pending_review');
    }

    /**
     * Add report feedback
     */
    public function addFeedback($reportId, $feedback, $userId) {
        $sql = "INSERT INTO report_feedback (report_id, feedback, user_id, created_at) 
                VALUES (:report_id, :feedback, :user_id, NOW())";
        
        return $this->query($sql, [
            'report_id' => $reportId,
            'feedback' => $feedback,
            'user_id' => $userId
        ]);
    }
}
