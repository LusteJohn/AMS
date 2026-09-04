<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\AttendanceLog;
use App\Model\Student;
use PDOException;

class AttendanceLogController extends Controller
{
	private AttendanceLog $logs;
	private Student $students;

	public function __construct()
	{
		parent::__construct();
		$this->logs = new AttendanceLog();
		$this->students = new Student();
	}

	public function index(): void
	{
		$attendanceId = $this->query('attendance_id');
		$attendanceId = $attendanceId === null || $attendanceId === '' ? null : $this->positiveId((string) $attendanceId, 'attendance');
		$studentId = $this->studentScope();
		if ($studentId !== null && $attendanceId !== null) $this->authorizeAttendance($attendanceId, $studentId);
		$this->json(['success' => true, 'data' => $this->logs->all($studentId, $attendanceId)]);
	}

	public function show(string $id): void
	{
		$log = $this->logs->find($this->positiveId($id, 'attendance log'));
		$this->authorizeLog($log);
		$this->json(['success' => true, 'data' => $log]);
	}

	public function store(): void
	{
		$this->requireCsrf();
		$studentId = $this->studentScope();
		$data = $this->validatedLog();
		$this->authorizeAttendance($data['attendance_id'], $studentId);
		try {
			$logId = $this->logs->createLog($data);
		} catch (PDOException $exception) {
			if ((int) ($exception->errorInfo[1] ?? 0) === 1062) $this->response->error('This attendance type already exists', null, 409);
			$this->response->serverError('Unable to create attendance log');
		}
		$this->response->created($this->logs->find($logId), 'Attendance log created successfully');
	}

	public function update(string $id): void
	{
		$this->requireCsrf();
		$logId = $this->positiveId($id, 'attendance log');
		$current = $this->logs->find($logId);
		$this->authorizeLog($current);
		$data = $this->validatedLog();
		$this->authorizeAttendance($data['attendance_id'], $this->studentScope());
		try {
			$this->logs->updateLog($logId, $data);
		} catch (PDOException $exception) {
			if ((int) ($exception->errorInfo[1] ?? 0) === 1062) $this->response->error('This attendance type already exists', null, 409);
			$this->response->serverError('Unable to update attendance log');
		}
		$this->response->updated($this->logs->find($logId), 'Attendance log updated successfully');
	}

	public function destroy(string $id): void
	{
		$this->requireCsrf();
		$logId = $this->positiveId($id, 'attendance log');
		$log = $this->logs->find($logId);
		$this->authorizeLog($log);
		if ($this->logs->deleteLog($logId) === 0) $this->response->notFound('Attendance log not found');
		$this->response->deleted('Attendance log deleted successfully');
	}

	private function studentScope(): ?int
	{
		if (session_status() === PHP_SESSION_NONE) session_start();
		$user = $_SESSION['user'] ?? [];
		if (strtolower(trim((string) ($user['role'] ?? ''))) !== 'student') return null;
		$profile = $this->students->findByUserId((int) ($user['user_id'] ?? 0));
		if (!$profile) $this->response->notFound('Student profile not found');
		return (int) $profile['student_id'];
	}

	private function authorizeLog(?array $log): void
	{
		if (!$log) $this->response->notFound('Attendance log not found');
		$studentId = $this->studentScope();
		if ($studentId !== null && (int) $log['student_id'] !== $studentId) $this->response->forbidden('You may only manage your own attendance logs');
	}

	private function authorizeAttendance(int $attendanceId, ?int $studentId): void
	{
		$attendance = $this->logs->findAttendance($attendanceId);
		if (!$attendance) $this->response->error('Attendance record not found', null, 422);
		if ($studentId !== null && (int) $attendance['student_id'] !== $studentId) $this->response->forbidden('You may only use your own attendance record');
	}

	private function validatedLog(): array
	{
		$data = [
			'attendance_id' => filter_var($this->input('attendance_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]),
			'attendance_type' => strtolower($this->textInput('attendance_type')),
			'attendance_time' => $this->timeInput('attendance_time'),
			'status' => strtolower($this->textInput('status') ?: 'on_time'),
		];
		$errors = [];
		if (!$data['attendance_id']) $errors['attendance_id'] = 'A valid attendance record is required.';
		if (!in_array($data['attendance_type'], ['morning_in', 'morning_out', 'afternoon_in', 'afternoon_out'], true)) $errors['attendance_type'] = 'Invalid attendance type.';
		if (!$data['attendance_time']) $errors['attendance_time'] = 'A valid attendance time is required.';
		if (!in_array($data['status'], ['on_time', 'late'], true)) $errors['status'] = 'Status must be on_time or late.';
		if ($errors) $this->response->error('Validation failed', $errors, 422);
		return $data;
	}

	private function timeInput(string $key): ?string
	{
		$value = trim((string) $this->input($key, ''));
		foreach (['H:i', 'H:i:s'] as $format) {
			$time = \DateTime::createFromFormat($format, $value);
			if ($time && $time->format($format) === $value) return $time->format('H:i:s');
		}
		return null;
	}

	private function textInput(string $key): string
	{
		return trim(preg_replace('/[\x00-\x1F\x7F]/u', '', strip_tags((string) $this->input($key, ''))) ?? '');
	}

	private function positiveId(string $id, string $label): int
	{
		if (!ctype_digit($id) || (int) $id < 1) $this->response->badRequest("Invalid {$label} ID");
		return (int) $id;
	}

	private function requireCsrf(): void
	{
		if (!Csrf::validate((string) (Csrf::fromRequest() ?? ''))) $this->response->error('Invalid CSRF token', null, 419);
	}
}
