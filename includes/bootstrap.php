<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function base_url(string $path = ''): string
{
    return $path;
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
    header('Location: ' . $to);
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
