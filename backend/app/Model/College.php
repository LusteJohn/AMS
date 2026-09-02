<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class College extends Model
{
    protected string $table = 'college';

    public function __construct()
    {
        parent::__construct();
        $this->table = 'college';
    }

    public function create(array $data): int
    {
        $name = trim($data['college_name'] ?? '');
        $name = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $sql = "INSERT INTO {$this->table} (college_name) VALUES (:name)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY college_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $id = (int)$id;
        $sql = "SELECT * FROM {$this->table} WHERE college_id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function updateCollege(int $id, array $data): int
    {
        $id = (int)$id;
        $name = trim($data['college_name'] ?? '');
        $name = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $sql = "UPDATE {$this->table} SET college_name = :name WHERE college_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function deleteCollege(int $id): int
    {
        $id = (int)$id;

        try {
            $this->db->beginTransaction();

            $sqlPrograms = "SELECT program_id FROM program WHERE college_id = :cid";
            $stmtPrograms = $this->db->prepare($sqlPrograms);
            $stmtPrograms->bindValue(':cid', $id, PDO::PARAM_INT);
            $stmtPrograms->execute();
            $programIds = $stmtPrograms->fetchAll(PDO::FETCH_COLUMN);

            // Delete sections for those programs (if any)
            if (!empty($programIds)) {
                $placeholders = implode(',', array_fill(0, count($programIds), '?'));
                $delSectionsSql = "DELETE FROM section WHERE program_id IN ({$placeholders})";
                $delSectionsStmt = $this->db->prepare($delSectionsSql);
                foreach ($programIds as $i => $pid) {
                    $delSectionsStmt->bindValue($i + 1, (int)$pid, PDO::PARAM_INT);
                }
                $delSectionsStmt->execute();
            }

            // Delete programs
            $delProgramsSql = "DELETE FROM program WHERE college_id = :cid";
            $delProgramsStmt = $this->db->prepare($delProgramsSql);
            $delProgramsStmt->bindValue(':cid', $id, PDO::PARAM_INT);
            $delProgramsStmt->execute();

            // Delete college
            $delCollegeSql = "DELETE FROM {$this->table} WHERE college_id = :id";
            $delCollegeStmt = $this->db->prepare($delCollegeSql);
            $delCollegeStmt->bindValue(':id', $id, PDO::PARAM_INT);
            $delCollegeStmt->execute();

            $deleted = $delCollegeStmt->rowCount();
            $this->db->commit();

            return $deleted;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return 0;
        }
    }

    public function programs(int $collegeId): array
    {
        $collegeId = (int)$collegeId;
        $sql = "SELECT * FROM program WHERE college_id = :cid ORDER BY program";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cid', $collegeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
