<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';

final class AuthController extends Controller
{
    public function home(array $params = []): string
    {
        return $this->redirect(isset($_SESSION['user_id']) ? url('dashboard') : url('login'));
    }

    public function showLogin(array $params = []): string
    {
        if (isset($_SESSION['user_id'])) {
            return $this->redirect(url('dashboard'));
        }

        return $this->render('auth/login', [
            'pageTitle' => 'Sign in',
            'bodyClass' => 'auth-body',
            'authMode' => 'login',
            'errors' => $this->pullErrors(),
            'old' => $this->pullOld(),
            'flashMessages' => $this->pullFlashMessages(),
        ]);
    }

    public function login(array $params = []): string
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return $this->redirect(url('login'));
        }

        if (isset($_SESSION['user_id'])) {
            return $this->redirect(url('dashboard'));
        }

        if (!$this->verifyCsrfToken($this->post('_token'))) {
            $this->flash('danger', 'The form expired. Please try again.');

            return $this->redirect(url('login'));
        }

        $email = strtolower(trim((string) $this->post('email', '')));
        $password = (string) $this->post('password', '');
        $errors = [];

        if ($email === '' || strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Enter a valid email address.';
        }

        if ($password === '' || strlen($password) > 72) {
            $errors[] = 'Enter your password.';
        }

        if ($errors !== []) {
            return $this->failAuthentication($errors, ['email' => $email], url('login'));
        }

        $userModel = new User();
        $user = $userModel->authenticate($email, $password);

        if (!is_array($user)) {
            return $this->failAuthentication(
                ['The email or password is incorrect.'],
                ['email' => $email],
                url('login')
            );
        }

        $this->establishSession($user);
        $this->flash('success', 'Welcome back to AuraLib.');

        return $this->redirect($this->homeForRole((string) $user['role']));
    }

    public function showRegister(array $params = []): string
    {
        if (isset($_SESSION['user_id'])) {
            return $this->redirect(url('dashboard'));
        }

        return $this->render('auth/login', [
            'pageTitle' => 'Create account',
            'bodyClass' => 'auth-body',
            'authMode' => 'register',
            'errors' => $this->pullErrors(),
            'old' => $this->pullOld(),
            'flashMessages' => $this->pullFlashMessages(),
        ]);
    }

    public function register(array $params = []): string
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return $this->redirect(url('register'));
        }

        if (isset($_SESSION['user_id'])) {
            return $this->redirect(url('dashboard'));
        }

        if (!$this->verifyCsrfToken($this->post('_token'))) {
            $this->flash('danger', 'The form expired. Please try again.');

            return $this->redirect(url('register'));
        }

        $name = trim((string) $this->post('name', ''));
        $email = strtolower(trim((string) $this->post('email', '')));
        $password = (string) $this->post('password', '');
        $passwordConfirmation = (string) $this->post('password_confirmation', '');
        $errors = $this->validateRegistration($name, $email, $password, $passwordConfirmation);

        if ($errors !== []) {
            return $this->failAuthentication(
                $errors,
                ['name' => $name, 'email' => $email],
                url('register')
            );
        }

        $userModel = new User();

        if ($userModel->emailExists($email)) {
            return $this->failAuthentication(
                ['An account with that email already exists.'],
                ['name' => $name, 'email' => $email],
                url('register')
            );
        }

        try {
            $userId = $userModel->createMember($name, $email, $userModel->hashPassword($password));
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return $this->failAuthentication(
                    ['An account with that email already exists.'],
                    ['name' => $name, 'email' => $email],
                    url('register')
                );
            }

            throw $exception;
        }

        $user = $userModel->findActiveById($userId);

        if (!is_array($user)) {
            throw new RuntimeException('The newly registered account could not be loaded.');
        }

        $this->establishSession($user);
        $this->flash('success', 'Your AuraLib membership is ready.');

        return $this->redirect($this->homeForRole((string) $user['role']));
    }

    public function dashboard(array $params = []): string
    {
        $role = (string) ($_SESSION['user_role'] ?? '');

        if (!isset($_SESSION['user_id']) || !in_array($role, ['admin', 'member'], true)) {
            return $this->redirect(url('login'));
        }

        return $this->redirect($this->homeForRole($role));
    }

    private function validateRegistration(string $name, string $email, string $password, string $confirmation): array
    {
        $errors = [];
        $nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);

        if ($nameLength < 2 || $nameLength > 120) {
            $errors[] = 'Name must be between 2 and 120 characters.';
        }

        if ($email === '' || strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Enter a valid email address.';
        }

        if (
            strlen($password) < 8
            || strlen($password) > 72
            || preg_match('/[a-z]/', $password) !== 1
            || preg_match('/[A-Z]/', $password) !== 1
            || preg_match('/\d/', $password) !== 1
        ) {
            $errors[] = 'Password must be 8–72 characters and include upper, lower, and numeric characters.';
        }

        if (!hash_equals($password, $confirmation)) {
            $errors[] = 'Password confirmation does not match.';
        }

        return $errors;
    }

    private function establishSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = (string) $user['role'];
        $_SESSION['user_status'] = (string) $user['status'];
        $_SESSION['authenticated_at'] = time();
    }

    private function failAuthentication(array $errors, array $old, string $redirectPath): string
    {
        $_SESSION['auth_errors'] = $errors;
        $_SESSION['auth_old'] = $old;

        return $this->redirect($redirectPath);
    }

    private function pullErrors(): array
    {
        $errors = isset($_SESSION['auth_errors']) && is_array($_SESSION['auth_errors'])
            ? $_SESSION['auth_errors']
            : [];
        unset($_SESSION['auth_errors']);

        return $errors;
    }

    private function pullOld(): array
    {
        $old = isset($_SESSION['auth_old']) && is_array($_SESSION['auth_old'])
            ? $_SESSION['auth_old']
            : [];
        unset($_SESSION['auth_old']);

        return $old;
    }

}
