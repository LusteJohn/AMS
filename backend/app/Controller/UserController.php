<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\User;
use PDOException;

class UserController extends Controller
{
	private User $users;

	public function __construct()
	{
		parent::__construct();
		$this->users = new User();
	}

	public function index(): void
	{
		$this->json(['success' => true, 'data' => $this->users->all()]);
	}

	public function show(string $id): void
	{
		$userId = $this->validatedId($id);
		$user = $this->users->find($userId);
		if (!$user) {
			$this->response->notFound('User not found');
		}
		$this->json(['success' => true, 'data' => $user]);
	}

	public function store(bool $allowRole = true): void
	{
		$this->requireCsrf();
		$input = $this->validatedUserInput(true, $allowRole);

		try {
			$id = $this->users->createUser($input['username'], $input['email'], $input['password'], $input['role']);
		} catch (PDOException $exception) {
			if ((int) $exception->errorInfo[1] === 1062) {
				$this->response->error('Email is already registered', null, 409);
			}
			$this->response->serverError('Unable to create user');
		}

		$this->response->created($this->users->find($id), 'User created successfully');
	}

	public function update(string $id): void
	{
		$this->requireCsrf();
		$userId = $this->validatedId($id);
		if (!$this->users->find($userId)) {
			$this->response->notFound('User not found');
		}

		$input = $this->validatedUserInput(false);
		try {
			$this->users->updateUser($userId, $input['username'], $input['email'], $input['password'] ?: null, $input['role']);
		} catch (PDOException $exception) {
			if ((int) $exception->errorInfo[1] === 1062) {
				$this->response->error('Email is already registered', null, 409);
			}
			$this->response->serverError('Unable to update user');
		}

		$this->response->updated($this->users->find($userId), 'User updated successfully');
	}

	public function destroy(string $id): void
	{
		$this->requireCsrf();
		$userId = $this->validatedId($id);
		if (!$this->users->find($userId)) {
			$this->response->notFound('User not found');
		}
		$this->users->deleteUser($userId);
		$this->response->deleted('User deleted successfully');
	}

	public function register(): void
	{
		$this->store(false);
	}

	public function login(): void
	{
		$this->requireCsrf();
		$login = $this->sanitizeText($this->input('login', $this->input('username', $this->input('email', ''))));
		$password = $this->stringInput('password');
		if ($login === '' || strlen($password) < 1) {
			$this->response->badRequest('Username or email and password are required');
		}

		$user = $this->users->verifyCredentials($login, $password);
		if (!$user) {
			$this->response->unauthorized('Invalid email or password');
		}

		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		session_regenerate_id(true);
		$_SESSION['user'] = $user;
		$this->json(['success' => true, 'data' => $user, 'message' => 'Login successful']);
	}

	public function logout(): void
	{
		$this->requireCsrf();
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		$_SESSION = [];
		if (ini_get('session.use_cookies')) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
		}
		session_destroy();
		$this->json(['success' => true, 'message' => 'Logout successful']);
	}

	public function session(): void
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		$user = $_SESSION['user'] ?? null;
		if (!$user) {
			$this->response->unauthorized('Not authenticated');
		}

		$this->json(['success' => true, 'data' => $user]);
	}

	private function validatedUserInput(bool $passwordRequired, bool $allowRole = true): array
	{
		$username = $this->sanitizeText($this->input('username', ''));
		$email = $this->email($this->input('email'));
		$password = $this->stringInput('password');
		$role = $allowRole ? strtolower($this->stringInput('role') ?: 'student') : 'student';
		$errors = [];

		if (!preg_match('/^[\p{L}\p{N} ._\'-]{2,100}$/u', $username)) {
			$errors['username'] = 'Username must be 2 to 100 characters.';
		}
		if (!$email) {
			$errors['email'] = 'A valid email is required.';
		}
		if (($passwordRequired && strlen($password) < 8) || (!$passwordRequired && $password !== '' && strlen($password) < 8)) {
			$errors['password'] = 'Password must be at least 8 characters.';
		}
		if (!in_array($role, ['admin', 'faculty', 'student'], true)) {
			$errors['role'] = 'Role must be admin, faculty, or student.';
		}
		if ($passwordRequired && $password === '') {
			$errors['password'] = 'Password is required.';
		}
		if ($errors) {
			$this->response->error('Validation failed', $errors, 422);
		}

		return compact('username', 'email', 'password', 'role');
	}

	private function validatedId(string $id): int
	{
		if (!ctype_digit($id) || (int) $id < 1) {
			$this->response->badRequest('Invalid user ID');
		}
		return (int) $id;
	}

	private function stringInput(string $key): string
	{
		return trim((string) $this->input($key, ''));
	}

	private function sanitizeText(mixed $value): string
	{
		$value = strip_tags((string) $value);
		$value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
		return trim($value);
	}

	private function email(mixed $value): ?string
	{
		$email = trim((string) $value);
		return filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower($email) : null;
	}

	private function requireCsrf(): void
	{
		if (!Csrf::validate((string) (Csrf::fromRequest() ?? ''))) {
			$this->response->error('Invalid CSRF token', null, 419);
		}
	}
}
