<?php
namespace App\Models;

class Compliance extends Model {
    protected $table = 'compliance';
    protected $fillable = [
        'standard_name',
        'version',
        'category',
        'description',
        'requirements',
        'validation_rules',
        'severity_level',
        'created_by',
        'status',
        'last_updated',
        'effective_date'
    ];

    /**
     * Create a new compliance standard
     */
    public function createStandard(array $data) {
        // Set default values
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['last_updated'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 'active';
        
        return $this->create($data);
    }

    /**
     * Get active compliance standards
     */
    public function getActiveStandards() {
        return $this->findBy('status', 'active');
    }

    /**
     * Get standards by category
     */
    public function getByCategory($category) {
        return $this->findBy('category', $category);
    }

    /**
     * Get standards by severity level
     */
    public function getBySeverity($severityLevel) {
        return $this->findBy('severity_level', $severityLevel);
    }

    /**
     * Update compliance standard
     */
    public function updateStandard($id, array $data) {
        $data['last_updated'] = date('Y-m-d H:i:s');
        return $this->update($id, $data);
    }

    /**
     * Get compliance standard with full details
     */
    public function getStandardWithDetails($standardId) {
        $sql = "SELECT c.*, u.name as created_by_name 
                FROM {$this->table} c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.id = :standard_id";
        
        $stmt = $this->query($sql, ['standard_id' => $standardId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Check if audit meets compliance standards
     */
    public function checkAuditCompliance($auditId) {
        $sql = "SELECT c.*, ac.status as compliance_status, ac.notes
                FROM {$this->table} c
                LEFT JOIN audit_compliance ac ON ac.compliance_id = c.id 
                    AND ac.audit_id = :audit_id
                WHERE c.status = 'active'";
        
        $stmt = $this->query($sql, ['audit_id' => $auditId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Record audit compliance check
     */
    public function recordComplianceCheck($auditId, $complianceId, $status, $notes = null) {
        $sql = "INSERT INTO audit_compliance 
                (audit_id, compliance_id, status, notes, checked_at) 
                VALUES (:audit_id, :compliance_id, :status, :notes, NOW())";
        
        return $this->query($sql, [
            'audit_id' => $auditId,
            'compliance_id' => $complianceId,
            'status' => $status,
            'notes' => $notes
        ]);
    }

    /**
     * Get compliance statistics
     */
    public function getComplianceStats() {
        $sql = "SELECT 
                c.category,
                COUNT(*) as total_standards,
                SUM(CASE WHEN c.status = 'active' THEN 1 ELSE 0 END) as active_standards,
                AVG(CASE 
                    WHEN ac.status = 'compliant' THEN 1
                    WHEN ac.status = 'non_compliant' THEN 0
                    ELSE NULL
                END) as compliance_rate
                FROM {$this->table} c
                LEFT JOIN audit_compliance ac ON ac.compliance_id = c.id
                GROUP BY c.category";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Search compliance standards
     */
    public function searchStandards($searchTerm) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE standard_name LIKE :search 
                OR description LIKE :search
                OR requirements LIKE :search";
        
        $stmt = $this->query($sql, ['search' => "%$searchTerm%"]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get compliance history
     */
    public function getComplianceHistory($standardId) {
        $sql = "SELECT ch.*, u.name as modified_by_name 
                FROM compliance_history ch
                LEFT JOIN users u ON ch.modified_by = u.id
                WHERE ch.compliance_id = :standard_id 
                ORDER BY ch.modified_at DESC";
        
        $stmt = $this->query($sql, ['standard_id' => $standardId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Archive compliance standard
     */
    public function archiveStandard($standardId, $userId) {
        $this->update($standardId, [
            'status' => 'archived',
            'last_updated' => date('Y-m-d H:i:s')
        ]);

        // Record in history
        $sql = "INSERT INTO compliance_history 
                (compliance_id, action, modified_by, modified_at) 
                VALUES (:compliance_id, 'archived', :modified_by, NOW())";
        
        return $this->query($sql, [
            'compliance_id' => $standardId,
            'modified_by' => $userId
        ]);
    }

    /**
     * Get upcoming standard changes
     */
    public function getUpcomingChanges() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE effective_date > NOW() 
                AND status = 'pending'
                ORDER BY effective_date ASC";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
