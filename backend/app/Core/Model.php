<?php

namespace App\Core;

use PDO;
use PDOStatement;

abstract class Model
{
    protected PDO $db;
    protected string $table;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getConnection(): PDO
    {
        return $this->db;
    }

    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected function fetch(string $sql, array $params = []): ?array
    {
        return $this->query($sql, $params)->fetch();
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    protected function insert(string $sql, array $params = []): int
    {
        $this->query($sql, $params);
        return (int)$this->db->lastInsertId();
    }

    protected function update(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    protected function delete(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    protected function appNow(): string
    {
        return date('Y-m-d H:i:s');
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        return $this->fetchAll($sql);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->fetch($sql, [$id]);
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->fetch($sql);
        return $result['count'] ?? 0;
    }
}
