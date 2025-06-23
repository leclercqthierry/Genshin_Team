<?php
declare (strict_types = 1);

namespace GenshinTeam\Session;

class SessionManager
{
    public function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function clear(): void
    {
        $_SESSION = [];
    }

    public function destroy(): void
    {
        if ($this->isStarted()) {
            session_destroy();
        }
    }
}
