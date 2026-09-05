<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\Attendance;
use App\Model\Student;
use PDOException;

class AttendanceController extends Controller
{
	private Attendance $attendance;
	private Student $students;

	public function __construct()
	{
		parent::__construct();
		$this->attendance = new Attendance();
		$this->students = new Student();
	}

	public function index(): void
	{
		$sectionId = filter_var($this->query('section_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		$sectionId = $sectionId === false ? null : (int) $sectionId;
		$this->json(['success' => true, 'data' => $this->attendance->all($this->studentScope(), $sectionId)]);
	}

	public function show(string $id): void
	{
		$attendanceId = $this->positiveId($id);
		$record = $this->attendance->find($attendanceId);
		$this->authorizeRecord($record);
		$this->json(['success' => true, 'data' => $record]);
	}

	public function store(): void
	{
		$this->requireCsrf();
		$studentId = $this->studentScope();
		$data = $this->validatedAttendance();
		$this->authorizeAssignment($data['student_company_id'], $studentId);
		if ($this->attendance->existsForAssignmentDate($data['student_company_id'], $data['attendance_date'])) {
			$this->response->error('You can only add one attendance record per day', null, 409);
		}
		try {
			$attendanceId = $this->attendance->createAttendance($data);
		} catch (PDOException $exception) {
			if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
				$this->response->error('Attendance for this date already exists', null, 409);
			}
			$this->response->serverError('Unable to create attendance record');
		}
		$this->response->created($this->attendance->find($attendanceId), 'Attendance record created successfully');
	}

	public function update(string $id): void
	{
		$this->requireCsrf();
		$attendanceId = $this->positiveId($id);
		$current = $this->attendance->find($attendanceId);
		$this->authorizeRecord($current);
		$data = $this->validatedAttendance();
		$this->authorizeAssignment($data['student_company_id'], $this->studentScope());
		if ($this->attendance->existsForAssignmentDate($data['student_company_id'], $data['attendance_date'], $attendanceId)) {
			$this->response->error('You can only add one attendance record per day', null, 409);
		}
		try {
			$this->attendance->updateAttendance($attendanceId, $data);
		} catch (PDOException $exception) {
			if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
				$this->response->error('Attendance for this date already exists', null, 409);
			}
			$this->response->serverError('Unable to update attendance record');
		}
		$this->response->updated($this->attendance->find($attendanceId), 'Attendance record updated successfully');
	}

	public function destroy(string $id): void
	{
		$this->requireCsrf();
		$attendanceId = $this->positiveId($id);
		$this->authorizeRecord($this->attendance->find($attendanceId));
		if ($this->attendance->deleteAttendance($attendanceId) === 0) {
			$this->response->notFound('Attendance record not found');
		}
		$this->response->deleted('Attendance record deleted successfully');
	}

	private function studentScope(): ?int
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		$user = $_SESSION['user'] ?? [];
		if (strtolower(trim((string) ($user['role'] ?? ''))) !== 'student') {
			return null;
		}
		$profile = $this->students->findByUserId((int) ($user['user_id'] ?? 0));
		if (!$profile) {
			$this->response->notFound('Student profile not found');
		}
		return (int) $profile['student_id'];
	}

	private function authorizeRecord(?array $record): void
	{
		if (!$record) {
			$this->response->notFound('Attendance record not found');
		}
		$studentId = $this->studentScope();
		if ($studentId !== null && (int) $record['student_id'] !== $studentId) {
			$this->response->forbidden('You may only manage your own attendance records');
		}
	}

	private function authorizeAssignment(int $assignmentId, ?int $studentId): void
	{
		$record = $this->attendance->findAssignment($assignmentId);
		if (!$record) {
			$this->response->error('OJT assignment not found', null, 422);
		}
		if ($studentId !== null && (int) $record['student_id'] !== $studentId) {
			$this->response->forbidden('You may only use your own OJT assignment');
		}
	}

	private function validatedAttendance(): array
	{
		$data = [
			'student_company_id' => filter_var($this->input('student_company_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]),
			'attendance_date' => $this->dateInput('attendance_date'),
			'total_hours' => $this->input('total_hours', 0),
			'status' => strtolower($this->textInput('status') ?: 'pending'),
		];
		$errors = [];
		if (!$data['student_company_id']) $errors['student_company_id'] = 'A valid OJT assignment is required.';
		if (!$data['attendance_date']) $errors['attendance_date'] = 'A valid attendance date is required.';
		if (!is_numeric($data['total_hours']) || (float) $data['total_hours'] < 0 || (float) $data['total_hours'] > 999.99) $errors['total_hours'] = 'Total hours must be between 0 and 999.99.';
		if (!in_array($data['status'], ['pending', 'present', 'absent', 'late', 'leave'], true)) $errors['status'] = 'Invalid attendance status.';
		if ($errors) $this->response->error('Validation failed', $errors, 422);
		$data['total_hours'] = number_format((float) $data['total_hours'], 2, '.', '');
		return $data;
	}

	private function dateInput(string $key): ?string
	{
		$value = trim((string) $this->input($key, ''));
		$date = \DateTime::createFromFormat('Y-m-d', $value);
		return $date && $date->format('Y-m-d') === $value ? $value : null;
	}

	private function textInput(string $key): string
	{
		return trim(preg_replace('/[\x00-\x1F\x7F]/u', '', strip_tags((string) $this->input($key, ''))) ?? '');
	}

	private function positiveId(string $id): int
	{
		if (!ctype_digit($id) || (int) $id < 1) $this->response->badRequest('Invalid attendance ID');
		return (int) $id;
	}

	private function requireCsrf(): void
	{
		if (!Csrf::validate((string) (Csrf::fromRequest() ?? ''))) $this->response->error('Invalid CSRF token', null, 419);
	}
}
