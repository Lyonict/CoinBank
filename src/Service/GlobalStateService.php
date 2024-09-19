<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;

class GlobalStateService
{
    private const LOCKDOWN_KEY = 'app_lockdown';

    public function __construct(private CacheInterface $cache)
    {
    }

    public function setLockdown(bool $lockdown): void
    {
        $this->cache->delete(self::LOCKDOWN_KEY);
        $this->cache->get(self::LOCKDOWN_KEY, function () use ($lockdown) {
            return $lockdown;
        });
    }

    public function isLockdown(): bool
    {
        return $this->cache->get(self::LOCKDOWN_KEY, function () {
            return false;
        });
    }
}