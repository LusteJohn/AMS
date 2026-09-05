<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\Faculty;
use App\Model\User;
use PDOException;

class FacultyController extends Controller
{
    private Faculty $faculty;
    private User $users;

    public function __construct()
    {
        parent::__construct();
        $this->faculty = new Faculty();
        $this->users = new User();
    }

    public function index(): void
    {
        $this->json(['success' => true, 'data' => $this->faculty->all()]);
    }

    public function show(string $id): void
    {
        $facultyId = $this->validatedId($id);
        $faculty = $this->faculty->find($facultyId);
        if (!$faculty) {
            $this->response->notFound('Faculty not found');
        }
        $this->json(['success' => true, 'data' => $faculty]);
    }

    public function profile(): void
    {
        $userId = $this->authenticatedUserId();
        $profile = $this->faculty->findByUserId($userId);
        $this->json(['success' => true, 'data' => $profile]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $account = $this->validatedAccount();
        $profile = $this->validatedProfile();
        $connection = $this->faculty->getConnection();

        try {
            $connection->beginTransaction();
            $userId = $this->users->createUser($account['username'], $account['email'], $account['password'], 'faculty');
            $facultyId = $this->faculty->createProfile($userId, $profile);
            $connection->commit();
        } catch (PDOException $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                $this->response->error('Username or email is already registered', null, 409);
            }
            $this->response->serverError('Unable to create faculty account');
        }

        $this->response->created($this->faculty->find($facultyId), 'Faculty account created successfully');
    }

    public function update(string $id): void
    {
        $this->requireCsrf();
        $facultyId = $this->validatedId($id);
        $current = $this->faculty->find($facultyId);
        if (!$current) {
            $this->response->notFound('Faculty not found');
        }

        $account = $this->validatedAccount(false);
        $profile = $this->validatedProfile();
        $connection = $this->faculty->getConnection();
        try {
            $connection->beginTransaction();
            $this->users->updateUser((int) $current['user_id'], $account['username'], $account['email'], $account['password'] ?: null, 'faculty');
            $this->faculty->updateProfile($facultyId, $profile);
            $connection->commit();
        } catch (PDOException $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                $this->response->error('Username or email is already registered', null, 409);
            }
            $this->response->serverError('Unable to update faculty');
        }

        $this->response->updated($this->faculty->find($facultyId), 'Faculty updated successfully');
    }

    public function destroy(string $id): void
    {
        $this->requireCsrf();
        $facultyId = $this->validatedId($id);
        $current = $this->faculty->find($facultyId);
        if (!$current) {
            $this->response->notFound('Faculty not found');
        }

        // users.user_id is the parent; ON DELETE CASCADE removes faculty automatically.
        $this->faculty->deleteByUserId((int) $current['user_id']);
        $this->response->deleted('Faculty account and profile deleted successfully');
    }

    private function validatedAccount(bool $passwordRequired = true): array
    {
        $username = $this->textInput('username');
        $email = filter_var(trim((string) $this->input('email', '')), FILTER_VALIDATE_EMAIL);
        $password = (string) $this->input('password', '');
        $errors = [];

        if (!preg_match('/^[\p{L}\p{N} ._\'-]{2,100}$/u', $username)) {
            $errors['username'] = 'Username must be 2 to 100 characters.';
        }
        if ($email === false) {
            $errors['email'] = 'A valid email is required.';
        }
        if (($passwordRequired && $password === '') || (!$passwordRequired && $password !== '' && strlen($password) < 1)) {
            $errors['password'] = 'Password is required.';
        }
        if ($errors) {
            $this->response->error('Validation failed', $errors, 422);
        }

        return ['username' => $username, 'email' => strtolower((string) $email), 'password' => $password];
    }

    private function validatedProfile(): array
    {
        $data = [
            'section_id' => filter_var($this->input('section_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]),
            'firstname' => $this->textInput('firstname'),
            'middlename' => $this->textInput('middlename'),
            'lastname' => $this->textInput('lastname'),
            'name_ext' => $this->textInput('name_ext'),
            'gender' => $this->textInput('gender'),
            'address' => $this->textInput('address'),
        ];
        $errors = [];
        foreach (['firstname' => 50, 'middlename' => 50, 'lastname' => 50, 'name_ext' => 5, 'gender' => 20, 'address' => 255] as $field => $maxLength) {
            if (!in_array($field, ['middlename', 'name_ext'], true) && $data[$field] === '') {
                $errors[$field] = ucfirst($field) . ' is required.';
            } elseif (strlen($data[$field]) > $maxLength) {
                $errors[$field] = ucfirst($field) . " must not exceed {$maxLength} characters.";
            }
        }
        if (!$data['section_id']) {
            $errors['section_id'] = 'A valid section is required.';
        }
        if ($errors) {
            $this->response->error('Validation failed', $errors, 422);
        }
        return $data;
    }

    private function textInput(string $key): string
    {
        $value = strip_tags((string) $this->input($key, ''));
        return trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '');
    }

    private function validatedId(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->response->badRequest('Invalid faculty ID');
        }
        return (int) $id;
    }

    private function authenticatedUserId(): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = $_SESSION['user']['user_id'] ?? null;
        if (!is_numeric($userId) || (int) $userId < 1) {
            $this->response->unauthorized('Authenticated user not found');
        }
        return (int) $userId;
    }

    private function requireCsrf(): void
    {
        if (!Csrf::validate((string) (Csrf::fromRequest() ?? ''))) {
            $this->response->error('Invalid CSRF token', null, 419);
        }
    }
}
