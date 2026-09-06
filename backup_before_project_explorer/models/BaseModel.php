<?php
class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct() { $this->db = db(); }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findAll($conditions = [], $orderBy = 'created_at DESC', $limit = null, $offset = null) {
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL";
        $params = [];
        foreach ($conditions as $key => $value) { $sql .= " AND {$key} = ?"; $params[] = $value; }
        $sql .= " ORDER BY {$orderBy}";
        if ($limit !== null) { $sql .= " LIMIT ?"; $params[] = $limit; if ($offset !== null) { $sql .= " OFFSET ?"; $params[] = $offset; } }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE deleted_at IS NULL";
        $params = [];
        foreach ($conditions as $key => $value) { $sql .= " AND {$key} = ?"; $params[] = $value; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    }

    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $set = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $params = array_values($data);
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?");
        return $stmt->execute($params);
    }

    public function softDelete($id) {
        return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }
}
