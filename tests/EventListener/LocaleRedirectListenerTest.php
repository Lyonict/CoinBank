<?php

namespace App\Tests\EventListener;

use App\EventListener\LocaleRedirectListener;
use App\Service\LocaleService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class LocaleRedirectListenerTest extends TestCase
{
    /** @var RouterInterface&\PHPUnit\Framework\MockObject\MockObject */
    private RouterInterface $router;

    /** @var LocaleService&\PHPUnit\Framework\MockObject\MockObject */
    private LocaleService $localeService;

    /** @var LocaleRedirectListener&\PHPUnit\Framework\MockObject\MockObject */
    private LocaleRedirectListener $listener;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->localeService = $this->createMock(LocaleService::class);
        $this->listener = new LocaleRedirectListener($this->router, $this->localeService);
    }

    public function testOnKernelRequestWithLocale(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/en/some-path']);
        $event = $this->createRequestEvent($request);

        $this->listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
        // We check that the locale is correctly extracted from the URL
        $this->assertEquals('en', $request->getLocale());
    }

    public function testOnKernelRequestWithoutLocale(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/some-path']);
        $event = $this->createRequestEvent($request);

        $this->localeService->expects($this->once())
            ->method('getPreferredLocale')
            ->willReturn('fr');

        $this->listener->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertTrue($response->isRedirect('/fr/some-path'));
        $this->assertEquals('fr', $request->attributes->get('preferred-locale'));
    }

    public function testOnKernelRequestWithStaticResource(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/assets/image.jpg']);
        $event = $this->createRequestEvent($request);

        $this->listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
        $this->assertFalse($request->attributes->has('preferred-locale'));
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        /** @var HttpKernelInterface&\PHPUnit\Framework\MockObject\MockObject */
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}