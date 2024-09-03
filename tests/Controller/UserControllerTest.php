<?php

namespace App\Tests\Controller;

use App\Controller\UserController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use App\Entity\User;
use App\Service\UserBankService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class UserControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private $client;
    private $entityManager;
    private $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->userRepository = $this->entityManager->getRepository(User::class);
    }

    protected function tearDown(): void
    {
        // Roll back transaction
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
        parent::tearDown();
    }

    public function testBankAction(): void
    {
        // Create a test user
        $testUser = new User();
        $testUser->setEmail('test@example.com');
        $testUser->setUsername('testuser');
        $testUser->setPassword('$2y$13$hK7Xq0qXNSPyZZzUgfLW3.QOi0lJRQQVtbFk.4wO1eNgxKLGvv7Oi'); // hashed password
        $testUser->setPreferedLocale('en');
        $testUser->setSponsorCode(Uuid::v4());
        $testUser->setBank(1000); // Initial balance

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($testUser);
        $entityManager->flush();

        // Log in the user
        $this->client->loginUser($testUser);

        // Make a request to the bank page
        $crawler = $this->client->request('GET', '/en/user/bank');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Bank Transaction');

        // Test form submission
        $form = $crawler->selectButton('Proceed')->form();
        $form['bank_form[amount]'] = '100';
        $form['bank_form[bankTransactionMode]'] = 'deposit';

        $this->client->submit($form);

        $this->assertResponseRedirects('/en/user/bank');
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert.alert-success');

        // Verify that the user's balance has been updated
        $updatedUser = $this->userRepository->findOneByEmail('test@example.com');
        $this->assertEquals(1100.0, $updatedUser->getBank());
    }

    public function testBankActionThrowsLogicExceptionWhenUserNotFound(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You must be logged in to access this page.');

        /** @var Security&MockObject $security */
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        /** @var FormFactoryInterface&MockObject $formFactory */
        $formFactory = $this->createMock(FormFactoryInterface::class);

        /** @var TranslatorInterface&MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);

        $controller = new UserController($translator, $security);
        $controller->setContainer($this->getContainerMock($security, $formFactory));

        /** @var UserBankService&MockObject $userBankService */
        $userBankService = $this->createMock(UserBankService::class);

        $request = new Request();
        $controller->bank($request, $userBankService);
    }

    public function testBankActionHandlesInvalidFormSubmission(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getBank')->willReturn(1000.0);

        /** @var Security&MockObject $security */
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        /** @var TranslatorInterface&MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);

        $controller = new UserController($translator, $security);
        $controller->setContainer($this->getContainerMock($security, $formFactory, $translator));

        /** @var UserBankService&MockObject $userBankService */
        $userBankService = $this->createMock(UserBankService::class);

        $request = new Request();
        $response = $controller->bank($request, $userBankService);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testBankActionHandlesExceptionDuringTransaction(): void
    {
        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getBank')->willReturn(1000.0);

        /** @var Security&MockObject $security */
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        /** @var FormInterface&MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn(['amount' => 100, 'bankTransactionMode' => 'deposit']);

        /** @var FormFactoryInterface&MockObject $formFactory */
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        /** @var UserBankService&MockObject $userBankService */
        $userBankService = $this->createMock(UserBankService::class);
        $userBankService->method('updateUserBank')
            ->willThrowException(new \Exception('Transaction failed'));

        /** @var TranslatorInterface&MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('An error occurred');

        $controller = new UserController($translator, $security);
        $controller->setContainer($this->getContainerMock($security, $formFactory, $translator, $userBankService));

        $request = new Request();
        $response = $controller->bank($request, $userBankService);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
    * @return ContainerInterface&MockObject
    */
    private function getContainerMock($security, $formFactory = null, $translator = null, $userBankService = null): ContainerInterface
    {
        /** @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);

        /** @var Environment&MockObject $twig */
        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturnCallback(function ($template, $params) {
            return json_encode($params);
        });

        /** @var FlashBagInterface&MockObject $flashBag */
        $flashBag = $this->createMock(FlashBagInterface::class);

        /** @var FlashBagAwareSessionInterface&MockObject $session */
        $session = $this->createMock(FlashBagAwareSessionInterface::class);
        $session->method('getFlashBag')->willReturn($flashBag);

        /** @var RequestStack&MockObject $requestStack */
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        /** @var RouterInterface&MockObject $router */
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('/mocked-url');

        $container->method('get')
            ->willReturnCallback(function ($id) use ($security, $formFactory, $translator, $userBankService, $twig, $requestStack, $router) {
                switch ($id) {
                    case 'security.helper':
                        return $security;
                    case 'form.factory':
                        return $formFactory;
                    case 'translator':
                        return $translator;
                    case UserBankService::class:
                        return $userBankService;
                    case 'twig':
                        return $twig;
                    case 'request_stack':
                        return $requestStack;
                    case 'router':
                        return $router;
                    default:
                        return null;
                }
            });

        $container->method('has')
            ->willReturnCallback(function ($id) {
                return in_array($id, ['security.helper', 'form.factory', 'translator', UserBankService::class, 'twig', 'request_stack', 'router']);
            });

        return $container;
    }
}