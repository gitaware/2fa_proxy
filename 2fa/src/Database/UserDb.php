<?php

namespace App\Database;

class UserDb extends JsonDb
{
    protected function getDefaultData(): array
    {
        return ['users' => []];
    }

    public function getUserByEmail(string $email): ?array
    {
        foreach ($this->data['users'] as $user) {
            if (strcasecmp($user['email'], $email) === 0) {
                return $user;
            }
        }
        return null;
    }

    public function addUser(string $name, string $email, string $secret): bool
    {
        if ($this->getUserByEmail($email) !== null) {
            return false;
        }

        $this->data['users'][] = [
            'id'     => $this->generateNewId(),
            'name'   => $name,
            'email'  => $email,
            'secret' => $secret,
        ];

        $this->save();
        return true;
    }

    public function deleteUserByEmail(string $email): bool
    {
        foreach ($this->data['users'] as $index => $user) {
            if (strcasecmp($user['email'], $email) === 0) {
                array_splice($this->data['users'], $index, 1);
                $this->save();
                return true;
            }
        }

        return false;
    }

    public function updateSecret(string $email, string $secret): bool
    {
        foreach ($this->data['users'] as &$user) {
            if (strcasecmp($user['email'], $email) === 0) {
                $user['secret'] = $secret;
                $this->save();
                return true;
            }
        }

        return false;
    }

    public function getAllUsers(): array
    {
        return $this->data['users'];
    }

    public function isAdmin(string $email): bool
    {
        $user = $this->getUserByEmail($email);
        return $user && !empty($user['isadmin']);
    }

    private function generateNewId(): int
    {
        $maxId = 0;

        foreach ($this->data['users'] as $user) {
            if (isset($user['id']) && $user['id'] > $maxId) {
                $maxId = $user['id'];
            }
        }

        return $maxId + 1;
    }

    public function toggleAdminStatus(string $email): bool
    {
        foreach ($this->data['users'] as &$user) {
            if (strcasecmp($user['email'], $email) === 0) {
                $user['isadmin'] = !($user['isadmin'] ?? false);
                $this->save();
                return true;
            }
        }

        return false;
    }

    public function updateUser(string $email, array $updateData): bool
    {
        foreach ($this->data['users'] as &$user) {
            if (strcasecmp($user['email'], $email) === 0) {
                foreach ($updateData as $key => $value) {
                    $user[$key] = $value;
                }
                $this->save();
                return true;
            }
        }

        return false;
    }
}

