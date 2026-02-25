<?php
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RateLimitMiddleware.php';
require_once __DIR__ . '/../controllers/Admin/AuthController.php';
require_once __DIR__ . '/../controllers/Admin/BookManager.php';
require_once __DIR__ . '/../controllers/Admin/AuthorManager.php';
require_once __DIR__ . '/../controllers/Admin/CategoryManager.php';
require_once __DIR__ . '/../controllers/Admin/UserManager.php';
require_once __DIR__ . '/../controllers/Admin/SettingsManager.php';
require_once __DIR__ . '/../controllers/Admin/StatsController.php';
require_once __DIR__ . '/../controllers/Admin/DevController.php';
require_once __DIR__ . '/../controllers/Admin/StorageController.php';
require_once __DIR__ . '/../controllers/Public/DownloadController.php';
require_once __DIR__ . '/../controllers/Public/BookController.php';
require_once __DIR__ . '/../controllers/Public/CategoryController.php';
require_once __DIR__ . '/../controllers/Public/AuthorController.php';
require_once __DIR__ . '/../controllers/Public/SettingsController.php';
require_once __DIR__ . '/../controllers/Public/SearchController.php';
$router->add('GET', '/api/ping', function () {
    Response::json(['status' => 'ok']);
});
$router->add('POST', '/api/admin/login', [new Admin\AuthController(), 'login'], [
    RateLimitMiddleware::perIpActionLimit('login_attempt', 10, 300)
]);
$router->add('GET', '/api/books/{id}/download', [new PublicC\DownloadController(), 'download'], [
    RateLimitMiddleware::perBookDownloads(5, 60)
]);
$router->add('GET', '/api/books', [new PublicC\BookController(), 'list']);
$router->add('GET', '/api/books/{id}', [new PublicC\BookController(), 'show']);
$router->add('GET', '/api/categories', [new PublicC\CategoryController(), 'list']);
$router->add('GET', '/api/authors', [new PublicC\AuthorController(), 'list']);
$router->add('GET', '/api/authors/{id}', [new PublicC\AuthorController(), 'show']);
$router->add('GET', '/api/settings', [new PublicC\SettingsController(), 'get']);
$router->add('GET', '/api/search', [new PublicC\SearchController(), 'search']);
$router->add('POST', '/api/admin/books', [new Admin\BookManager(), 'create'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('PUT', '/api/admin/books/{id}', [new Admin\BookManager(), 'update'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('DELETE', '/api/admin/books/{id}', [new Admin\BookManager(), 'delete'], [
    AuthMiddleware::requireRole(['admin'])
]);
$router->add('GET', '/api/admin/books/{id}', [new Admin\BookManager(), 'get'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('GET', '/api/admin/books', [new Admin\BookManager(), 'list'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('GET', '/api/admin/authors', [new Admin\AuthorManager(), 'list'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('POST', '/api/admin/authors', [new Admin\AuthorManager(), 'create'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('PUT', '/api/admin/authors/{id}', [new Admin\AuthorManager(), 'update'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('DELETE', '/api/admin/authors/{id}', [new Admin\AuthorManager(), 'delete'], [
    AuthMiddleware::requireRole(['admin'])
]);
$router->add('GET', '/api/admin/categories', [new Admin\CategoryManager(), 'list'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('GET', '/api/admin/subcategories', [new Admin\CategoryManager(), 'listSubcategories'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('POST', '/api/admin/categories', [new Admin\CategoryManager(), 'createCategory'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('PUT', '/api/admin/categories/{id}', [new Admin\CategoryManager(), 'updateCategory'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('DELETE', '/api/admin/categories/{id}', [new Admin\CategoryManager(), 'deleteCategory'], [
    AuthMiddleware::requireRole(['admin'])
]);
$router->add('POST', '/api/admin/subcategories', [new Admin\CategoryManager(), 'createSubcategory'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('PUT', '/api/admin/subcategories/{id}', [new Admin\CategoryManager(), 'updateSubcategory'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('DELETE', '/api/admin/subcategories/{id}', [new Admin\CategoryManager(), 'deleteSubcategory'], [
    AuthMiddleware::requireRole(['admin'])
]);
$router->add('GET', '/api/admin/users', [new Admin\UserManager(), 'list'], [
    AuthMiddleware::requireRole(['admin'])
]);
$router->add('POST', '/api/admin/users', [new Admin\UserManager(), 'create'], [
    AuthMiddleware::requireRole(['admin'])
]);
$router->add('PUT', '/api/admin/users/{id}', [new Admin\UserManager(), 'update'], [
    AuthMiddleware::requireRole(['admin'])
]);
$router->add('DELETE', '/api/admin/users/{id}', [new Admin\UserManager(), 'delete'], [
    AuthMiddleware::requireRole(['admin'])
]);
$router->add('GET', '/api/admin/settings', [new Admin\SettingsManager(), 'list'], [
    AuthMiddleware::requireRole(['admin'])
]);
$router->add('PUT', '/api/admin/settings', [new Admin\SettingsManager(), 'update'], [
    AuthMiddleware::requireRole(['admin'])
]);
$router->add('DELETE', '/api/admin/settings', [new Admin\SettingsManager(), 'delete'], [
    AuthMiddleware::requireRole(['admin'])
]);
$router->add('GET', '/api/admin/stats', [new Admin\StatsController(), 'summary'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('GET', '/api/admin/stats/activity', [new Admin\StatsController(), 'activity'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('GET', '/api/admin/stats/metrics', [new Admin\StatsController(), 'metrics'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('GET', '/api/admin/stats/overview', [new Admin\StatsController(), 'overview'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
// $router->add('GET', '/api/dev/reset-admin', [new Admin\DevController(), 'resetAdmin']);
$router->add('POST', '/api/admin/storage/upload', [new Admin\StorageController(), 'upload'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('DELETE', '/api/admin/storage', [new Admin\StorageController(), 'delete'], [
    AuthMiddleware::requireRole(['admin'])
]);
$router->add('GET', '/api/admin/storage/url', [new Admin\StorageController(), 'url'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
$router->add('GET', '/api/admin/storage/view', [new Admin\StorageController(), 'view'], [
    AuthMiddleware::requireRole(['admin', 'employee'])
]);
