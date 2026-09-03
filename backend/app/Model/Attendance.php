<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class Attendance extends Model
{
	protected string $table = 'attendance';

	public function all(?int $studentId = null): array
	{
		$sql = 'SELECT a.attendance_id, a.student_company_id,
					   a.attendance_date,
					   a.total_hours, a.status, a.created_at, a.updated_at,
					   osc.student_id, osc.company_id, c.company_name,
					   s.school_id, s.firstname, s.middlename, s.lastname
				FROM attendance a
				INNER JOIN ojt_student_company osc
					ON osc.student_company_id = a.student_company_id
				INNER JOIN ojt_company c ON c.company_id = osc.company_id
				INNER JOIN student s ON s.student_id = osc.student_id';
		$params = [];
		if ($studentId !== null) {
			$sql .= ' WHERE osc.student_id = :student_id';
			$params[':student_id'] = $studentId;
		}
		$sql .= ' ORDER BY a.attendance_date DESC, a.created_at DESC';
		return $this->fetchAll($sql, $params);
	}

	public function find(int $attendanceId): ?array
	{
		return $this->fetch(
			'SELECT a.attendance_id, a.student_company_id,
					a.attendance_date,
					a.total_hours, a.status, a.created_at, a.updated_at,
					osc.student_id, osc.company_id, c.company_name,
					s.school_id, s.firstname, s.middlename, s.lastname
			 FROM attendance a
			 INNER JOIN ojt_student_company osc
				 ON osc.student_company_id = a.student_company_id
			 INNER JOIN ojt_company c ON c.company_id = osc.company_id
			 INNER JOIN student s ON s.student_id = osc.student_id
			 WHERE a.attendance_id = :attendance_id LIMIT 1',
			[':attendance_id' => $attendanceId]
		);
	}

	public function findAssignment(int $studentCompanyId): ?array
	{
		return $this->fetch(
			'SELECT student_company_id, student_id
			 FROM ojt_student_company
			 WHERE student_company_id = :student_company_id LIMIT 1',
			[':student_company_id' => $studentCompanyId]
		);
	}

	public function createAttendance(array $data): int
	{
		$statement = $this->db->prepare(
			'INSERT INTO attendance
				(student_company_id, attendance_date, total_hours, status)
			 VALUES
				(:student_company_id, :attendance_date, :total_hours, :status)'
		);
		$this->bindAttendance($statement, $data);
		$statement->execute();
		return (int) $this->db->lastInsertId();
	}

	public function updateAttendance(int $attendanceId, array $data): int
	{
		$statement = $this->db->prepare(
			'UPDATE attendance SET student_company_id = :student_company_id,
				attendance_date = :attendance_date, total_hours = :total_hours,
				status = :status
			 WHERE attendance_id = :attendance_id'
		);
		$this->bindAttendance($statement, $data);
		$statement->bindValue(':attendance_id', $attendanceId, PDO::PARAM_INT);
		$statement->execute();
		return $statement->rowCount();
	}

	public function deleteAttendance(int $attendanceId): int
	{
		$statement = $this->db->prepare('DELETE FROM attendance WHERE attendance_id = :attendance_id');
		$statement->bindValue(':attendance_id', $attendanceId, PDO::PARAM_INT);
		$statement->execute();
		return $statement->rowCount();
	}

	private function bindAttendance($statement, array $data): void
	{
		$statement->bindValue(':student_company_id', $data['student_company_id'], PDO::PARAM_INT);
		$statement->bindValue(':attendance_date', $data['attendance_date'], PDO::PARAM_STR);
		$statement->bindValue(':total_hours', $data['total_hours'], PDO::PARAM_STR);
		$statement->bindValue(':status', $data['status'], PDO::PARAM_STR);
	}
}
