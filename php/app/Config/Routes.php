<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ── Auth ─────────────────────────────────────────────────────
$routes->match(['get', 'post'], '/', 'Shared\Auth::login');
$routes->match(['get', 'post'], 'login', 'Shared\Auth::login');
$routes->get('logout', 'Shared\Auth::logout');

// ── API auth (not behind authGuard — this is how a session is obtained) ──
$routes->post('api/auth/login', 'Api\AuthController::login');
$routes->post('api/auth/logout', 'Api\AuthController::logout');
$routes->get('api/auth/me', 'Api\AuthController::me');

// ── Pages (session required) ────────────────────────────────
$routes->group('', ['filter' => 'authGuard'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Admin\Dashboard::index');
    $routes->get('teacher-dashboard', 'Teacher\TeacherDashboard::index');
    $routes->get('adas-dashboard', 'Adas\AdasDashboard::index');
    $routes->match(['get', 'post'], 'submit-documents', 'Teacher\SubmitDocuments::index');
    $routes->match(['get', 'post'], 'documents', 'Admin\Documents::index');
    $routes->get('documents/(:num)/file', 'Shared\DocumentFile::show/$1');
    $routes->get('documents/(:num)/download', 'Shared\DocumentFile::download/$1');
    $routes->get('document-files/(:num)/download', 'Shared\DocumentFileDownload::show/$1');
    $routes->get('document-files/(:num)/preview', 'Shared\DocumentFileDownload::preview/$1');
    $routes->get('performance', 'Admin\Performance::index');
    $routes->get('enrollment-kpis', 'Admin\EnrollmentKpis::index');
    $routes->match(['get', 'post'], 'announcements', 'Shared\Announcements::index');
    $routes->match(['get', 'post'], 'parent-meetings', 'Shared\ParentMeetings::index');
    $routes->match(['get', 'post'], 'time-records', 'Shared\TimeRecords::index');
    $routes->post('time-records/import', 'Shared\TimeRecords::import');
    $routes->match(['get', 'post'], 'deped-documents', 'Shared\DepedDocuments::index');
    $routes->match(['get', 'post'], 'document-links', 'Shared\DocumentLinks::index');
    $routes->match(['get', 'post'], 'templates', 'Shared\Templates::index');
    $routes->get('templates/download/(:num)', 'Shared\Templates::download/$1');
    $routes->get('templates/preview/(:num)', 'Shared\Templates::preview/$1');
    $routes->match(['get', 'post'], 'property-management', 'Admin\Properties::index');
    $routes->match(['get', 'post'], 'users', 'Admin\Users::index');
    $routes->match(['get', 'post'], 'tasks', 'Admin\Tasks::index');
    $routes->match(['get', 'post'], 'tasks/(:num)', 'Admin\Tasks::view/$1');
    $routes->match(['get', 'post'], 'my-tasks', 'Shared\MyTasks::index');
    $routes->get('task-submissions/(:num)/download', 'Shared\TaskDownload::show/$1');
    $routes->get('task-submissions/(:num)/preview', 'Shared\TaskDownload::preview/$1');
    $routes->match(['get', 'post'], 'profile', 'Shared\Profile::index');
    $routes->post('notifications/(:num)/read', 'Shared\Notifications::markRead/$1');
    $routes->match(['get', 'post'], 'chat', 'Shared\Chat::index');
    $routes->get('chat/(:num)/messages', 'Shared\Chat::messages/$1');
    $routes->post('chat/(:num)/send', 'Shared\Chat::send/$1');
    $routes->get('chat/attachment/(:num)', 'Shared\Chat::attachment/$1');
});

// ── JSON API (session required) ─────────────────────────────
$routes->group('api', ['filter' => 'authGuard'], static function (RouteCollection $routes) {
    $routes->get('announcements', 'Api\AnnouncementsController::index');
    $routes->post('announcements', 'Api\AnnouncementsController::create');
    $routes->put('announcements', 'Api\AnnouncementsController::update');
    $routes->delete('announcements', 'Api\AnnouncementsController::delete');

    $routes->get('teachers', 'Api\TeachersController::index');
    $routes->post('teachers', 'Api\TeachersController::create');
    $routes->put('teachers', 'Api\TeachersController::update');
    $routes->delete('teachers', 'Api\TeachersController::delete');

    $routes->get('documents', 'Api\DocumentsController::index');
    $routes->post('documents/feedback', 'Api\DocumentsController::addFeedback');
    $routes->post('documents', 'Api\DocumentsController::create');
    $routes->put('documents', 'Api\DocumentsController::update');
    $routes->delete('documents', 'Api\DocumentsController::delete');

    $routes->get('performance', 'Api\PerformanceController::index');

    $routes->get('time-records', 'Api\TimeRecordsController::index');
    $routes->post('time-records', 'Api\TimeRecordsController::create');
    $routes->put('time-records', 'Api\TimeRecordsController::update');
    $routes->delete('time-records', 'Api\TimeRecordsController::delete');

    $routes->get('properties', 'Api\PropertiesController::index');
    $routes->post('properties', 'Api\PropertiesController::create');
    $routes->put('properties', 'Api\PropertiesController::update');
    $routes->delete('properties', 'Api\PropertiesController::delete');

    $routes->get('document-links', 'Api\DocumentLinksController::index');
    $routes->post('document-links', 'Api\DocumentLinksController::create');
    $routes->delete('document-links', 'Api\DocumentLinksController::delete');

    $routes->get('parent-meetings', 'Api\ParentMeetingsController::index');
    $routes->post('parent-meetings', 'Api\ParentMeetingsController::create');
    $routes->put('parent-meetings', 'Api\ParentMeetingsController::update');
    $routes->delete('parent-meetings', 'Api\ParentMeetingsController::delete');
});
