<?php
namespace App\Models;

class Audit extends Model {
    protected $table = 'audits';
    protected $fillable = [
        'user_id',
        'contract_name',
        'contract_address',
        'contract_network',
        'contract_source',
        'status',
        'priority',
        'audit_type',
        'submission_date',
        'completion_date',
        'assigned_to',
        'notes'
    ];

    /**
     * Create a new audit request
     */
    public function createAudit(array $data) {
        // Set default values
        $data['status'] = $data['status'] ?? 'pending';
        $data['submission_date'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }

    /**
     * Update audit status
     */
    public function updateStatus($id, $status, $notes = null) {
        $data = ['status' => $status];
        
        if ($status === 'completed') {
            $data['completion_date'] = date('Y-m-d H:i:s');
        }
        
        if ($notes) {
            $data['notes'] = $notes;
        }
        
        return $this->update($id, $data);
    }

    /**
     * Assign audit to an admin/auditor
     */
    public function assignAudit($auditId, $adminId) {
        return $this->update($auditId, [
            'assigned_to' => $adminId,
            'status' => 'in_progress'
        ]);
    }

    /**
     * Get audits by status
     */
    public function getByStatus($status) {
        return $this->findBy('status', $status);
    }

    /**
     * Get audits assigned to specific admin/auditor
     */
    public function getAssignedAudits($adminId) {
        return $this->findBy('assigned_to', $adminId);
    }

    /**
     * Get audit with related data
     */
    public function getAuditWithDetails($auditId) {
        $sql = "SELECT a.*, u.name as user_name, u.email as user_email,
                admin.name as assigned_to_name
                FROM {$this->table} a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN users admin ON a.assigned_to = admin.id
                WHERE a.id = :audit_id";
        
        $stmt = $this->query($sql, ['audit_id' => $auditId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get audits by priority
     */
    public function getByPriority($priority) {
        return $this->findBy('priority', $priority);
    }

    /**
     * Get pending audits count
     */
    public function getPendingCount() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'pending'";
        $stmt = $this->query($sql);
        return $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
    }

    /**
     * Get audits statistics
     */
    public function getAuditStats() {
        $sql = "SELECT 
                status,
                COUNT(*) as count,
                AVG(TIMESTAMPDIFF(HOUR, submission_date, 
                    CASE 
                        WHEN completion_date IS NOT NULL THEN completion_date
                        ELSE NOW()
                    END
                )) as avg_duration
                FROM {$this->table}
                GROUP BY status";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Search audits
     */
    public function searchAudits($searchTerm) {
        $sql = "SELECT a.*, u.name as user_name 
                FROM {$this->table} a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.contract_name LIKE :search 
                OR a.contract_address LIKE :search
                OR u.name LIKE :search";
        
        $stmt = $this->query($sql, ['search' => "%$searchTerm%"]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get recent audits
     */
    public function getRecentAudits($limit = 10) {
        $sql = "SELECT a.*, u.name as user_name 
                FROM {$this->table} a
                LEFT JOIN users u ON a.user_id = u.id
                ORDER BY a.submission_date DESC 
                LIMIT :limit";
        
        $stmt = $this->query($sql, ['limit' => $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get audit timeline
     */
    public function getAuditTimeline($auditId) {
        $sql = "SELECT * FROM audit_history 
                WHERE audit_id = :audit_id 
                ORDER BY created_at DESC";
        
        $stmt = $this->query($sql, ['audit_id' => $auditId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
