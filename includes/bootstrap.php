<?php
require_once __DIR__ . '/../config/db.php';

const SESSION_TIMEOUT_SECONDS = 1800;

if (session_status() !== PHP_SESSION_ACTIVE) {
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_start();
}

if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > SESSION_TIMEOUT_SECONDS) {
    session_unset();
    session_destroy();
    session_start();
    set_flash('warning', 'Session expired. Please login again.');
}
$_SESSION['last_activity'] = time();

function app_base_path(): string
{
    static $basePath = null;
    if ($basePath !== null) {
        return $basePath;
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (preg_match('#^(.*)/(auth|admin|seller|user|includes|config|assets|uploads|database)/#', $scriptName, $matches)) {
        $basePath = rtrim($matches[1], '/');
    } else {
        $basePath = '';
    }

    return $basePath;
}

function base_url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    return app_base_path() . $path;
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function current_user(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function redirect(string $to): void
{
    header('Location: ' . base_url($to));
    exit;
}

function require_login(?string $role = null): void
{
    $user = current_user();
    if (!$user) {
        set_flash('danger', 'Please login first.');
        redirect('/auth/login.php');
    }

    if ($role && $user['role'] !== $role) {
        http_response_code(403);
        exit('Forbidden');
    }

    if (($user['status'] ?? 'active') !== 'active') {
        session_destroy();
        redirect('/auth/login.php');
    }
}

function paginate(int $total, int $perPage = 10): array
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $pages = (int) ceil($total / $perPage);
    $page = min($page, max($pages, 1));
    $offset = ($page - 1) * $perPage;
    return [$page, $pages, $offset, $perPage];
}
