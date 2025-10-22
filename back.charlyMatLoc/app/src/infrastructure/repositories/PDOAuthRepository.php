<?php
declare(strict_types=1);

namespace charlymatloc\infra\repositories;

use charlymatloc\core\ports\spi\AuthRepositoryInterface;

class PDOAuthRepository implements AuthRepositoryInterface
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findUserByEmail(string $email): ?array
    {
        $sql = 'SELECT user_id, email, password_hash, role_code FROM users WHERE email = :email LIMIT 1';
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            return [
                'id' => (string)$row['user_id'],
                'email' => (string)$row['email'],
                'password' => (string)$row['password_hash'],
                'role' => (int)$row['role_code'],
            ];
        } catch (\Throwable $e) {
            error_log('PDOAuthRepository error: ' . $e->getMessage());
            return null;
        }
    }
}
