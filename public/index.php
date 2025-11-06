<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

$page = $_GET['page'] ?? 'login';

$protectedPages = ['dashboard', 'sto', 'scan'];

if (in_array($page, $protectedPages) && !isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

if ($page === 'login' && isset($_SESSION['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

switch ($page) {
    case 'login':
        require __DIR__ . '/../app/controllers/AuthController.php';
        break;
    case 'dashboard':
        require __DIR__ . '/../app/controllers/DashboardController.php';
        break;
    case 'master_sto':
        require __DIR__ . '/../app/controllers/MasterStoController.php';
        break;
    case 'gudang':
        require __DIR__ . '/../app/controllers/GudangController.php';
        break;
    case 'users':
        require __DIR__ . '/../app/controllers/UserController.php';
        break;
    case 'report':
        require __DIR__ . '/../app/controllers/ReportController.php';
        break;
    case 'report_generate':
        require __DIR__ . '/../app/controllers/ReportGenerateController.php';
        break;
    case 'report_update':
        require __DIR__ . '/../app/controllers/ReportUpdateController.php';
        break;
    case 'invoice_view_partial':
        require __DIR__ . '/../app/controllers/InvoiceViewController.php';
        break;
    case 'invoice_delete':
        require __DIR__ . '/../app/controllers/InvoiceDeleteController.php';
        break;
    case 'scan':
        require __DIR__.'/../app/controllers/ScanController.php';
        break;
    case 'logout':
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        header('Location: index.php?page=login');
        exit;
    default:
        if (isset($_SESSION['user_id'])) {
            require __DIR__ . '/../app/controllers/DashboardController.php';
        } else {
            require __DIR__ . '/../app/controllers/AuthController.php';
        }
        break;
}