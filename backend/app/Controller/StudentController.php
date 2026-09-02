<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\Student;
use PDOException;

class StudentController extends Controller
{
    private Student $students;

    public function __construct()
    {
        parent::__construct();
        $this->students = new Student();
    }

    public function profile(): void
    {
        $userId = $this->authenticatedUserId();
        $profile = $this->students->findByUserId($userId);
        $this->json(['success' => true, 'data' => $profile]);
    }

    public function storeProfile(): void
    {
        $this->requireCsrf();
        $userId = $this->authenticatedUserId();
        if ($this->students->findByUserId($userId)) {
            $this->response->error('Student profile already exists', null, 409);
        }

        try {
            $studentId = $this->students->createProfile($userId, $this->validatedProfile());
        } catch (PDOException $exception) {
            $this->response->serverError('Unable to create student profile');
        }

        $this->response->created($this->students->findByUserId($userId), 'Student profile created successfully');
    }

    public function updateProfile(): void
    {
        $this->requireCsrf();
        $userId = $this->authenticatedUserId();
        $profile = $this->students->findByUserId($userId);
        if (!$profile) {
            $this->response->notFound('Student profile not found');
        }

        try {
            $this->students->updateProfile((int) $profile['student_id'], $this->validatedProfile());
        } catch (PDOException $exception) {
            $this->response->serverError('Unable to update student profile');
        }

        $this->response->updated($this->students->findByUserId($userId), 'Student profile updated successfully');
    }

    public function destroyProfile(): void
    {
        $this->requireCsrf();
        $userId = $this->authenticatedUserId();
        $profile = $this->students->findByUserId($userId);
        if (!$profile) {
            $this->response->notFound('Student profile not found');
        }

        $this->students->deleteProfile((int) $profile['student_id']);
        $this->response->deleted('Student profile deleted successfully');
    }

    public function index(): void
    {
        $this->json(['success' => true, 'data' => $this->students->all()]);
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

    private function validatedProfile(): array
    {
        $data = [
            'section_id' => filter_var($this->input('section_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]),
            'school_id' => $this->nullablePositiveInt($this->input('school_id')),
            'firstname' => $this->textInput('firstname'),
            'middlename' => $this->textInput('middlename'),
            'lastname' => $this->textInput('lastname'),
            'name_ext' => $this->textInput('name_ext'),
            'gender' => $this->textInput('gender'),
            'address' => $this->textInput('address'),
        ];
        $errors = [];

        foreach (['firstname' => 50, 'middlename' => 50, 'lastname' => 50, 'name_ext' => 5, 'gender' => 20, 'address' => 255] as $field => $maxLength) {
            if ($field !== 'middlename' && $data[$field] === '') {
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

    private function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $number === false ? null : (int) $number;
    }

    private function requireCsrf(): void
    {
        if (!Csrf::validate((string) (Csrf::fromRequest() ?? ''))) {
            $this->response->error('Invalid CSRF token', null, 419);
        }
    }
}
