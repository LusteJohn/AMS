<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class Faculty extends Model
{
    protected string $table = 'faculty';

    public function all(): array
    {
        return $this->fetchAll(
            'SELECT f.faculty_id, f.user_id, f.section_id, f.firstname, f.middlename,
                    f.lastname, f.name_ext, f.gender, f.address, f.created_at, f.updated_at,
                    u.username, u.email, sec.section_name, p.program_name, c.college_name
             FROM faculty f
             INNER JOIN users u ON u.user_id = f.user_id
             LEFT JOIN section sec ON sec.section_id = f.section_id
             LEFT JOIN program p ON p.program_id = sec.program_id
             LEFT JOIN college c ON c.college_id = p.college_id
             ORDER BY f.lastname, f.firstname'
        );
    }

    public function find(int $facultyId): ?array
    {
        return $this->fetch(
            'SELECT f.faculty_id, f.user_id, f.section_id, f.firstname, f.middlename,
                    f.lastname, f.name_ext, f.gender, f.address, f.created_at, f.updated_at,
                    u.username, u.email, sec.section_name, p.program_name, c.college_name
             FROM faculty f
             INNER JOIN users u ON u.user_id = f.user_id
             LEFT JOIN section sec ON sec.section_id = f.section_id
             LEFT JOIN program p ON p.program_id = sec.program_id
             LEFT JOIN college c ON c.college_id = p.college_id
             WHERE f.faculty_id = :faculty_id LIMIT 1',
            [':faculty_id' => $facultyId]
        );
    }

    public function createProfile(int $userId, array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO faculty
                (user_id, section_id, firstname, middlename, lastname, name_ext, gender, address)
             VALUES
                (:user_id, :section_id, :firstname, :middlename, :lastname, :name_ext, :gender, :address)'
        );
        $this->bindProfile($statement, $data);
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->execute();
        return (int) $this->db->lastInsertId();
    }

    public function updateProfile(int $facultyId, array $data): int
    {
        $statement = $this->db->prepare(
            'UPDATE faculty SET section_id = :section_id, firstname = :firstname,
                middlename = :middlename, lastname = :lastname, name_ext = :name_ext,
                gender = :gender, address = :address
             WHERE faculty_id = :faculty_id'
        );
        $this->bindProfile($statement, $data);
        $statement->bindValue(':faculty_id', $facultyId, PDO::PARAM_INT);
        $statement->execute();
        return $statement->rowCount();
    }

    public function deleteByUserId(int $userId): int
    {
        // The users -> faculty foreign key uses ON DELETE CASCADE.
        $statement = $this->db->prepare('DELETE FROM users WHERE user_id = :user_id');
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->execute();
        return $statement->rowCount();
    }

    private function bindProfile($statement, array $data): void
    {
        $statement->bindValue(':section_id', (int) $data['section_id'], PDO::PARAM_INT);
        $statement->bindValue(':firstname', $data['firstname'], PDO::PARAM_STR);
        $statement->bindValue(':middlename', $data['middlename'] ?: null, $data['middlename'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':lastname', $data['lastname'], PDO::PARAM_STR);
        $statement->bindValue(':name_ext', $data['name_ext'] ?: null, $data['name_ext'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':gender', $data['gender'], PDO::PARAM_STR);
        $statement->bindValue(':address', $data['address'], PDO::PARAM_STR);
    }
}
