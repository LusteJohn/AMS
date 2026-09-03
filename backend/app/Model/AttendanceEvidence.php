<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class AttendanceEvidence extends Model
{
	protected string $table = 'attendance_evidence';

	public function all(?int $studentId = null, ?int $attendanceId = null): array
	{
		$sql = 'SELECT ae.attendance_evidence_id, ae.attendance_id,
					   ae.evidence_type, ae.image_path, ae.captured_at,
					   ae.created_at, ae.updated_at,
					   a.attendance_date, a.student_company_id,
					   osc.student_id, osc.company_id, c.company_name
				FROM attendance_evidence ae
				INNER JOIN attendance a ON a.attendance_id = ae.attendance_id
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
			$conditions[] = 'ae.attendance_id = :attendance_id';
			$params[':attendance_id'] = $attendanceId;
		}
		if ($conditions) {
			$sql .= ' WHERE ' . implode(' AND ', $conditions);
		}
		$sql .= ' ORDER BY ae.created_at DESC';
		return $this->fetchAll($sql, $params);
	}

	public function find(int $evidenceId): ?array
	{
		return $this->fetch(
			'SELECT ae.attendance_evidence_id, ae.attendance_id,
					ae.evidence_type, ae.image_path, ae.captured_at,
					ae.created_at, ae.updated_at,
					a.attendance_date, a.student_company_id,
					osc.student_id, osc.company_id, c.company_name
			 FROM attendance_evidence ae
			 INNER JOIN attendance a ON a.attendance_id = ae.attendance_id
			 INNER JOIN ojt_student_company osc
				 ON osc.student_company_id = a.student_company_id
			 INNER JOIN ojt_company c ON c.company_id = osc.company_id
			 WHERE ae.attendance_evidence_id = :attendance_evidence_id LIMIT 1',
			[':attendance_evidence_id' => $evidenceId]
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

	public function createEvidence(array $data): int
	{
		$statement = $this->db->prepare(
			'INSERT INTO attendance_evidence
				(attendance_id, evidence_type, image_path)
			 VALUES
				(:attendance_id, :evidence_type, :image_path)'
		);
		$this->bindEvidence($statement, $data);
		$statement->execute();
		return (int) $this->db->lastInsertId();
	}

	public function updateEvidence(int $evidenceId, array $data): int
	{
		$statement = $this->db->prepare(
			'UPDATE attendance_evidence SET attendance_id = :attendance_id,
				evidence_type = :evidence_type, image_path = :image_path
			 WHERE attendance_evidence_id = :attendance_evidence_id'
		);
		$this->bindEvidence($statement, $data);
		$statement->bindValue(':attendance_evidence_id', $evidenceId, PDO::PARAM_INT);
		$statement->execute();
		return $statement->rowCount();
	}

	public function deleteEvidence(int $evidenceId): int
	{
		$statement = $this->db->prepare('DELETE FROM attendance_evidence WHERE attendance_evidence_id = :attendance_evidence_id');
		$statement->bindValue(':attendance_evidence_id', $evidenceId, PDO::PARAM_INT);
		$statement->execute();
		return $statement->rowCount();
	}

	private function bindEvidence($statement, array $data): void
	{
		$statement->bindValue(':attendance_id', $data['attendance_id'], PDO::PARAM_INT);
		$statement->bindValue(':evidence_type', $data['evidence_type'], PDO::PARAM_STR);
		$statement->bindValue(':image_path', $data['image_path'], PDO::PARAM_STR);
	}
}
