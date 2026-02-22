<?php
namespace Admin;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/JWTHandler.php';
require_once __DIR__ . '/../../utils/Logger.php';
require_once __DIR__ . '/../../utils/Validator.php';
class AuthController extends \Controller {
    public function login(): void {
        $email = $this->request['body']['email'] ?? '';
        $password = $this->request['body']['password'] ?? '';
        $reqErrors = \Validator::require(['email' => $email, 'password' => $password], ['email', 'password']);
        if ($reqErrors || !\Validator::email($email)) {
            \Response::json(['error' => 'Invalid input'], 400);
            return;
        }
        $model = new class extends \Model {
            public function findUser(string $email): ?array {
                $stmt = $this->db->prepare("SELECT id, full_name, email, password, role, is_active FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $row = $stmt->fetch();
                return $row ?: null;
            }
            public function updateLogin(int $id): void {
                $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$id]);
            }
        };
        $user = $model->findUser($email);
        if (!$user || !password_verify($password, $user['password'])) {
            \Response::json(['error' => 'Invalid credentials'], 401);
            return;
        }
        if (!(int)$user['is_active']) {
            \Response::json(['error' => 'User inactive'], 403);
            return;
        }
        $model->updateLogin((int)$user['id']);
        \Logger::adminLog((int)$user['id'], 'login', null, null, null);
        $token = \JWTHandler::encode(['sub' => (int)$user['id'], 'name' => $user['full_name'], 'role' => $user['role']]);
        \Response::json(['token' => $token, 'role' => $user['role'], 'name' => $user['full_name']]);
    }
}
