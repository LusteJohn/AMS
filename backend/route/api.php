<?php

// If this file is accessed directly (not via public/index.php), ensure autoload is available
if (!class_exists('\App\\Core\\Router')) {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }
}

use App\Core\Router;
use App\Core\Database;
use App\Core\Csrf;
use App\Controller\UserController;
use App\Controller\CollegeController;
use App\Controller\ProgramController;
use App\Controller\SectionController;
use App\Controller\StudentController;
use App\Controller\FacultyController;
use App\Controller\OjtCompanyController;
use App\Controller\CompanySupervisorController;
use App\Controller\OjtStudentCompanyController;
use App\Controller\AttendanceController;
use App\Controller\AttendanceEvidenceController;
use App\Controller\AttendanceLogController;
use App\Controller\AttendanceCompanyScheduleController;
use App\Middleware\AdminMiddleware;
use App\Middleware\FacultyMiddleware;
use App\Middleware\StudentMiddleware;

$router = new Router();

// CORS middleware - adjust origin as needed for your frontend
$router->middleware(function () {
    // Allow your frontend origin (change to specific origin in production)
    header('Access-Control-Allow-Origin: http://localhost:5173');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
    header('Access-Control-Allow-Credentials: true');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
});

$router->get('/api/health', function () {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $connection = Database::getInstance()->getConnection();
        $connection->query('SELECT 1');

        echo json_encode([
            'success' => true,
            'message' => 'API and database are connected',
            'data' => ['api' => 'ok', 'database' => 'ok'],
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $exception) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'message' => 'API is reachable, but the database connection failed',
            'data' => ['api' => 'ok', 'database' => 'error'],
        ], JSON_UNESCAPED_UNICODE);
    }
});

$router->get('/api/csrf-token', function () {
    $response = new \App\Core\Response();
    $response->success(['csrf_token' => Csrf::token()]);
});

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->get('/users', [UserController::class, 'index']);
    $router->get('/users/{id}', [UserController::class, 'show']);
    $router->post('/users', [UserController::class, 'store']);
    $router->put('/users/{id}', [UserController::class, 'update']);
    $router->delete('/users/{id}', [UserController::class, 'destroy']);
}, [AdminMiddleware::class . '::requireAdmin']);

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->get('/student/profile', [StudentController::class, 'profile']);
    $router->get('/student/sections', [SectionController::class, 'index']);
    $router->post('/student/profile', [StudentController::class, 'storeProfile']);
    $router->put('/student/profile', [StudentController::class, 'updateProfile']);
    $router->delete('/student/profile', [StudentController::class, 'destroyProfile']);
}, [StudentMiddleware::class . '::requireStudent']);

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->get('/faculty/profile', [FacultyController::class, 'profile']);
    $router->put('/faculty/profile', [FacultyController::class, 'updateProfile']);
}, [FacultyMiddleware::class . '::requireFaculty']);

$canManageStudents = function (): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $role = strtolower(trim((string) ($_SESSION['user']['role'] ?? '')));
    if (!in_array($role, ['admin', 'faculty'], true)) {
        (new \App\Core\Response())->unauthorized('Admin or faculty access required');
    }
};

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->get('/students', [StudentController::class, 'index']);
    $router->post('/students', [StudentController::class, 'adminStore']);
    $router->post('/students/import', [StudentController::class, 'importCsv']);
}, [$canManageStudents]);

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->get('/faculty', [FacultyController::class, 'index']);
    $router->get('/faculty/{id}', [FacultyController::class, 'show']);
    $router->post('/faculty', [FacultyController::class, 'store']);
    $router->put('/faculty/{id}', [FacultyController::class, 'update']);
    $router->delete('/faculty/{id}', [FacultyController::class, 'destroy']);
}, [AdminMiddleware::class . '::requireAdmin']);

$canManageCompanies = function (): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $role = strtolower(trim((string) ($_SESSION['user']['role'] ?? '')));
    if (!in_array($role, ['admin', 'faculty', 'student'], true)) {
        (new \App\Core\Response())->unauthorized('Admin, faculty, or student access required');
    }
};

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->get('/companies', [OjtCompanyController::class, 'index']);
    $router->get('/companies/{id}', [OjtCompanyController::class, 'show']);
    $router->post('/companies', [OjtCompanyController::class, 'store']);
    $router->put('/companies/{id}', [OjtCompanyController::class, 'update']);
}, [$canManageCompanies]);

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->delete('/companies/{id}', [OjtCompanyController::class, 'destroy']);
}, [AdminMiddleware::class . '::requireAdmin']);

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->get('/ojt-student-companies', [OjtStudentCompanyController::class, 'index']);
    $router->get('/ojt-student-companies/{id}', [OjtStudentCompanyController::class, 'show']);
    $router->post('/ojt-student-companies', [OjtStudentCompanyController::class, 'store']);
    $router->put('/ojt-student-companies/{id}', [OjtStudentCompanyController::class, 'update']);
    $router->delete('/ojt-student-companies/{id}', [OjtStudentCompanyController::class, 'destroy']);
}, [$canManageCompanies]);

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->get('/attendance', [AttendanceController::class, 'index']);
    $router->get('/attendance/{id}', [AttendanceController::class, 'show']);
    $router->post('/attendance', [AttendanceController::class, 'store']);
    $router->put('/attendance/{id}', [AttendanceController::class, 'update']);
    $router->delete('/attendance/{id}', [AttendanceController::class, 'destroy']);
}, [$canManageCompanies]);

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->get('/attendance-logs', [AttendanceLogController::class, 'index']);
    $router->get('/attendance-logs/{id}', [AttendanceLogController::class, 'show']);
    $router->post('/attendance-logs', [AttendanceLogController::class, 'store']);
    $router->put('/attendance-logs/{id}', [AttendanceLogController::class, 'update']);
    $router->delete('/attendance-logs/{id}', [AttendanceLogController::class, 'destroy']);
}, [$canManageCompanies]);

// Attendance evidence routes are reserved for a future selfie/evidence workflow.
// $router->groupWithMiddleware('/api', function (Router $router) {
//     $router->get('/attendance-evidence', [AttendanceEvidenceController::class, 'index']);
//     $router->get('/attendance-evidence/{id}', [AttendanceEvidenceController::class, 'show']);
//     $router->post('/attendance-evidence', [AttendanceEvidenceController::class, 'store']);
//     $router->put('/attendance-evidence/{id}', [AttendanceEvidenceController::class, 'update']);
//     $router->delete('/attendance-evidence/{id}', [AttendanceEvidenceController::class, 'destroy']);
// }, [$canManageCompanies]);

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->post('/company-schedules', [AttendanceCompanyScheduleController::class, 'store']);
}, [StudentMiddleware::class . '::requireStudent']);

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->get('/company-schedules', [AttendanceCompanyScheduleController::class, 'index']);
}, [$canManageCompanies]);

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->get('/company-schedules/{id}', [AttendanceCompanyScheduleController::class, 'show']);
    $router->put('/company-schedules/{id}', [AttendanceCompanyScheduleController::class, 'update']);
    $router->delete('/company-schedules/{id}', [AttendanceCompanyScheduleController::class, 'destroy']);
}, [AdminMiddleware::class . '::requireAdmin']);

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->get('/company-supervisors', [CompanySupervisorController::class, 'index']);
    $router->get('/company-supervisors/{id}', [CompanySupervisorController::class, 'show']);
    $router->post('/company-supervisors', [CompanySupervisorController::class, 'store']);
    $router->put('/company-supervisors/{id}', [CompanySupervisorController::class, 'update']);
    $router->delete('/company-supervisors/{id}', [CompanySupervisorController::class, 'destroy']);
}, [$canManageCompanies]);

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->get('/colleges', [CollegeController::class, 'index']);
    $router->get('/colleges/{id}', [CollegeController::class, 'show']);
    $router->post('/colleges', [CollegeController::class, 'store']);
    $router->put('/colleges/{id}', [CollegeController::class, 'update']);
    $router->delete('/colleges/{id}', [CollegeController::class, 'delete']);

    $router->get('/programs', [ProgramController::class, 'index']);
    $router->get('/programs/{id}', [ProgramController::class, 'show']);
    $router->post('/programs', [ProgramController::class, 'store']);
    $router->put('/programs/{id}', [ProgramController::class, 'update']);
    $router->delete('/programs/{id}', [ProgramController::class, 'delete']);

    $router->get('/sections', [SectionController::class, 'index']);
    $router->get('/sections/{id}', [SectionController::class, 'show']);
    $router->post('/sections', [SectionController::class, 'store']);
    $router->put('/sections/{id}', [SectionController::class, 'update']);
    $router->delete('/sections/{id}', [SectionController::class, 'delete']);
}, [AdminMiddleware::class . '::requireAdmin']);

$router->post('/api/auth/register', [UserController::class, 'register']);
$router->post('/api/auth/login', [UserController::class, 'login']);
$router->post('/api/auth/logout', [UserController::class, 'logout']);
$router->get('/api/auth/session', [UserController::class, 'session']);

return $router;
