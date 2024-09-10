<?php

namespace App\Tests\Controller;

use App\Controller\SecurityController;
use App\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Twig\Environment;

class SecurityControllerTest extends TestCase
{
    /** @var SecurityController&\PHPUnit\Framework\MockObject\MockObject */
    private SecurityController $controller;

    /** @var AuthenticationUtils&\PHPUnit\Framework\MockObject\MockObject */
    private AuthenticationUtils $authenticationUtils;

    /** @var RouterInterface&\PHPUnit\Framework\MockObject\MockObject */
    private RouterInterface $router;

    /** @var TokenStorageInterface&\PHPUnit\Framework\MockObject\MockObject */
    private TokenStorageInterface $tokenStorage;

    /** @var Environment&\PHPUnit\Framework\MockObject\MockObject */
    private Environment $twig;

    protected function setUp(): void
    {
        $this->authenticationUtils = $this->createMock(AuthenticationUtils::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->twig = $this->createMock(Environment::class);

        $this->controller = new SecurityController();
        $this->controller->setContainer($this->getContainerMock());
    }

    public function testLoginWithAuthenticatedUser(): void
    {
        $user = $this->createMock(User::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('app_user_dashboard')
            ->willReturn('/en/user');

        $response = $this->controller->login($this->authenticationUtils);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isRedirect('/en/user'));
    }

    public function testLoginWithoutAuthenticatedUser(): void
    {
        $this->tokenStorage->method('getToken')->willReturn(null);

        $this->authenticationUtils->method('getLastAuthenticationError')
            ->willReturn(null);
        $this->authenticationUtils->method('getLastUsername')
            ->willReturn('test_user');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('security/login.html.twig', $this->anything())
            ->willReturn('Rendered login template');

        $response = $this->controller->login($this->authenticationUtils);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Rendered login template', $response->getContent());
    }

    public function testLoginPageRender(): void
    {
        $this->tokenStorage->method('getToken')->willReturn(null);

        $this->authenticationUtils->method('getLastAuthenticationError')
            ->willReturn(null);
        $this->authenticationUtils->method('getLastUsername')
            ->willReturn('test_user');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('security/login.html.twig', [
                'last_username' => 'test_user',
                'error' => null,
            ])
            ->willReturn('Rendered login template');

        $response = $this->controller->login($this->authenticationUtils);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Rendered login template', $response->getContent());
    }

    public function testLoginPageRenderWithError(): void
    {
        $this->tokenStorage->method('getToken')->willReturn(null);

        $error = new AuthenticationException('Invalid credentials');

        $this->authenticationUtils->method('getLastAuthenticationError')
            ->willReturn($error);
        $this->authenticationUtils->method('getLastUsername')
            ->willReturn('failed_user');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('security/login.html.twig', $this->callback(function ($params) {
                return $params['last_username'] === 'failed_user'
                    && $params['error'] instanceof AuthenticationException
                    && $params['error']->getMessage() === 'Invalid credentials';
            }))
            ->willReturn('Rendered login template with error');

        $response = $this->controller->login($this->authenticationUtils);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Rendered login template with error', $response->getContent());
    }

    public function testLogout(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This method can be blank - it will be intercepted by the logout key on your firewall.');

        $this->controller->logout();
    }

    /**
     * @return ContainerInterface&MockObject
     */
    private function getContainerMock(): ContainerInterface|MockObject
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->method('get')
            ->willReturnCallback(function ($id) {
                switch ($id) {
                    case 'router':
                        return $this->router;
                    case 'security.token_storage':
                        return $this->tokenStorage;
                    case 'twig':
                        return $this->twig;
                    default:
                        return null;
                }
            });

        $container->method('has')
            ->willReturnCallback(function ($id) {
                return in_array($id, ['router', 'security.token_storage', 'twig']);
            });

        return $container;
    }
}