<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class AttendanceLog extends Model
{
	protected string $table = 'attendance_log';

	public function all(?int $studentId = null, ?int $attendanceId = null): array
	{
		$sql = 'SELECT al.attendance_log_id, al.attendance_id,
					   al.attendance_type, al.attendance_time,
					   al.status,
					   al.created_at, al.updated_at,
					   a.attendance_date, osc.student_id,
					   osc.company_id, c.company_name
				FROM attendance_log al
				INNER JOIN attendance a ON a.attendance_id = al.attendance_id
				INNER JOIN ojt_student_company osc
					ON osc.student_company_id = a.student_company_id
				INNER JOIN ojt_company c ON c.company_id = osc.company_id';
		$conditions = [];
		$params = [];
		if ($studentId !== null) {
			$conditions[] = 'osc.student_id = :student_id';
			$params[':student_id'] = $studentId;
		}
		if ($attendanceId !== null) {
			$conditions[] = 'al.attendance_id = :attendance_id';
			$params[':attendance_id'] = $attendanceId;
		}
		if ($conditions) $sql .= ' WHERE ' . implode(' AND ', $conditions);
		$sql .= ' ORDER BY a.attendance_date DESC, al.attendance_type';
		return $this->fetchAll($sql, $params);
	}

	public function find(int $logId): ?array
	{
		return $this->fetch(
			'SELECT al.attendance_log_id, al.attendance_id,
					al.attendance_type, al.attendance_time,
					al.status,
					al.created_at, al.updated_at,
					a.attendance_date, osc.student_id,
					osc.company_id, c.company_name
			 FROM attendance_log al
			 INNER JOIN attendance a ON a.attendance_id = al.attendance_id
			 INNER JOIN ojt_student_company osc
				 ON osc.student_company_id = a.student_company_id
			 INNER JOIN ojt_company c ON c.company_id = osc.company_id
			 WHERE al.attendance_log_id = :attendance_log_id LIMIT 1',
			[':attendance_log_id' => $logId]
		);
	}

	public function findAttendance(int $attendanceId): ?array
	{
		return $this->fetch(
			'SELECT a.attendance_id, osc.student_id
			 FROM attendance a
			 INNER JOIN ojt_student_company osc
				 ON osc.student_company_id = a.student_company_id
			 WHERE a.attendance_id = :attendance_id LIMIT 1',
			[':attendance_id' => $attendanceId]
		);
	}

	public function createLog(array $data): int
	{
		$statement = $this->db->prepare(
			'INSERT INTO attendance_log (attendance_id, attendance_type, attendance_time, status)
			 VALUES (:attendance_id, :attendance_type, :attendance_time, :status)'
		);
		$this->bindLog($statement, $data);
		$statement->execute();
		return (int) $this->db->lastInsertId();
	}

	public function updateLog(int $logId, array $data): int
	{
		$statement = $this->db->prepare(
			'UPDATE attendance_log SET attendance_id = :attendance_id,
				attendance_type = :attendance_type, attendance_time = :attendance_time,
				status = :status
			 WHERE attendance_log_id = :attendance_log_id'
		);
		$this->bindLog($statement, $data);
		$statement->bindValue(':attendance_log_id', $logId, PDO::PARAM_INT);
		$statement->execute();
		return $statement->rowCount();
	}

	public function deleteLog(int $logId): int
	{
		$statement = $this->db->prepare('DELETE FROM attendance_log WHERE attendance_log_id = :attendance_log_id');
		$statement->bindValue(':attendance_log_id', $logId, PDO::PARAM_INT);
		$statement->execute();
		return $statement->rowCount();
	}

	private function bindLog($statement, array $data): void
	{
		$statement->bindValue(':attendance_id', $data['attendance_id'], PDO::PARAM_INT);
		$statement->bindValue(':attendance_type', $data['attendance_type'], PDO::PARAM_STR);
		$statement->bindValue(':attendance_time', $data['attendance_time'], PDO::PARAM_STR);
		$statement->bindValue(':status', $data['status'], PDO::PARAM_STR);
	}
}
