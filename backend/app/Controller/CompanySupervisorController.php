<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\CompanySupervisor;
use PDOException;

class CompanySupervisorController extends Controller
{
    private CompanySupervisor $supervisors;

    public function __construct()
    {
        parent::__construct();
        $this->supervisors = new CompanySupervisor();
    }

    public function index(): void
    {
        $companyId = $this->query('company_id');
        $companyId = $companyId === null || $companyId === '' ? null : $this->positiveId((string) $companyId, 'company');
        $this->json(['success' => true, 'data' => $this->supervisors->all($companyId)]);
    }

    public function show(string $id): void
    {
        $supervisorId = $this->positiveId($id, 'supervisor');
        $supervisor = $this->supervisors->find($supervisorId);
        if (!$supervisor) {
            $this->response->notFound('Company supervisor not found');
        }
        $this->json(['success' => true, 'data' => $supervisor]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        try {
            $supervisorId = $this->supervisors->createSupervisor($this->validatedSupervisor());
        } catch (PDOException $exception) {
            $this->response->serverError('Unable to create company supervisor');
        }
        $this->response->created($this->supervisors->find($supervisorId), 'Company supervisor created successfully');
    }

    public function update(string $id): void
    {
        $this->requireCsrf();
        $supervisorId = $this->positiveId($id, 'supervisor');
        if (!$this->supervisors->find($supervisorId)) {
            $this->response->notFound('Company supervisor not found');
        }
        try {
            $this->supervisors->updateSupervisor($supervisorId, $this->validatedSupervisor());
        } catch (PDOException $exception) {
            $this->response->serverError('Unable to update company supervisor');
        }
        $this->response->updated($this->supervisors->find($supervisorId), 'Company supervisor updated successfully');
    }

    public function destroy(string $id): void
    {
        $this->requireCsrf();
        $supervisorId = $this->positiveId($id, 'supervisor');
        if ($this->supervisors->deleteSupervisor($supervisorId) === 0) {
            $this->response->notFound('Company supervisor not found');
        }
        $this->response->deleted('Company supervisor deleted successfully');
    }

    private function validatedSupervisor(): array
    {
        $data = [
            'company_id' => filter_var($this->input('company_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]),
            'firstname' => $this->textInput('firstname'),
            'middlename' => $this->textInput('middlename'),
            'lastname' => $this->textInput('lastname'),
            'name_ext' => $this->textInput('name_ext'),
            'gender' => $this->textInput('gender'),
            'address' => $this->textInput('address'),
        ];
        $errors = [];
        if (!$data['company_id']) {
            $errors['company_id'] = 'A valid company is required.';
        }
        foreach (['firstname' => 50, 'middlename' => 50, 'lastname' => 50, 'name_ext' => 5, 'gender' => 20, 'address' => 255] as $field => $maxLength) {
            if (!in_array($field, ['middlename', 'name_ext'], true) && $data[$field] === '') {
                $errors[$field] = ucfirst($field) . ' is required.';
            } elseif (strlen($data[$field]) > $maxLength) {
                $errors[$field] = ucfirst($field) . " must not exceed {$maxLength} characters.";
            }
        }
        if ($errors) {
            $this->response->error('Validation failed', $errors, 422);
        }
        return $data;
    }

    private function textInput(string $key): string
    {
        $value = strip_tags((string) $this->input($key, ''));
        return trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '');
    }

    private function positiveId(string $id, string $label): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->response->badRequest("Invalid {$label} ID");
        }
        return (int) $id;
    }

    private function requireCsrf(): void
    {
        if (!Csrf::validate((string) (Csrf::fromRequest() ?? ''))) {
            $this->response->error('Invalid CSRF token', null, 419);
        }
    }
}
