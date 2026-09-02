<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class Program extends Model
{
    protected string $table = 'program';

    public function __construct()
    {
        parent::__construct();
        $this->table = 'program';
    }

    public function create(array $data): int
    {
        $collegeId = (int)($data['college_id'] ?? 0);
        $program = trim($data['program'] ?? '');
        $program = htmlspecialchars($program, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $sql = "INSERT INTO {$this->table} (college_id, program_name) VALUES (:cid, :program)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cid', $collegeId, PDO::PARAM_INT);
        $stmt->bindValue(':program', $program, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY program_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByCollegeId(int $collegeId): array
    {
        $collegeId = (int)$collegeId;
        $sql = "SELECT * FROM {$this->table} WHERE college_id = :cid ORDER BY program_name";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cid', $collegeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $id = (int)$id;
        $sql = "SELECT * FROM {$this->table} WHERE program_id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function updateProgram(int $id, array $data): int
    {
        $id = (int)$id;
        $collegeId = (int)($data['college_id'] ?? 0);
        $program = trim($data['program'] ?? '');
        $program = htmlspecialchars($program, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $sql = "UPDATE {$this->table} SET college_id = :cid, program_name = :program WHERE program_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cid', $collegeId, PDO::PARAM_INT);
        $stmt->bindValue(':program', $program, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function deleteProgram(int $id): int
    {
        $id = (int)$id;

        try {
            $this->db->beginTransaction();

            // delete sections belonging to this program
            $delSectionsSql = "DELETE FROM section WHERE program_id = :pid";
            $delSectionsStmt = $this->db->prepare($delSectionsSql);
            $delSectionsStmt->bindValue(':pid', $id, PDO::PARAM_INT);
            $delSectionsStmt->execute();

            // delete program
            $sql = "DELETE FROM {$this->table} WHERE program_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $deleted = $stmt->rowCount();
            $this->db->commit();
            return $deleted;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return 0;
        }
    }

    public function sections(int $programId): array
    {
        $programId = (int)$programId;
        $sql = "SELECT * FROM section WHERE program_id = :pid ORDER BY section_name";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':pid', $programId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function college(int $collegeId): ?array
    {
        $collegeId = (int)$collegeId;
        $sql = "SELECT * FROM college WHERE college_id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $collegeId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
