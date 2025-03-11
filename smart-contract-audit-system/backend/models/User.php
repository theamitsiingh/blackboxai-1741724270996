<?php
namespace App\Models;

class User extends Model {
    protected $table = 'users';
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'last_login_at'
    ];

    /**
     * Create a new user
     */
    public function createUser(array $data) {
        // Hash password before saving
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        // Set default role if not provided
        if (!isset($data['role'])) {
            $data['role'] = 'user';
        }

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'active';
        }

        return $this->create($data);
    }

    /**
     * Update user information
     */
    public function updateUser($id, array $data) {
        // Hash password if it's being updated
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return $this->update($id, $data);
    }

    /**
     * Find user by email
     */
    public function findByEmail($email) {
        return $this->findOneBy('email', $email);
    }

    /**
     * Verify user password
     */
    public function verifyPassword($password, $hashedPassword) {
        return password_verify($password, $hashedPassword);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin($id) {
        $sql = "UPDATE {$this->table} SET last_login_at = NOW() WHERE id = :id";
        return $this->query($sql, ['id' => $id]);
    }

    /**
     * Get users by role
     */
    public function findByRole($role) {
        return $this->findBy('role', $role);
    }

    /**
     * Get active users
     */
    public function getActiveUsers() {
        return $this->findBy('status', 'active');
    }

    /**
     * Deactivate user
     */
    public function deactivateUser($id) {
        return $this->update($id, ['status' => 'inactive']);
    }

    /**
     * Activate user
     */
    public function activateUser($id) {
        return $this->update($id, ['status' => 'active']);
    }

    /**
     * Change user role
     */
    public function changeRole($id, $newRole) {
        return $this->update($id, ['role' => $newRole]);
    }

    /**
     * Get user's audits
     */
    public function getAudits($userId) {
        $sql = "SELECT * FROM audits WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->query($sql, ['user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get user's reports
     */
    public function getReports($userId) {
        $sql = "SELECT r.* FROM reports r 
                JOIN audits a ON r.audit_id = a.id 
                WHERE a.user_id = :user_id 
                ORDER BY r.generated_at DESC";
        $stmt = $this->query($sql, ['user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Search users
     */
    public function searchUsers($searchTerm) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE name LIKE :search 
                OR email LIKE :search";
        $stmt = $this->query($sql, ['search' => "%$searchTerm%"]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get user statistics
     */
    public function getUserStats($userId) {
        $sql = "SELECT 
                (SELECT COUNT(*) FROM audits WHERE user_id = :user_id) as total_audits,
                (SELECT COUNT(*) FROM audits WHERE user_id = :user_id AND status = 'completed') as completed_audits,
                (SELECT COUNT(*) FROM audits WHERE user_id = :user_id AND status = 'pending') as pending_audits";
        $stmt = $this->query($sql, ['user_id' => $userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
