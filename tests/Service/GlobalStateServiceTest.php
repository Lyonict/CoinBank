<?php

namespace App\Tests\Service;

use App\Service\GlobalStateService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class GlobalStateServiceTest extends TestCase
{
    private GlobalStateService $globalStateService;

    /** @var CacheInterface&\PHPUnit\Framework\MockObject\MockObject */
    private CacheInterface $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->globalStateService = new GlobalStateService($this->cache);
    }

    public function testSetLockdown(): void
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with(GlobalStateService::LOCKDOWN_KEY);

        $this->cache->expects($this->once())
            ->method('get')
            ->with(GlobalStateService::LOCKDOWN_KEY, $this->isType('callable'))
            ->willReturnCallback(function ($key, $callback) {
                return $callback();
            });

        $this->globalStateService->setLockdown(true);
    }

    public function testIsLockdownTrue(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with(GlobalStateService::LOCKDOWN_KEY, $this->isType('callable'))
            ->willReturn(true);

        $result = $this->globalStateService->isLockdown();

        $this->assertTrue($result);
    }

    public function testIsLockdownFalse(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with(GlobalStateService::LOCKDOWN_KEY, $this->isType('callable'))
            ->willReturn(false);

        $result = $this->globalStateService->isLockdown();

        $this->assertFalse($result);
    }

    public function testIsLockdownDefaultFalse(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with(GlobalStateService::LOCKDOWN_KEY, $this->isType('callable'))
            ->willReturnCallback(function ($key, $callback) {
                return $callback();
            });

        $result = $this->globalStateService->isLockdown();

        $this->assertFalse($result);
    }
}