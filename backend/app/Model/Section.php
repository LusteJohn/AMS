<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class Section extends Model
{
    protected string $table = 'section';

    public function __construct()
    {
        parent::__construct();
        $this->table = 'section';
    }

    public function create(array $data): int
    {
        $programId = (int)($data['program_id'] ?? 0);
        $section = trim($data['section'] ?? '');
        $section = htmlspecialchars($section, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $sql = "INSERT INTO {$this->table} (program_id, section_name) VALUES (:pid, :section)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':pid', $programId, PDO::PARAM_INT);
        $stmt->bindValue(':section', $section, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    public function getAll(): array
    {
        $sql = "SELECT s.section_id, s.program_id, s.section_name, p.program_name, c.college_name
                FROM {$this->table} s
                LEFT JOIN program p ON s.program_id = p.program_id
                LEFT JOIN college c ON p.college_id = c.college_id
                ORDER BY c.college_name, p.program_name, s.section_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $id = (int)$id;
        $sql = "SELECT * FROM {$this->table} WHERE section_id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function updateSection(int $id, array $data): int
    {
        $id = (int)$id;
        $programId = (int)($data['program_id'] ?? 0);
        $section = trim($data['section'] ?? '');
        $section = htmlspecialchars($section, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $sql = "UPDATE {$this->table} SET program_id = :pid, section_name = :section WHERE section_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':pid', $programId, PDO::PARAM_INT);
        $stmt->bindValue(':section', $section, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function deleteSection(int $id): int
    {
        $id = (int)$id;
        $sql = "DELETE FROM {$this->table} WHERE section_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function sections(int $programId): array
    {
        $programId = (int)$programId;
        $sql = "SELECT * FROM {$this->table} WHERE program_id = :pid ORDER BY section_name";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':pid', $programId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function program(int $programId): ?array
    {
        $programId = (int)$programId;
        $sql = "SELECT * FROM program WHERE program_id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $programId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
