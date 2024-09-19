<?php

namespace App\Tests\Twig;

use App\Service\GlobalStateService;
use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class AppExtentionTest extends TestCase
{
    /** @var GlobalStateService&\PHPUnit\Framework\MockObject\MockObject */
    private GlobalStateService $globalStateService;

    /** @var AppExtension&\PHPUnit\Framework\MockObject\MockObject */
    private AppExtension $appExtension;

    protected function setUp(): void
    {
        $this->globalStateService = $this->createMock(GlobalStateService::class);
        $this->appExtension = new AppExtension($this->globalStateService);
    }

    public function testGetFunctions(): void
    {
        $functions = $this->appExtension->getFunctions();

        $this->assertIsArray($functions);
        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertEquals('is_lockdown', $functions[0]->getName());
        $this->assertEquals([$this->appExtension, 'isLockdown'], $functions[0]->getCallable());
    }

    public function testIsLockdownReturnsTrue(): void
    {
        $this->globalStateService->expects($this->once())
            ->method('isLockdown')
            ->willReturn(true);

        $result = $this->appExtension->isLockdown();

        $this->assertTrue($result);
    }

    public function testIsLockdownReturnsFalse(): void
    {
        $this->globalStateService->expects($this->once())
            ->method('isLockdown')
            ->willReturn(false);

        $result = $this->appExtension->isLockdown();

        $this->assertFalse($result);
    }
}