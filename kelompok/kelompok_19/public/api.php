<?php
// public/api.php
// API Gateway: route endpoint REST ke Controller yang sesuai.
// Base URL: /api

require_once __DIR__ . "/../app/Config/Database.php";
require_once __DIR__ . "/../app/Controllers/LahanController.php";
require_once __DIR__ . "/../app/Controllers/SiklusController.php";
require_once __DIR__ . "/../app/Controllers/InventarisController.php";
require_once __DIR__ . "/../app/Controllers/PekerjaController.php";
require_once __DIR__ . "/../app/Controllers/PanenController.php";

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Hilangkan prefix /api
$uri = preg_replace('/^\/api/', '', $uri);

// Parse path segments
$segments = array_filter(explode('/', $uri), 'strlen');

// Routing sederhana: /api/lahan -> LahanController
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;

$controllers = [
    'lahan' => new LahanController(),
    'siklus' => new SiklusController(),
    'inventaris' => new InventarisController(),
    'pekerja' => new PekerjaController(),
    'panen' => new PanenController(),
];

if (!isset($controllers[$resource])) {
    http_response_code(404);
    \App\Config\Database::response(['error' => 'Endpoint tidak ditemukan']);
    exit;
}

$controller = $controllers[$resource];

// TODO: Implementasikan pemanggilan method sesuai HTTP method & path.
// Contoh struktur:
// GET    /api/lahan        -> $controller->index()
// GET    /api/lahan/{id}   -> $controller->show($id)
// POST   /api/lahan        -> $controller->create()
// PUT    /api/lahan/{id}   -> $controller->update($id)
// DELETE /api/lahan/{id}   -> $controller->delete($id)

switch ($method) {
    case 'GET':
        if ($id) {
            // TODO: $controller->show($id);
            $controller->show($id);
        } else {
            // TODO: $controller->index();
            $controller->index();
        }
        break;

    case 'POST':
        // TODO: $controller->create();
        $controller->create();
        break;

    case 'PUT':
        if ($id) {
            // TODO: $controller->update($id);
            $controller->update($id);
        } else {
            http_response_code(400);
            \App\Config\Database::response(['error' => 'ID diperlukan untuk update']);
        }
        break;

    case 'DELETE':
        if ($id) {
            // TODO: $controller->delete($id);
            $controller->delete($id);
        } else {
            http_response_code(400);
            \App\Config\Database::response(['error' => 'ID diperlukan untuk delete']);
        }
        break;

    default:
        http_response_code(405);
        \App\Config\Database::response(['error' => 'Method tidak diizinkan']);
        break;
}