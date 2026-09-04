<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class AttendanceCompanySchedule extends Model
{
	protected string $table = 'ojt_company_schedule';

	public function all(?int $studentId = null): array
	{
		$sql = 'SELECT s.schedule_id, s.company_id, c.company_name,
					s.morning_in, s.morning_out, s.afternoon_in, s.afternoon_out,
					s.grace_period_minutes, s.created_at, s.updated_at
			 FROM ojt_company_schedule s
			 INNER JOIN ojt_company c ON c.company_id = s.company_id
			 LEFT JOIN ojt_student_company osc ON osc.company_id = s.company_id';
		$params = [];
		if ($studentId !== null) {
			$sql .= ' WHERE osc.student_id = :student_id';
			$params[':student_id'] = $studentId;
		}
		$sql .= ' ORDER BY c.company_name';
		return $this->fetchAll($sql, $params);
	}

	public function find(int $scheduleId): ?array
	{
		return $this->fetch(
			'SELECT s.schedule_id, s.company_id, c.company_name,
					s.morning_in, s.morning_out, s.afternoon_in, s.afternoon_out,
					s.grace_period_minutes, s.created_at, s.updated_at
			 FROM ojt_company_schedule s
			 INNER JOIN ojt_company c ON c.company_id = s.company_id
			 WHERE s.schedule_id = :schedule_id LIMIT 1',
			[':schedule_id' => $scheduleId]
		);
	}

	public function companyExists(int $companyId): bool
	{
		return (bool) $this->fetch(
			'SELECT company_id FROM ojt_company WHERE company_id = :company_id LIMIT 1',
			[':company_id' => $companyId]
		);
	}

	public function studentHasCompany(int $studentId, int $companyId): bool
	{
		return (bool) $this->fetch(
			'SELECT student_company_id
			 FROM ojt_student_company
			 WHERE student_id = :student_id AND company_id = :company_id LIMIT 1',
			[':student_id' => $studentId, ':company_id' => $companyId]
		);
	}

	public function createSchedule(array $data): int
	{
		$statement = $this->db->prepare(
			'INSERT INTO ojt_company_schedule
				(company_id, morning_in, morning_out, afternoon_in, afternoon_out, grace_period_minutes)
			 VALUES
				(:company_id, :morning_in, :morning_out, :afternoon_in, :afternoon_out, :grace_period_minutes)'
		);
		$this->bindSchedule($statement, $data);
		$statement->execute();
		return (int) $this->db->lastInsertId();
	}

	public function updateSchedule(int $scheduleId, array $data): int
	{
		$statement = $this->db->prepare(
			'UPDATE ojt_company_schedule SET company_id = :company_id,
				morning_in = :morning_in, morning_out = :morning_out,
				afternoon_in = :afternoon_in, afternoon_out = :afternoon_out,
				grace_period_minutes = :grace_period_minutes
			 WHERE schedule_id = :schedule_id'
		);
		$this->bindSchedule($statement, $data);
		$statement->bindValue(':schedule_id', $scheduleId, PDO::PARAM_INT);
		$statement->execute();
		return $statement->rowCount();
	}

	public function deleteSchedule(int $scheduleId): int
	{
		$statement = $this->db->prepare('DELETE FROM ojt_company_schedule WHERE schedule_id = :schedule_id');
		$statement->bindValue(':schedule_id', $scheduleId, PDO::PARAM_INT);
		$statement->execute();
		return $statement->rowCount();
	}

	private function bindSchedule($statement, array $data): void
	{
		$statement->bindValue(':company_id', $data['company_id'], PDO::PARAM_INT);
		$statement->bindValue(':morning_in', $data['morning_in'], PDO::PARAM_STR);
		$statement->bindValue(':morning_out', $data['morning_out'], PDO::PARAM_STR);
		$statement->bindValue(':afternoon_in', $data['afternoon_in'], PDO::PARAM_STR);
		$statement->bindValue(':afternoon_out', $data['afternoon_out'], PDO::PARAM_STR);
		$statement->bindValue(':grace_period_minutes', $data['grace_period_minutes'], PDO::PARAM_INT);
	}
}
