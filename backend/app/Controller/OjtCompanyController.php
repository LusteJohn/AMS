<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\OjtCompany;
use PDOException;

class OjtCompanyController extends Controller
{
    private OjtCompany $companies;

    public function __construct()
    {
        parent::__construct();
        $this->companies = new OjtCompany();
    }

    public function index(): void
    {
        $this->json(['success' => true, 'data' => $this->companies->all()]);
    }

    public function show(string $id): void
    {
        $companyId = $this->validatedId($id);
        $company = $this->companies->find($companyId);
        if (!$company) {
            $this->response->notFound('OJT company not found');
        }
        $this->json(['success' => true, 'data' => $company]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = $this->validatedCompany();
        try {
            $companyId = $this->companies->createCompany($data);
        } catch (PDOException $exception) {
            $this->response->serverError('Unable to create OJT company');
        }
        $this->response->created($this->companies->find($companyId), 'OJT company created successfully');
    }

    public function update(string $id): void
    {
        $this->requireCsrf();
        $companyId = $this->validatedId($id);
        if (!$this->companies->find($companyId)) {
            $this->response->notFound('OJT company not found');
        }

        try {
            $this->companies->updateCompany($companyId, $this->validatedCompany());
        } catch (PDOException $exception) {
            $this->response->serverError('Unable to update OJT company');
        }
        $this->response->updated($this->companies->find($companyId), 'OJT company updated successfully');
    }

    public function destroy(string $id): void
    {
        $this->requireCsrf();
        $companyId = $this->validatedId($id);
        if (!$this->companies->find($companyId)) {
            $this->response->notFound('OJT company not found');
        }

        try {
            $deleted = $this->companies->deleteCompany($companyId);
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1451) {
                $this->response->error('Cannot delete this company while OJT requirements reference it', null, 409);
            }
            $this->response->serverError('Unable to delete OJT company');
        }
        if ($deleted === 0) {
            $this->response->notFound('OJT company not found');
        }
        $this->response->deleted('OJT company deleted successfully');
    }

    private function validatedCompany(): array
    {
        $companyName = $this->textInput('company_name');
        $description = $this->textInput('description');
        $contactNumber = $this->textInput('contact_number');
        $email = trim((string) $this->input('email_address', ''));
        $address = $this->textInput('address');
        $errors = [];

        if ($companyName === '' || strlen($companyName) > 150) {
            $errors['company_name'] = 'Company name is required and must not exceed 150 characters.';
        }
        if (strlen($description) > 65535) {
            $errors['description'] = 'Description is too long.';
        }
        if (strlen($contactNumber) > 30) {
            $errors['contact_number'] = 'Contact number must not exceed 30 characters.';
        }
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email_address'] = 'Email address must be valid.';
        }
        if ($email !== '' && strlen($email) > 100) {
            $errors['email_address'] = 'Email address must not exceed 100 characters.';
        }
        if ($address === '' || strlen($address) > 255) {
            $errors['address'] = 'Address is required and must not exceed 255 characters.';
        }
        if ($errors) {
            $this->response->error('Validation failed', $errors, 422);
        }

        return [
            'company_name' => $companyName,
            'description' => $description,
            'contact_number' => $contactNumber,
            'email_address' => $email === '' ? '' : strtolower($email),
            'address' => $address,
        ];
    }

    private function textInput(string $key): string
    {
        $value = strip_tags((string) $this->input($key, ''));
        return trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '');
    }

    private function validatedId(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->response->badRequest('Invalid company ID');
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
