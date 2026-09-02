<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\Student;
use App\Model\User;
use PDOException;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class StudentController extends Controller
{
    private Student $students;
    private User $users;

    public function __construct()
    {
        parent::__construct();
        $this->students = new Student();
        $this->users = new User();
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

    public function adminStore(): void
    {
        $this->requireCsrf();
        $account = $this->validatedAccount();
        $profile = $this->validatedProfile();
        $connection = $this->students->getConnection();

        try {
            $connection->beginTransaction();
            $userId = $this->users->createUser($account['username'], $account['email'], $account['password'], 'student');
            $this->students->createProfile($userId, $profile);
            $connection->commit();
        } catch (PDOException $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                $this->response->error('Username or email is already registered', null, 409);
            }
            $this->response->serverError('Unable to register student account');
        }

        $this->response->created($this->students->findByUserId($userId), 'Student account registered successfully');
    }

    public function importCsv(): void
    {
        $this->requireCsrf();
        $sectionId = filter_var($_POST['section_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $file = $_FILES['csv_file'] ?? null;

        if (!$sectionId || !$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->response->badRequest('A valid CSV file and section are required');
        }
        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
            $this->response->badRequest('Only CSV files are allowed');
        }

        try {
            $reader = new Csv();
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($file['tmp_name'])->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        } catch (Throwable $exception) {
            $this->response->badRequest('Unable to read the CSV file');
        }

        $headers = array_shift($rows);
        $requiredHeaders = ['school_id', 'firstname', 'middlename', 'lastname', 'gender'];
        $normalizedHeaders = array_map(static function ($header): string {
            return strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', $header)));
        }, $headers ?: []);
        if (array_diff($requiredHeaders, $normalizedHeaders)) {
            $this->response->error('CSV must contain school_id, firstname, middlename, lastname, and gender columns', null, 422);
        }

        $columnMap = array_flip($normalizedHeaders);
        $records = [];
        $rowNumber = 1;
        foreach ($rows as $row) {
            $rowNumber++;
            if (count(array_filter($row, static fn ($value): bool => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $record = [];
            foreach ($requiredHeaders as $header) {
                $record[$header] = $this->csvText($row[$columnMap[$header]] ?? '');
            }
            $records[] = ['row' => $rowNumber, 'data' => $record];
        }

        if (!$records) {
            $this->response->error('The CSV file contains no student records', null, 422);
        }

        $errors = [];
        foreach ($records as $record) {
            $data = $record['data'];
            if ($data['school_id'] === '' || strlen($data['school_id']) > 10) {
                $errors[] = "Row {$record['row']}: school_id is required and must not exceed 10 characters.";
            }
            foreach (['firstname' => 50, 'middlename' => 50, 'lastname' => 50, 'gender' => 20] as $field => $maxLength) {
                if ($field !== 'middlename' && $data[$field] === '') {
                    $errors[] = "Row {$record['row']}: {$field} is required.";
                } elseif (strlen($data[$field]) > $maxLength) {
                    $errors[] = "Row {$record['row']}: {$field} exceeds {$maxLength} characters.";
                }
            }
        }
        if ($errors) {
            $this->response->error('CSV validation failed', $errors, 422);
        }

        $connection = $this->students->getConnection();
        try {
            $connection->beginTransaction();
            foreach ($records as $record) {
                $data = $record['data'];
                $userId = $this->users->createUser(
                    $data['school_id'],
                    $data['school_id'] . '@student.local',
                    $data['school_id'],
                    'student'
                );
                $this->students->createProfile($userId, [
                    'section_id' => $sectionId,
                    'school_id' => $data['school_id'],
                    'firstname' => $data['firstname'],
                    'middlename' => $data['middlename'],
                    'lastname' => $data['lastname'],
                    'name_ext' => '',
                    'gender' => $data['gender'],
                    'address' => '',
                ]);
            }
            $connection->commit();
        } catch (PDOException $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                $this->response->error('A school_id in the CSV is already registered', null, 409);
            }
            $this->response->serverError('Unable to import student accounts');
        }

        $this->response->created(['imported' => count($records)], 'Student CSV imported successfully');
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
            'school_id' => $this->nullableSchoolId($this->input('school_id')),
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
        if ($data['school_id'] !== null && strlen($data['school_id']) > 10) {
            $errors['school_id'] = 'School ID must not exceed 10 characters.';
        }
        if ($errors) {
            $this->response->error('Validation failed', $errors, 422);
        }
        return $data;
    }

    private function validatedAccount(): array
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
        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }
        if ($errors) {
            $this->response->error('Validation failed', $errors, 422);
        }

        return [
            'username' => $username,
            'email' => strtolower((string) $email),
            'password' => $password,
        ];
    }

    private function textInput(string $key): string
    {
        $value = strip_tags((string) $this->input($key, ''));
        return trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '');
    }

    private function csvText(mixed $value): string
    {
        $value = strip_tags((string) $value);
        return trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '');
    }

    private function nullableSchoolId(mixed $value): ?string
    {
        $schoolId = $this->textInputValue($value);
        return $schoolId === '' ? null : $schoolId;
    }

    private function textInputValue(mixed $value): string
    {
        $value = strip_tags((string) $value);
        return trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '');
    }

    private function requireCsrf(): void
    {
        if (!Csrf::validate((string) (Csrf::fromRequest() ?? ''))) {
            $this->response->error('Invalid CSRF token', null, 419);
        }
    }
}
