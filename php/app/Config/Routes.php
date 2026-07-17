<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ── Auth ─────────────────────────────────────────────────────
$routes->match(['get', 'post'], '/', 'Auth::login');
$routes->match(['get', 'post'], 'login', 'Auth::login');
$routes->get('logout', 'Auth::logout');

// ── API auth (not behind authGuard — this is how a session is obtained) ──
$routes->post('api/auth/login', 'Api\AuthController::login');
$routes->post('api/auth/logout', 'Api\AuthController::logout');
$routes->get('api/auth/me', 'Api\AuthController::me');

// ── Pages (session required) ────────────────────────────────
$routes->group('', ['filter' => 'authGuard'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('teacher-dashboard', 'TeacherDashboard::index');
    $routes->get('secretary-dashboard', 'SecretaryDashboard::index');
    $routes->get('adas-dashboard', 'AdasDashboard::index');
    $routes->match(['get', 'post'], 'submit-documents', 'SubmitDocuments::index');
    $routes->match(['get', 'post'], 'documents', 'Documents::index');
    $routes->get('performance', 'Performance::index');
    $routes->get('enrollment-kpis', 'EnrollmentKpis::index');
    $routes->match(['get', 'post'], 'announcements', 'Announcements::index');
    $routes->match(['get', 'post'], 'parent-meetings', 'ParentMeetings::index');
    $routes->match(['get', 'post'], 'time-records', 'TimeRecords::index');
    $routes->match(['get', 'post'], 'deped-documents', 'DepedDocuments::index');
    $routes->match(['get', 'post'], 'document-links', 'DocumentLinks::index');
    $routes->match(['get', 'post'], 'property-management', 'Properties::index');
    $routes->match(['get', 'post'], 'users', 'Users::index');
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

    $routes->get('deped-documents', 'Api\DepedDocumentsController::index');
    $routes->post('deped-documents', 'Api\DepedDocumentsController::create');
    $routes->put('deped-documents', 'Api\DepedDocumentsController::update');
    $routes->delete('deped-documents', 'Api\DepedDocumentsController::delete');

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
