<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class Student extends Model
{
    protected string $table = 'student';

    public function findByUserId(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT s.student_id, s.user_id, s.section_id, s.school_id,
                    s.firstname, s.middlename, s.lastname, s.name_ext,
                    s.gender, s.address, s.created_at, s.updated_at,
                    sec.section_name, p.program_name, c.college_name
             FROM student s
             LEFT JOIN section sec ON sec.section_id = s.section_id
             LEFT JOIN program p ON p.program_id = sec.program_id
             LEFT JOIN college c ON c.college_id = p.college_id
             WHERE s.user_id = :user_id
             LIMIT 1'
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetch() ?: null;
    }

    public function all(): array
    {
        return $this->fetchAll(
            'SELECT s.student_id, s.user_id, s.section_id, s.school_id,
                    s.firstname, s.middlename, s.lastname, s.name_ext,
                    s.gender, s.address, s.created_at, s.updated_at,
                    u.username, u.email, sec.section_name,
                    p.program_name, c.college_name
             FROM student s
             INNER JOIN users u ON u.user_id = s.user_id
             LEFT JOIN section sec ON sec.section_id = s.section_id
             LEFT JOIN program p ON p.program_id = sec.program_id
             LEFT JOIN college c ON c.college_id = p.college_id
             ORDER BY s.lastname, s.firstname'
        );
    }

    public function createProfile(int $userId, array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO student
                (user_id, section_id, school_id, firstname, middlename, lastname, name_ext, gender, address)
             VALUES
                (:user_id, :section_id, :school_id, :firstname, :middlename, :lastname, :name_ext, :gender, :address)'
        );
        $this->bindProfile($statement, $userId, $data);
        $statement->execute();
        return (int) $this->db->lastInsertId();
    }

    public function updateProfile(int $studentId, array $data): int
    {
        $statement = $this->db->prepare(
            'UPDATE student SET
                section_id = :section_id, school_id = :school_id,
                firstname = :firstname, middlename = :middlename,
                lastname = :lastname, name_ext = :name_ext,
                gender = :gender, address = :address
             WHERE student_id = :student_id'
        );
        $this->bindProfile($statement, null, $data);
        $statement->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $statement->execute();
        return $statement->rowCount();
    }

    public function deleteProfile(int $studentId): int
    {
        $statement = $this->db->prepare('DELETE FROM student WHERE student_id = :student_id');
        $statement->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $statement->execute();
        return $statement->rowCount();
    }

    private function bindProfile($statement, ?int $userId, array $data): void
    {
        if ($userId !== null) {
            $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
        $statement->bindValue(':section_id', (int) $data['section_id'], PDO::PARAM_INT);
        if ($data['school_id'] === null) {
            $statement->bindValue(':school_id', null, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':school_id', (int) $data['school_id'], PDO::PARAM_INT);
        }
        $statement->bindValue(':firstname', $data['firstname'], PDO::PARAM_STR);
        $statement->bindValue(':middlename', $data['middlename'] ?: null, $data['middlename'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':lastname', $data['lastname'], PDO::PARAM_STR);
        $statement->bindValue(':name_ext', $data['name_ext'] ?: null, $data['name_ext'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':gender', $data['gender'], PDO::PARAM_STR);
        $statement->bindValue(':address', $data['address'], PDO::PARAM_STR);
    }
}
