<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class OjtStudentCompany extends Model
{
    protected string $table = 'ojt_student_company';

    public function all(?int $studentId = null): array
    {
        $sql = 'SELECT osc.student_company_id, osc.company_id, osc.student_id,
                       osc.ojt_start_date, osc.ojt_end_date, osc.required_hours,
                       osc.status, osc.created_at, osc.updated_at,
                       c.company_name, s.school_id, s.firstname, s.middlename,
                       s.lastname, sec.section_name, p.program_name, col.college_name
                FROM ojt_student_company osc
                INNER JOIN ojt_company c ON c.company_id = osc.company_id
                INNER JOIN student s ON s.student_id = osc.student_id
                LEFT JOIN section sec ON sec.section_id = s.section_id
                LEFT JOIN program p ON p.program_id = sec.program_id
                LEFT JOIN college col ON col.college_id = p.college_id';
        $params = [];
        if ($studentId !== null) {
            $sql .= ' WHERE osc.student_id = :student_id';
            $params[':student_id'] = $studentId;
        }
        $sql .= ' ORDER BY osc.created_at DESC';
        return $this->fetchAll($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->fetch(
            'SELECT osc.student_company_id, osc.company_id, osc.student_id,
                    osc.ojt_start_date, osc.ojt_end_date, osc.required_hours,
                    osc.status, osc.created_at, osc.updated_at,
                    c.company_name, s.school_id, s.firstname, s.middlename,
                    s.lastname, sec.section_name, p.program_name, col.college_name
             FROM ojt_student_company osc
             INNER JOIN ojt_company c ON c.company_id = osc.company_id
             INNER JOIN student s ON s.student_id = osc.student_id
             LEFT JOIN section sec ON sec.section_id = s.section_id
             LEFT JOIN program p ON p.program_id = sec.program_id
             LEFT JOIN college col ON col.college_id = p.college_id
             WHERE osc.student_company_id = :student_company_id LIMIT 1',
            [':student_company_id' => $id]
        );
    }

    public function createAssignment(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO ojt_student_company
                (company_id, student_id, ojt_start_date, ojt_end_date, required_hours, status)
             VALUES
                (:company_id, :student_id, :ojt_start_date, :ojt_end_date, :required_hours, :status)'
        );
        $this->bindAssignment($statement, $data);
        $statement->execute();
        return (int) $this->db->lastInsertId();
    }

    public function updateAssignment(int $id, array $data): int
    {
        $statement = $this->db->prepare(
            'UPDATE ojt_student_company SET company_id = :company_id,
                student_id = :student_id, ojt_start_date = :ojt_start_date,
                ojt_end_date = :ojt_end_date, required_hours = :required_hours,
                status = :status
             WHERE student_company_id = :student_company_id'
        );
        $this->bindAssignment($statement, $data);
        $statement->bindValue(':student_company_id', $id, PDO::PARAM_INT);
        $statement->execute();
        return $statement->rowCount();
    }

    public function deleteAssignment(int $id): int
    {
        $statement = $this->db->prepare('DELETE FROM ojt_student_company WHERE student_company_id = :student_company_id');
        $statement->bindValue(':student_company_id', $id, PDO::PARAM_INT);
        $statement->execute();
        return $statement->rowCount();
    }

    private function bindAssignment($statement, array $data): void
    {
        $statement->bindValue(':company_id', $data['company_id'], PDO::PARAM_INT);
        $statement->bindValue(':student_id', $data['student_id'], PDO::PARAM_INT);
        $statement->bindValue(':ojt_start_date', $data['ojt_start_date'], PDO::PARAM_STR);
        $statement->bindValue(':ojt_end_date', $data['ojt_end_date'], PDO::PARAM_STR);
        $statement->bindValue(':required_hours', $data['required_hours'], PDO::PARAM_STR);
        $statement->bindValue(':status', $data['status'], PDO::PARAM_STR);
    }
}
