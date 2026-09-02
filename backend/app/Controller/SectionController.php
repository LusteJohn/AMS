<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\Section;

class SectionController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $programId = (int)$this->query('program_id', 0);
        $model = new Section();

        if ($programId > 0) {
            $data = $model->sections($programId);
        } else {
            $data = $model->getAll();
        }

        $this->response->success($data);
    }

    public function show(int $id): void
    {
        $model = new Section();
        $item = $model->getById($id);
        if (!$item) {
            $this->response->notFound('Section not found');
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

        $programId = (int)$this->input('program_id', 0);
        $section = trim($this->input('section', ''));

        if ($programId <= 0 || $section === '') {
            $this->response->badRequest('program_id and section are required');
        }

        $model = new Section();
        $id = $model->create(['program_id' => $programId, 'section' => $section]);
        $this->response->created(['section_id' => $id]);
    }

    public function update(int $id): void
    {
        if (!$this->isPost() && !$this->isPut()) {
            $this->abort(405, 'Method not allowed');
        }

        if (!$this->validateCsrf()) {
            $this->response->forbidden('Invalid CSRF token');
        }

        $programId = (int)$this->input('program_id', 0);
        $section = trim($this->input('section', ''));

        if ($programId <= 0 || $section === '') {
            $this->response->badRequest('program_id and section are required');
        }

        $model = new Section();
        $rows = $model->updateSection($id, ['program_id' => $programId, 'section' => $section]);
        $this->response->success(['updated' => $rows]);
    }

    public function delete(int $id): void
    {
        if (!$this->isDelete() && !$this->isPost()) {
            $this->abort(405, 'Method not allowed');
        }

        $model = new Section();
        $rows = $model->deleteSection($id);
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
