<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\AttendanceCompanySchedule;
use App\Model\Student;
use PDOException;

class AttendanceCompanyScheduleController extends Controller
{
	private AttendanceCompanySchedule $schedules;
	private Student $students;

	public function __construct()
	{
		parent::__construct();
		$this->schedules = new AttendanceCompanySchedule();
		$this->students = new Student();
	}

	public function index(): void
	{
		$this->json(['success' => true, 'data' => $this->schedules->all()]);
	}

	public function show(string $id): void
	{
		$schedule = $this->schedules->find($this->positiveId($id));
		if (!$schedule) $this->response->notFound('Company schedule not found');
		$this->json(['success' => true, 'data' => $schedule]);
	}

	public function store(): void
	{
		$this->requireCsrf();
		$studentId = $this->studentScope();
		$data = $this->validatedSchedule();
		if ($studentId !== null && !$this->schedules->studentHasCompany($studentId, $data['company_id'])) {
			$this->response->forbidden('You may only create a schedule for your OJT company');
		}
		try {
			$scheduleId = $this->schedules->createSchedule($data);
		} catch (PDOException $exception) {
			if ((int) ($exception->errorInfo[1] ?? 0) === 1062) $this->response->error('A schedule for this company already exists', null, 409);
			$this->response->serverError('Unable to create company schedule');
		}
		$this->response->created($this->schedules->find($scheduleId), 'Company schedule created successfully');
	}

	public function update(string $id): void
	{
		$this->requireCsrf();
		$scheduleId = $this->positiveId($id);
		if (!$this->schedules->find($scheduleId)) $this->response->notFound('Company schedule not found');
		$data = $this->validatedSchedule();
		try {
			$this->schedules->updateSchedule($scheduleId, $data);
		} catch (PDOException $exception) {
			if ((int) ($exception->errorInfo[1] ?? 0) === 1062) $this->response->error('A schedule for this company already exists', null, 409);
			$this->response->serverError('Unable to update company schedule');
		}
		$this->response->updated($this->schedules->find($scheduleId), 'Company schedule updated successfully');
	}

	public function destroy(string $id): void
	{
		$this->requireCsrf();
		$scheduleId = $this->positiveId($id);
		if (!$this->schedules->find($scheduleId)) $this->response->notFound('Company schedule not found');
		if ($this->schedules->deleteSchedule($scheduleId) === 0) $this->response->notFound('Company schedule not found');
		$this->response->deleted('Company schedule deleted successfully');
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

	private function validatedSchedule(): array
	{
		$data = [
			'company_id' => filter_var($this->input('company_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]),
			'morning_in' => $this->timeInput('morning_in'),
			'morning_out' => $this->timeInput('morning_out'),
			'afternoon_in' => $this->timeInput('afternoon_in'),
			'afternoon_out' => $this->timeInput('afternoon_out'),
			'grace_period_minutes' => filter_var($this->input('grace_period_minutes', 15), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1440]]),
		];
		$errors = [];
		if (!$data['company_id'] || !$this->schedules->companyExists($data['company_id'])) $errors['company_id'] = 'A valid company is required.';
		foreach (['morning_in', 'morning_out', 'afternoon_in', 'afternoon_out'] as $field) {
			if (!$data[$field]) $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid time.';
		}
		if ($data['morning_out'] <= $data['morning_in']) $errors['morning_out'] = 'Morning out must be after morning in.';
		if ($data['afternoon_out'] <= $data['afternoon_in']) $errors['afternoon_out'] = 'Afternoon out must be after afternoon in.';
		if ($data['afternoon_in'] <= $data['morning_out']) $errors['afternoon_in'] = 'Afternoon in must be after morning out.';
		if ($data['grace_period_minutes'] === false) $errors['grace_period_minutes'] = 'Grace period must be between 0 and 1440 minutes.';
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

	private function positiveId(string $id): int
	{
		if (!ctype_digit($id) || (int) $id < 1) $this->response->badRequest('Invalid schedule ID');
		return (int) $id;
	}

	private function requireCsrf(): void
	{
		if (!Csrf::validate((string) (Csrf::fromRequest() ?? ''))) $this->response->error('Invalid CSRF token', null, 419);
	}
}
