<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class User extends Model
{
	protected string $table = 'users';

	public function all(): array
	{
		return $this->fetchAll(
			'SELECT user_id, username, email, role, created_at, updated_at FROM users ORDER BY user_id DESC'
		);
	}

	public function find(int $id): ?array
	{
		return $this->fetch(
			'SELECT user_id, username, email, role, created_at, updated_at FROM users WHERE user_id = :user_id',
			[':user_id' => $id]
		);
	}

	public function findByEmail(string $email, bool $includePassword = false): ?array
	{
		$columns = $includePassword
			? 'user_id, username, email, password, role, created_at, updated_at'
			: 'user_id, username, email, role, created_at, updated_at';

		return $this->fetch(
			"SELECT {$columns} FROM users WHERE email = :email LIMIT 1",
			[':email' => $email]
		);
	}

	public function findByLogin(string $login, bool $includePassword = false): ?array
	{
		$columns = $includePassword
			? 'user_id, username, email, password, role, created_at, updated_at'
			: 'user_id, username, email, role, created_at, updated_at';

		$statement = $this->db->prepare(
			"SELECT {$columns} FROM users WHERE username = :username OR email = :email LIMIT 1"
		);
		$statement->bindValue(':username', $login, PDO::PARAM_STR);
		$statement->bindValue(':email', strtolower($login), PDO::PARAM_STR);
		$statement->execute();

		return $statement->fetch() ?: null;
	}

	public function createUser(string $username, string $email, string $password, string $role = 'student'): int
	{
		$statement = $this->db->prepare(
			'INSERT INTO users (username, email, password, role)
			 VALUES (:username, :email, :password, :role)'
		);
		$statement->bindValue(':username', $username, PDO::PARAM_STR);
		$statement->bindValue(':email', $email, PDO::PARAM_STR);
		$statement->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
		$statement->bindValue(':role', $role, PDO::PARAM_STR);
		$statement->execute();

		return (int) $this->db->lastInsertId();
	}

	public function updateUser(int $id, string $username, string $email, ?string $password, string $role): bool
	{
		$fields = 'username = :username, email = :email, role = :role';
		if ($password !== null) {
			$fields .= ', password = :password';
		}

		$statement = $this->db->prepare("UPDATE users SET {$fields} WHERE user_id = :user_id");
		$statement->bindValue(':user_id', $id, PDO::PARAM_INT);
		$statement->bindValue(':username', $username, PDO::PARAM_STR);
		$statement->bindValue(':email', $email, PDO::PARAM_STR);
		$statement->bindValue(':role', $role, PDO::PARAM_STR);
		if ($password !== null) {
			$statement->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
		}

		return $statement->execute();
	}

	public function deleteUser(int $id): bool
	{
		$statement = $this->db->prepare('DELETE FROM users WHERE user_id = :user_id');
		$statement->bindValue(':user_id', $id, PDO::PARAM_INT);
		return $statement->execute();
	}

	public function verifyCredentials(string $login, string $password): ?array
	{
		$user = $this->findByLogin($login, true);
		if (!$user || !password_verify($password, $user['password'])) {
			return null;
		}

		return [
			'user_id' => (int) $user['user_id'],
			'username' => (string) $user['username'],
			'email' => (string) $user['email'],
			'role' => strtolower(trim((string) $user['role'])),
			'created_at' => $user['created_at'],
			'updated_at' => $user['updated_at'],
		];
	}
}
