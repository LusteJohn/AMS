<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\Program;

class ProgramController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $collegeId = (int)$this->query('college_id', 0);
        $model = new Program();

        if ($collegeId > 0) {
            $data = $model->getByCollegeId($collegeId);
        } else {
            $data = $model->getAll();
        }

        $this->response->success($data);
    }

    public function show(int $id): void
    {
        $model = new Program();
        $item = $model->getById($id);
        if (!$item) {
            $this->response->notFound('Program not found');
        }
        $this->response->success($item);
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->abort(405, 'Method not allowed');
        }

        if (!$this->validateCsrf()) {
            $this->response->forbidden('Invalid CSRF token');
        }

        $collegeId = (int)$this->input('college_id', 0);
        $program = trim($this->input('program', ''));

        if ($collegeId <= 0 || $program === '') {
            $this->response->badRequest('college_id and program are required');
        }

        $model = new Program();
        $id = $model->create(['college_id' => $collegeId, 'program' => $program]);
        $this->response->created(['program_id' => $id]);
    }

    public function update(int $id): void
    {
        if (!$this->isPost() && !$this->isPut()) {
            $this->abort(405, 'Method not allowed');
        }

        if (!$this->validateCsrf()) {
            $this->response->forbidden('Invalid CSRF token');
        }

        $collegeId = (int)$this->input('college_id', 0);
        $program = trim($this->input('program', ''));

        if ($collegeId <= 0 || $program === '') {
            $this->response->badRequest('college_id and program are required');
        }

        $model = new Program();
        $rows = $model->updateProgram($id, ['college_id' => $collegeId, 'program' => $program]);
        $this->response->success(['updated' => $rows]);
    }

    public function delete(int $id): void
    {
        if (!$this->isDelete() && !$this->isPost()) {
            $this->abort(405, 'Method not allowed');
        }

        $model = new Program();
        $rows = $model->deleteProgram($id);
        $this->response->success(['deleted' => $rows]);
    }

    private function validateCsrf(): bool
    {
        $raw = file_get_contents('php://input');
        $token = null;

        if ($raw) {
            $data = json_decode($raw, true);
            if (is_array($data) && isset($data['_csrf'])) {
                $token = (string)$data['_csrf'];
            }
        }

        if (!$token) {
            $token = $_POST['_csrf'] ?? $_GET['_csrf'] ?? null;
        }

        if (!$token) {
            return false;
        }

        return Csrf::validate($token);
    }
}
