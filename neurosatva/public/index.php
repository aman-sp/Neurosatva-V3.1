<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/Env.php';
require dirname(__DIR__) . '/app/Core/helpers.php';
require dirname(__DIR__) . '/app/Core/Database.php';
require dirname(__DIR__) . '/app/Core/Session.php';
require dirname(__DIR__) . '/app/Core/Auth.php';
require dirname(__DIR__) . '/app/Core/Csrf.php';
require dirname(__DIR__) . '/app/Core/Router.php';

foreach (glob(dirname(__DIR__) . '/app/Models/*.php') as $file) {
    require $file;
}
foreach (glob(dirname(__DIR__) . '/app/Services/*.php') as $file) {
    require $file;
}
foreach (glob(dirname(__DIR__) . '/app/Controllers/*.php') as $file) {
    require $file;
}

Session::start();

if (app_config('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

$router = new Router();

$router->get('/', [AuthController::class, 'adminLogin']);
$router->get('/admin/login', [AuthController::class, 'adminLogin']);
$router->post('/admin/login', [AuthController::class, 'authenticateAdmin']);
$router->get('/tutor/login', [AuthController::class, 'tutorLogin']);
$router->post('/tutor/login', [AuthController::class, 'authenticateTutor']);
$router->get('/tutor/register', [AuthController::class, 'tutorRegister']);
$router->post('/tutor/register', [AuthController::class, 'submitTutorRegistration']);
$router->get('/tutor/register/success', [AuthController::class, 'tutorRegistrationSuccess']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/admin/dashboard', [AdminController::class, 'dashboard']);
$router->get('/admin/tutors', [AdminController::class, 'tutors']);
$router->get('/admin/registration-requests', [AdminController::class, 'registrationRequests']);
$router->get('/admin/registration-requests/view', [AdminController::class, 'viewRegistrationRequest']);
$router->post('/admin/registration-requests/approve', [AdminController::class, 'approveRegistrationRequest']);
$router->post('/admin/registration-requests/reject', [AdminController::class, 'rejectRegistrationRequest']);
$router->get('/admin/tutors/edit', [AdminController::class, 'editTutor']);
$router->post('/admin/tutors/edit', [AdminController::class, 'updateTutor']);
$router->post('/admin/tutors/delete', [AdminController::class, 'deleteTutor']);
$router->get('/admin/videos', [AdminController::class, 'videos']);
$router->post('/admin/videos', [AdminController::class, 'storeVideo']);
$router->post('/admin/videos/verify', [AdminController::class, 'verifyVideo']);
$router->get('/admin/modules', [ModuleController::class, 'index']);
$router->get('/admin/modules/create', [ModuleController::class, 'create']);
$router->post('/admin/modules', [ModuleController::class, 'store']);
$router->get('/admin/modules/view', [ModuleController::class, 'view']);
$router->get('/admin/modules/edit', [ModuleController::class, 'edit']);
$router->post('/admin/modules/update', [ModuleController::class, 'update']);
$router->post('/admin/modules/delete', [ModuleController::class, 'delete']);
$router->get('/admin/modules/test', [ModuleController::class, 'test']);
$router->get('/admin/assign-module', [AssignmentController::class, 'index']);
$router->post('/admin/assign-module', [AssignmentController::class, 'store']);
$router->post('/admin/assignments/update', [AssignmentController::class, 'update']);
$router->post('/admin/assignments/delete', [AssignmentController::class, 'delete']);
$router->get('/admin/profile', [AdminController::class, 'profile']);
$router->post('/admin/profile', [AdminController::class, 'updateProfile']);

$router->get('/tutor/dashboard', [TutorController::class, 'dashboard']);
$router->get('/tutor/videos', [TutorController::class, 'videos']);
$router->get('/tutor/modules', [TutorModuleController::class, 'index']);
$router->get('/tutor/modules/play', [TutorModuleController::class, 'play']);
$router->get('/tutor/instructions', [TutorController::class, 'instructions']);
$router->post('/tutor/instructions', [TutorController::class, 'submitVideoLink']);
$router->get('/tutor/profile', [TutorController::class, 'profile']);
$router->get('/tutor/official-gmail/setup', [TutorController::class, 'officialGmailSetup']);
$router->post('/tutor/official-gmail', [TutorController::class, 'saveOfficialGmail']);
$router->post('/tutor/official-gmail/verify-otp', [TutorController::class, 'verifyOfficialGmailOtp']);

$router->get('/modules/file', [ModuleFileController::class, 'serve']);
$router->get('/api/modules', [ModuleApiController::class, 'index']);
$router->post('/api/modules', [ModuleApiController::class, 'store']);
$router->put('/api/modules/{id}', [ModuleApiController::class, 'update']);
$router->delete('/api/modules/{id}', [ModuleApiController::class, 'delete']);
$router->post('/api/modules/test', [ModuleApiController::class, 'test']);
$router->post('/api/assignments', [AssignmentApiController::class, 'store']);
$router->put('/api/assignments/{id}', [AssignmentApiController::class, 'update']);
$router->delete('/api/assignments/{id}', [AssignmentApiController::class, 'delete']);
$router->get('/api/tutor/modules', [TutorApiController::class, 'modules']);
$router->get('/api/tutor/module/{id}', [TutorApiController::class, 'module']);
$router->post('/api/runtime/start', [RuntimeApiController::class, 'start']);
$router->post('/api/runtime/end', [RuntimeApiController::class, 'end']);

try {
    $router->dispatch(request_method(), parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
} catch (PDOException $exception) {
    http_response_code(500);
    view('errors/database', ['title' => 'Database Setup Required'], 'auth');
} catch (Throwable $exception) {
    http_response_code(500);
    if (app_config('debug')) {
        exit('Application error: ' . e($exception->getMessage()));
    }
    view('errors/404', ['title' => 'Application Error'], 'auth');
}
