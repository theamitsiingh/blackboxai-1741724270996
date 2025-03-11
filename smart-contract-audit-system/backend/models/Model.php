<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use Exception;

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Find a record by its primary key
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all records
     */
    public function all() {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new record
     */
    public function create(array $data) {
        $data = $this->filterFillable($data);
        
        $fields = array_keys($data);
        $fieldsStr = implode(', ', $fields);
        $valuesStr = implode(', ', array_map(fn($field) => ":$field", $fields));
        
        $sql = "INSERT INTO {$this->table} ($fieldsStr) VALUES ($valuesStr)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            return $this->find($this->db->lastInsertId());
        } catch (Exception $e) {
            throw new Exception("Failed to create record: " . $e->getMessage());
        }
    }

    /**
     * Update a record
     */
    public function update($id, array $data) {
        $data = $this->filterFillable($data);
        
        $fields = array_keys($data);
        $setStr = implode(', ', array_map(fn($field) => "$field = :$field", $fields));
        
        $sql = "UPDATE {$this->table} SET $setStr WHERE {$this->primaryKey} = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_merge($data, ['id' => $id]));
            return $this->find($id);
        } catch (Exception $e) {
            throw new Exception("Failed to update record: " . $e->getMessage());
        }
    }

    /**
     * Delete a record
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");
            return $stmt->execute(['id' => $id]);
        } catch (Exception $e) {
            throw new Exception("Failed to delete record: " . $e->getMessage());
        }
    }

    /**
     * Find records by specific field and value
     */
    public function findBy($field, $value) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE $field = :value");
        $stmt->execute(['value' => $value]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find one record by specific field and value
     */
    public function findOneBy($field, $value) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE $field = :value LIMIT 1");
        $stmt->execute(['value' => $value]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Filter data to only include fillable fields
     */
    protected function filterFillable(array $data) {
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Begin a database transaction
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }

    /**
     * Commit a database transaction
     */
    public function commit() {
        return $this->db->commit();
    }

    /**
     * Rollback a database transaction
     */
    public function rollback() {
        return $this->db->rollBack();
    }

    /**
     * Execute a custom query
     */
    protected function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (Exception $e) {
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }

    /**
     * Count total records
     */
    public function count() {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM {$this->table}");
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    /**
     * Paginate results
     */
    public function paginate($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $this->count(),
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($this->count() / $perPage)
        ];
    }
}
