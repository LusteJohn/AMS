<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\OjtStudentCompany;
use App\Model\Student;
use PDOException;

class OjtStudentCompanyController extends Controller
{
    private OjtStudentCompany $assignments;
    private Student $students;

    public function __construct()
    {
        parent::__construct();
        $this->assignments = new OjtStudentCompany();
        $this->students = new Student();
    }

    public function index(): void
    {
        $studentId = $this->studentScope();
        $this->json(['success' => true, 'data' => $this->assignments->all($studentId)]);
    }

    public function show(string $id): void
    {
        $assignmentId = $this->validatedId($id);
        $assignment = $this->assignments->find($assignmentId);
        $this->authorizeAssignment($assignment);
        $this->json(['success' => true, 'data' => $assignment]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $studentId = $this->studentScope();
        $data = $this->validatedAssignment($studentId);
        try {
            $assignmentId = $this->assignments->createAssignment($data);
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                $this->response->error('This student is already assigned to this company', null, 409);
            }
            $this->response->serverError('Unable to create OJT assignment');
        }
        $this->response->created($this->assignments->find($assignmentId), 'OJT assignment created successfully');
    }

    public function update(string $id): void
    {
        $this->requireCsrf();
        $assignmentId = $this->validatedId($id);
        $current = $this->assignments->find($assignmentId);
        $this->authorizeAssignment($current);
        $studentId = $this->studentScope();
        try {
            $this->assignments->updateAssignment($assignmentId, $this->validatedAssignment($studentId));
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                $this->response->error('This student is already assigned to this company', null, 409);
            }
            $this->response->serverError('Unable to update OJT assignment');
        }
        $this->response->updated($this->assignments->find($assignmentId), 'OJT assignment updated successfully');
    }

    public function destroy(string $id): void
    {
        $this->requireCsrf();
        $assignmentId = $this->validatedId($id);
        $this->authorizeAssignment($this->assignments->find($assignmentId));
        if ($this->assignments->deleteAssignment($assignmentId) === 0) {
            $this->response->notFound('OJT assignment not found');
        }
        $this->response->deleted('OJT assignment deleted successfully');
    }

    private function studentScope(): ?int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user = $_SESSION['user'] ?? [];
        $role = strtolower(trim((string) ($user['role'] ?? '')));
        if ($role !== 'student') {
            return null;
        }
        $profile = $this->students->findByUserId((int) ($user['user_id'] ?? 0));
        if (!$profile) {
            $this->response->notFound('Student profile not found');
        }
        return (int) $profile['student_id'];
    }

    private function authorizeAssignment(?array $assignment): void
    {
        if (!$assignment) {
            $this->response->notFound('OJT assignment not found');
        }
        $studentId = $this->studentScope();
        if ($studentId !== null && (int) $assignment['student_id'] !== $studentId) {
            $this->response->forbidden('You may only manage your own OJT assignments');
        }
    }

    private function validatedAssignment(?int $studentId = null): array
    {
        $requestStudentId = filter_var($this->input('student_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $data = [
            'company_id' => filter_var($this->input('company_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]),
            'student_id' => $studentId ?? $requestStudentId,
            'ojt_start_date' => $this->dateInput('ojt_start_date'),
            'ojt_end_date' => $this->dateInput('ojt_end_date'),
            'required_hours' => $this->input('required_hours', 0),
            'status' => strtolower($this->textInput('status') ?: 'pending'),
        ];
        $errors = [];
        if (!$data['company_id'] || !$data['student_id']) $errors['company_id'] = 'Valid company and student are required.';
        if (!$data['ojt_start_date'] || !$data['ojt_end_date']) $errors['dates'] = 'Valid start and end dates are required.';
        if ($data['ojt_start_date'] && $data['ojt_end_date'] && $data['ojt_end_date'] < $data['ojt_start_date']) $errors['dates'] = 'End date must not be before start date.';
        if (!is_numeric($data['required_hours']) || (float) $data['required_hours'] < 0 || (float) $data['required_hours'] > 99999.99) $errors['required_hours'] = 'Required hours must be between 0 and 99999.99.';
        if (!in_array($data['status'], ['pending', 'approved', 'rejected', 'active', 'completed'], true)) $errors['status'] = 'Invalid assignment status.';
        if ($errors) $this->response->error('Validation failed', $errors, 422);
        $data['required_hours'] = number_format((float) $data['required_hours'], 2, '.', '');
        return $data;
    }

    private function textInput(string $key): string
    {
        return trim(preg_replace('/[\x00-\x1F\x7F]/u', '', strip_tags((string) $this->input($key, ''))) ?? '');
    }

    private function dateInput(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function validatedId(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) $this->response->badRequest('Invalid assignment ID');
        return (int) $id;
    }

    private function requireCsrf(): void
    {
        if (!Csrf::validate((string) (Csrf::fromRequest() ?? ''))) $this->response->error('Invalid CSRF token', null, 419);
    }
}
