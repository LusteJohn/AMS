<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\College;

class CollegeController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $model = new College();
        $data = $model->getAll();
        $this->response->success($data);
    }

    public function show(int $id): void
    {
        $model = new College();
        $college = $model->getById($id);
        if (!$college) {
            $this->response->notFound('College not found');
        }
        $this->response->success($college);
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->abort(405, 'Method not allowed');
        }

        if (!$this->validateCsrf()) {
            $this->response->forbidden('Invalid CSRF token');
        }

        $name = trim($this->input('college_name', ''));
        if ($name === '') {
            $this->response->badRequest('college_name is required');
        }

        $model = new College();
        $id = $model->create(['college_name' => $name]);
        $this->response->created(['college_id' => $id]);
    }

    public function update(int $id): void
    {
        if (!$this->isPost() && !$this->isPut()) {
            $this->abort(405, 'Method not allowed');
        }

        if (!$this->validateCsrf()) {
            $this->response->forbidden('Invalid CSRF token');
        }

        $name = trim($this->input('college_name', ''));
        if ($name === '') {
            $this->response->badRequest('college_name is required');
        }

        $model = new College();
        $rows = $model->updateCollege($id, ['college_name' => $name]);
        $this->response->success(['updated' => $rows]);
    }

    public function delete(int $id): void
    {
        if (!$this->isDelete() && !$this->isPost()) {
            $this->abort(405, 'Method not allowed');
        }

        $model = new College();
        $rows = $model->deleteCollege($id);
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
