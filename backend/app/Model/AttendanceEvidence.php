<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class AttendanceEvidence extends Model
{
	protected string $table = 'attendance_evidence';

	public function all(?int $studentId = null, ?int $attendanceLogId = null): array
	{
		$sql = 'SELECT ae.attendance_evidence_id, ae.attendance_log_id,
					   ae.evidence_type, ae.image_path, ae.captured_at,
					   ae.created_at, ae.updated_at,
					   al.attendance_id, al.attendance_type, al.attendance_time,
					   a.attendance_date, a.student_company_id,
					   osc.student_id, osc.company_id, c.company_name
				FROM attendance_evidence ae
				INNER JOIN attendance_log al ON al.attendance_log_id = ae.attendance_log_id
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
		if ($attendanceLogId !== null) {
			$conditions[] = 'ae.attendance_log_id = :attendance_log_id';
			$params[':attendance_log_id'] = $attendanceLogId;
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
			'SELECT ae.attendance_evidence_id, ae.attendance_log_id,
					ae.evidence_type, ae.image_path, ae.captured_at,
					ae.created_at, ae.updated_at,
					al.attendance_id, al.attendance_type, al.attendance_time,
					a.attendance_date, a.student_company_id,
					osc.student_id, osc.company_id, c.company_name
			 FROM attendance_evidence ae
			 INNER JOIN attendance_log al ON al.attendance_log_id = ae.attendance_log_id
			 INNER JOIN attendance a ON a.attendance_id = al.attendance_id
			 INNER JOIN ojt_student_company osc
				 ON osc.student_company_id = a.student_company_id
			 INNER JOIN ojt_company c ON c.company_id = osc.company_id
			 WHERE ae.attendance_evidence_id = :attendance_evidence_id LIMIT 1',
			[':attendance_evidence_id' => $evidenceId]
		);
	}

	public function findLog(int $attendanceLogId): ?array
	{
		return $this->fetch(
			'SELECT al.attendance_log_id, al.attendance_id, osc.student_id
			 FROM attendance_log al
			 INNER JOIN attendance a ON a.attendance_id = al.attendance_id
			 INNER JOIN ojt_student_company osc
				 ON osc.student_company_id = a.student_company_id
			 WHERE al.attendance_log_id = :attendance_log_id LIMIT 1',
			[':attendance_log_id' => $attendanceLogId]
		);
	}

	public function createEvidence(array $data): int
	{
		$statement = $this->db->prepare(
			'INSERT INTO attendance_evidence
				(attendance_log_id, evidence_type, image_path)
			 VALUES
				(:attendance_log_id, :evidence_type, :image_path)'
		);
		$this->bindEvidence($statement, $data);
		$statement->execute();
		return (int) $this->db->lastInsertId();
	}

	public function updateEvidence(int $evidenceId, array $data): int
	{
		$statement = $this->db->prepare(
			'UPDATE attendance_evidence SET attendance_log_id = :attendance_log_id,
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
		$statement->bindValue(':attendance_log_id', $data['attendance_log_id'], PDO::PARAM_INT);
		$statement->bindValue(':evidence_type', $data['evidence_type'], PDO::PARAM_STR);
		$statement->bindValue(':image_path', $data['image_path'], PDO::PARAM_STR);
	}
}
