<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class CompanySupervisor extends Model
{
    protected string $table = 'company_supervisors';

    public function all(?int $companyId = null): array
    {
        $sql = 'SELECT s.supervisor_id, s.company_id, s.firstname, s.middlename,
                       s.lastname, s.name_ext, s.gender, s.address,
                       s.created_at, s.updated_at, c.company_name
                FROM company_supervisors s
                INNER JOIN ojt_company c ON c.company_id = s.company_id';
        $params = [];
        if ($companyId !== null) {
            $sql .= ' WHERE s.company_id = :company_id';
            $params[':company_id'] = $companyId;
        }
        $sql .= ' ORDER BY s.lastname, s.firstname';
        return $this->fetchAll($sql, $params);
    }

    public function find(int $supervisorId): ?array
    {
        return $this->fetch(
            'SELECT s.supervisor_id, s.company_id, s.firstname, s.middlename,
                    s.lastname, s.name_ext, s.gender, s.address,
                    s.created_at, s.updated_at, c.company_name
             FROM company_supervisors s
             INNER JOIN ojt_company c ON c.company_id = s.company_id
             WHERE s.supervisor_id = :supervisor_id LIMIT 1',
            [':supervisor_id' => $supervisorId]
        );
    }

    public function createSupervisor(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO company_supervisors
                (company_id, firstname, middlename, lastname, name_ext, gender, address)
             VALUES
                (:company_id, :firstname, :middlename, :lastname, :name_ext, :gender, :address)'
        );
        $this->bindSupervisor($statement, $data);
        $statement->execute();
        return (int) $this->db->lastInsertId();
    }

    public function updateSupervisor(int $supervisorId, array $data): int
    {
        $statement = $this->db->prepare(
            'UPDATE company_supervisors SET company_id = :company_id,
                firstname = :firstname, middlename = :middlename,
                lastname = :lastname, name_ext = :name_ext,
                gender = :gender, address = :address
             WHERE supervisor_id = :supervisor_id'
        );
        $this->bindSupervisor($statement, $data);
        $statement->bindValue(':supervisor_id', $supervisorId, PDO::PARAM_INT);
        $statement->execute();
        return $statement->rowCount();
    }

    public function deleteSupervisor(int $supervisorId): int
    {
        $statement = $this->db->prepare('DELETE FROM company_supervisors WHERE supervisor_id = :supervisor_id');
        $statement->bindValue(':supervisor_id', $supervisorId, PDO::PARAM_INT);
        $statement->execute();
        return $statement->rowCount();
    }

    private function bindSupervisor($statement, array $data): void
    {
        $statement->bindValue(':company_id', (int) $data['company_id'], PDO::PARAM_INT);
        $statement->bindValue(':firstname', $data['firstname'], PDO::PARAM_STR);
        $statement->bindValue(':middlename', $data['middlename'] ?: null, $data['middlename'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':lastname', $data['lastname'], PDO::PARAM_STR);
        $statement->bindValue(':name_ext', $data['name_ext'] ?: null, $data['name_ext'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':gender', $data['gender'], PDO::PARAM_STR);
        $statement->bindValue(':address', $data['address'], PDO::PARAM_STR);
    }
}
