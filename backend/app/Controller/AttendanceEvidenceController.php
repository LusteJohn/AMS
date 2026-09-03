<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\AttendanceEvidence;
use App\Model\Student;
use PDOException;

class AttendanceEvidenceController extends Controller
{
	private AttendanceEvidence $evidence;
	private Student $students;

	public function __construct()
	{
		parent::__construct();
		$this->evidence = new AttendanceEvidence();
		$this->students = new Student();
	}

	public function index(): void
	{
		$attendanceLogId = $this->query('attendance_log_id');
		$attendanceLogId = $attendanceLogId === null || $attendanceLogId === '' ? null : $this->positiveId((string) $attendanceLogId, 'attendance log');
		$studentId = $this->studentScope();
		if ($studentId !== null && $attendanceLogId !== null) {
			$this->authorizeLog($attendanceLogId, $studentId);
		}
		$this->json(['success' => true, 'data' => $this->evidence->all($studentId, $attendanceLogId)]);
	}

	public function show(string $id): void
	{
		$evidenceId = $this->positiveId($id, 'evidence');
		$record = $this->evidence->find($evidenceId);
		$this->authorizeEvidence($record);
		$this->json(['success' => true, 'data' => $record]);
	}

	public function store(): void
	{
		$this->requireCsrf();
		$studentId = $this->studentScope();
		$data = $this->validatedEvidence();
		$this->authorizeLog($data['attendance_log_id'], $studentId);
		$imagePath = $this->storeImage();
		$data['image_path'] = $imagePath;
		try {
			$evidenceId = $this->evidence->createEvidence($data);
		} catch (PDOException $exception) {
			$this->removeStoredImage($imagePath);
			if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
				$this->response->error('Evidence for this attendance and type already exists', null, 409);
			}
			$this->response->serverError('Unable to create attendance evidence');
		}
		$this->response->created($this->evidence->find($evidenceId), 'Attendance evidence created successfully');
	}

	public function update(string $id): void
	{
		$this->requireCsrf();
		$evidenceId = $this->positiveId($id, 'evidence');
		$current = $this->evidence->find($evidenceId);
		$this->authorizeEvidence($current);
		$data = $this->validatedEvidence();
		$this->authorizeLog($data['attendance_log_id'], $this->studentScope());
		$imagePath = $this->storeImage();
		$data['image_path'] = $imagePath;
		try {
			$this->evidence->updateEvidence($evidenceId, $data);
		} catch (PDOException $exception) {
			$this->removeStoredImage($imagePath);
			if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
				$this->response->error('Evidence for this attendance and type already exists', null, 409);
			}
			$this->response->serverError('Unable to update attendance evidence');
		}
		$this->removeStoredImage((string) $current['image_path']);
		$this->response->updated($this->evidence->find($evidenceId), 'Attendance evidence updated successfully');
	}

	public function destroy(string $id): void
	{
		$this->requireCsrf();
		$evidenceId = $this->positiveId($id, 'evidence');
		$record = $this->evidence->find($evidenceId);
		$this->authorizeEvidence($record);
		if ($this->evidence->deleteEvidence($evidenceId) === 0) {
			$this->response->notFound('Attendance evidence not found');
		}
		$this->removeStoredImage((string) $record['image_path']);
		$this->response->deleted('Attendance evidence deleted successfully');
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

	private function authorizeEvidence(?array $record): void
	{
		if (!$record) {
			$this->response->notFound('Attendance evidence not found');
		}
		$studentId = $this->studentScope();
		if ($studentId !== null && (int) $record['student_id'] !== $studentId) {
			$this->response->forbidden('You may only manage your own attendance evidence');
		}
	}

	private function authorizeLog(int $attendanceLogId, ?int $studentId): void
	{
		$log = $this->evidence->findLog($attendanceLogId);
		if (!$log) {
			$this->response->error('Attendance log not found', null, 422);
		}
		if ($studentId !== null && (int) $log['student_id'] !== $studentId) {
			$this->response->forbidden('You may only use your own attendance log');
		}
	}

	private function validatedEvidence(): array
	{
		$data = [
			'attendance_log_id' => filter_var($this->input('attendance_log_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]),
			'evidence_type' => 'selfie',
		];
		$image = $_FILES['image'] ?? null;
		$errors = [];
		if (!$data['attendance_log_id']) $errors['attendance_log_id'] = 'A valid attendance log is required.';
		if (!$image || ($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) $errors['image'] = 'A valid image file is required.';
		if ($image && ($image['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && ((int) ($image['size'] ?? 0) > 5 * 1024 * 1024)) $errors['image'] = 'Image file must not exceed 5 MB.';
		if ($errors) $this->response->error('Validation failed', $errors, 422);
		return $data;
	}

	private function storeImage(): string
	{
		$image = $_FILES['image'] ?? null;
		if (!$image || ($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($image['tmp_name'])) {
			$this->response->error('A valid image file is required', null, 422);
		}

		$mime = (new \finfo(FILEINFO_MIME_TYPE))->file($image['tmp_name']);
		$extensions = [
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/webp' => 'webp',
		];
		if (!isset($extensions[$mime])) {
			$this->response->error('Only JPG, PNG, and WEBP images are allowed', null, 422);
		}

		$directory = __DIR__ . '/../../public/storage/selfie';
		if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
			$this->response->serverError('Unable to create evidence storage directory');
		}

		$fileName = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
		$target = $directory . DIRECTORY_SEPARATOR . $fileName;
		if (!move_uploaded_file($image['tmp_name'], $target)) {
			$this->response->serverError('Unable to store attendance evidence image');
		}
		return 'storage/selfie/' . $fileName;
	}

	private function removeStoredImage(string $imagePath): void
	{
		if (!preg_match('#^storage/selfie/[A-Za-z0-9._-]+$#', $imagePath)) {
			return;
		}
		$filePath = __DIR__ . '/../../public/' . str_replace('/', DIRECTORY_SEPARATOR, $imagePath);
		if (is_file($filePath)) {
			unlink($filePath);
		}
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
