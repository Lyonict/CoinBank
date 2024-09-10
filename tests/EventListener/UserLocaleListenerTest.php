<?php

namespace App\Tests\EventListener;

use App\Entity\User;
use App\EventListener\UserLocaleListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserLocaleListenerTest extends TestCase
{
    /** @var Security&\PHPUnit\Framework\MockObject\MockObject */
    private Security $security;

    /** @var RouterInterface&\PHPUnit\Framework\MockObject\MockObject */
    private RouterInterface $router;

    /** @var UserLocaleListener&\PHPUnit\Framework\MockObject\MockObject */
    private UserLocaleListener $listener;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->listener = new UserLocaleListener($this->security, $this->router);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = UserLocaleListener::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertArrayHasKey(SecurityEvents::INTERACTIVE_LOGIN, $events);
    }

    public function testOnKernelRequestWithNoUser(): void
    {
        $request = new Request();
        $event = $this->createRequestEvent($request);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestNotMainRequest(): void
{
    $request = new Request();
    /** @var HttpKernelInterface&\PHPUnit\Framework\MockObject\MockObject */
    $kernel = $this->createMock(HttpKernelInterface::class);
    $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

    $this->security->expects($this->never())
        ->method('getUser');

    $this->listener->onKernelRequest($event);

    $this->assertNull($event->getResponse());
}

    public function testOnKernelRequestWithUserAndDifferentLocale(): void
    {
        $request = new Request([], [], ['_route' => 'some_route', '_route_params' => []]);
        $request->setLocale('en');
        $event = $this->createRequestEvent($request);

        $user = $this->createMock(User::class);
        $user->method('getPreferedLocale')->willReturn('fr');

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('some_route', ['_locale' => 'fr'])
            ->willReturn('/fr/some-route');

        $this->listener->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        /** @var RedirectResponse $response */
        $this->assertEquals('/fr/some-route', $response->getTargetUrl());
    }

    public function testOnInteractiveLogin(): void
    {
        $request = new Request();
        /** @var UsernamePasswordToken&\PHPUnit\Framework\MockObject\MockObject */
        $token = $this->createMock(UsernamePasswordToken::class);
        $event = new InteractiveLoginEvent($request, $token);

        $user = $this->createMock(User::class);
        $user->method('getPreferedLocale')->willReturn('de');

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->listener->onInteractiveLogin($event);

        $this->assertEquals('de', $request->getLocale());
    }

    public function testOnInteractiveLoginWithNonUserObject(): void
    {
        $request = new Request();
        /** @var UsernamePasswordToken&\PHPUnit\Framework\MockObject\MockObject */
        $token = $this->createMock(UsernamePasswordToken::class);
        $event = new InteractiveLoginEvent($request, $token);

        $nonUserObject = $this->createMock(UserInterface::class);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($nonUserObject);

        $initialLocale = $request->getLocale();

        $this->listener->onInteractiveLogin($event);

        $this->assertEquals($initialLocale, $request->getLocale(), 'Locale should not change for non-User objects');
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        /** @var HttpKernelInterface&\PHPUnit\Framework\MockObject\MockObject */
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}