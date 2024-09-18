<?php

namespace App\Tests;

use App\Entity\Cryptocurrency;
use App\Entity\Transaction;
use App\Entity\User;
use App\Service\CoinGeckoService;
use App\Service\CryptoTransactionService;
use App\Service\UserBankService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\CssSelector\XPath\TranslatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;
use Twig\Environment;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

abstract class BaseWebTestCase extends WebTestCase
{
    use ResetDatabase;
    use Factories;
    protected $client;
    protected $entityManager;
    protected $userRepository;
    protected $transactionRepository;
    protected $defaultTestEmail;
    protected $coinGeckoService;
    protected $mockCoinGeckoService;
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->transactionRepository = $this->entityManager->getRepository(Transaction::class);
        $this->defaultTestEmail = "test@example.com";

        // Create and set up the mock CoinGeckoService
        $this->mockCoinGeckoService = $this->mockCoinGeckoService();
        $this->client->getContainer()->set(CoinGeckoService::class, $this->mockCoinGeckoService);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Delete the test user if it exists
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        if ($user) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }

        // Roll back transaction
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }

        $this->entityManager->close();
        $this->entityManager = null;
    }

    protected function createTestUser(array $data = []): User
    {
        $user = new User();
        $user->setEmail($data['email'] ?? $this->defaultTestEmail);
        $user->setUsername($data['username'] ?? 'testuser');
        $user->setPassword($data['password'] ?? '$2y$13$hK7Xq0qXNSPyZZzUgfLW3.QOi0lJRQQVtbFk.4wO1eNgxKLGvv7Oi');
        $user->setPreferedLocale($data['locale'] ?? 'en');
        $user->setSponsorCode($data['sponsorCode'] ?? Uuid::v4());
        $user->setBank($data['bank'] ?? 1000);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function createTestCryptocurrency(array $data = []): Cryptocurrency
    {
        $crypto = new Cryptocurrency();
        $crypto->setName($data['name'] ?? 'Bitcoin');
        $crypto->setCoingeckoId($data['coingeckoId'] ?? 'bitcoin');
        $crypto->setSymbol($data['symbol'] ?? 'BTC');

        $this->entityManager->persist($crypto);
        $this->entityManager->flush();

        return $crypto;
    }

    protected function assertUserProperties(array $expectedProperties, ?string $email = null): void
    {
        $user = $this->userRepository->findOneByEmail($email ?? $this->defaultTestEmail);
        $this->assertNotNull($user);
        foreach ($expectedProperties as $property => $value) {
            $getter = 'get' . ucfirst($property);
            $this->assertEquals($value, $user->$getter());
        }
    }

    protected function submitForm(
        string $url,
        string $buttonText,
        array $formData): void
    {
        $crawler = $this->client->request('GET', $url);
        $form = $crawler->selectButton($buttonText)->form();
        foreach ($formData as $key => $value) {
            $form[$key] = $value;
        }
        $this->client->submit($form);
    }

    protected function submitFormAndAssertRedirect(
        string $url,
        string $buttonText,
        array $formData,
        string $redirectRoute,
        array $redirectParams = []): void
    {
        $crawler = $this->client->request('GET', $url);
        $form = $crawler->selectButton($buttonText)->form();
        foreach ($formData as $key => $value) {
            $form[$key] = $value;
        }
        $this->client->submit($form);
        $this->assertResponseRedirectsTo($redirectRoute, $redirectParams);
    }

    protected function assertResponseRedirectsTo(string $route, array $parameters = []): void
    {
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertRouteSame($route, $parameters);
    }

    // Sometime, multiple redirect happen one after the other. In those cases, we want to check where we eventually end up
    protected function assertResponseRedirectsEventuallyTo(string $expectedRouteName, array $parameters = []): void
    {
        do {
            $this->client->followRedirect();
        } while ($this->client->getResponse()->isRedirect());

        $this->assertRouteSame($expectedRouteName, $parameters);
    }

    protected function getContainerMock(
        $security = null,
        $formFactory = null,
        $translator = null,
        $userBankService = null): ContainerInterface
    {
         /** @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturnCallback(function ($template, $params) {
            return json_encode($params);
        });

        $flashBag = $this->createMock(FlashBagInterface::class);

        $session = $this->createMock(FlashBagAwareSessionInterface::class);
        $session->method('getFlashBag')->willReturn($flashBag);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('/mocked-url');

        $container->method('get')
            ->willReturnCallback(function ($id) use ($security, $formFactory, $translator, $userBankService, $twig, $requestStack, $router) {
                switch ($id) {
                    case 'security.helper':
                        return $security ?? $this->createMock(Security::class);
                    case 'form.factory':
                        return $formFactory ?? $this->createMock(FormFactoryInterface::class);
                    case 'translator':
                        return $translator ?? $this->createMock(TranslatorInterface::class);
                    case UserBankService::class:
                        return $userBankService ?? $this->createMock(UserBankService::class);
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

    protected function mockCoinGeckoService(): CoinGeckoService|MockObject
    {
        $mockCoinGeckoService = $this->createMock(CoinGeckoService::class);

        $mockPrices = [
            'bitcoin' => 30000.00,
            'ethereum' => 2000.00,
            'cardano' => 0.50,
        ];

        $mockCoinGeckoService->method('getAllCryptoCurrentPrice')
            ->willReturn($mockPrices);

        $mockCoinGeckoService->method('getCryptoCurrentPrice')
            ->willReturnCallback(function ($coingeckoId) use ($mockPrices) {
                return $mockPrices[$coingeckoId] ?? null;
            });

        return $mockCoinGeckoService;
    }
}