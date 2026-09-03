<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class OjtCompany extends Model
{
    protected string $table = 'ojt_company';

    public function all(): array
    {
        return $this->fetchAll(
            'SELECT company_id, company_name, description, contact_number,
                    email_address, address, created_at, updated_at
             FROM ojt_company ORDER BY company_name'
        );
    }

    public function find(int $companyId): ?array
    {
        return $this->fetch(
            'SELECT company_id, company_name, description, contact_number,
                    email_address, address, created_at, updated_at
             FROM ojt_company WHERE company_id = :company_id LIMIT 1',
            [':company_id' => $companyId]
        );
    }

    public function createCompany(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO ojt_company
                (company_name, description, contact_number, email_address, address)
             VALUES
                (:company_name, :description, :contact_number, :email_address, :address)'
        );
        $this->bindCompany($statement, $data);
        $statement->execute();
        return (int) $this->db->lastInsertId();
    }

    public function updateCompany(int $companyId, array $data): int
    {
        $statement = $this->db->prepare(
            'UPDATE ojt_company SET company_name = :company_name,
                description = :description, contact_number = :contact_number,
                email_address = :email_address, address = :address
             WHERE company_id = :company_id'
        );
        $this->bindCompany($statement, $data);
        $statement->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $statement->execute();
        return $statement->rowCount();
    }

    public function deleteCompany(int $companyId): int
    {
        $statement = $this->db->prepare('DELETE FROM ojt_company WHERE company_id = :company_id');
        $statement->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $statement->execute();
        return $statement->rowCount();
    }

    private function bindCompany($statement, array $data): void
    {
        $statement->bindValue(':company_name', $data['company_name'], PDO::PARAM_STR);
        $statement->bindValue(':description', $data['description'] ?: null, $data['description'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':contact_number', $data['contact_number'] ?: null, $data['contact_number'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':email_address', $data['email_address'] ?: null, $data['email_address'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':address', $data['address'], PDO::PARAM_STR);
    }
}
