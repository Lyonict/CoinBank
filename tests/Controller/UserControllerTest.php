<?php

namespace App\Tests\Controller;

use App\Controller\UserController;
use Symfony\Component\Form\FormFactoryInterface;
use App\Entity\User;
use App\Enum\TransactionType;
use App\Repository\TransactionRepository;
use App\Service\CoinGeckoService;
use App\Service\CryptoTransactionService;
use App\Service\UserBankService;
use App\Tests\BaseWebTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserControllerTest extends BaseWebTestCase
{
    public function testBankAction(): void
    {
        // Test access to bank page
        $testUser = $this->createTestUser();
        $this->client->loginUser($testUser);

        $this->submitForm(
            '/en/user/bank',
            'Proceed',
            [
                'bank_form[amount]' => '100',
                'bank_form[bankTransactionMode]' => 'deposit'
            ],
        );

        $this->assertResponseRedirectsTo('app_user_bank');
        $this->assertUserProperties(['bank'=>1100.0]);
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

    public function testProfileAction(): void
    {
        $testUser = $this->createTestUser();
        $this->client->loginUser($testUser);

        // Make a request to the profile page
        $crawler = $this->client->request('GET', '/en/user/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Personnal Profile');

        $this->submitForm(
            '/en/user/profile',
            'Confirm',
            [
                'profile_form[email]' => 'new@example.com',
                'profile_form[username]' => 'new_username',
                'profile_form[preferedLocale]' => 'fr'
            ],
        );

        $this->assertResponseRedirectsEventuallyTo('app_user_profile', ['_locale' => 'fr']);

        $this->assertUserProperties(['username'=>'new_username', 'preferedLocale'=>'fr'], 'new@example.com');
    }

    public function testCryptoFormAction(): void
    {
        $testUser = $this->createTestUser(['bank' => 100000]);
        $this->client->loginUser($testUser);
        $testCrypto = $this->createTestCryptocurrency();

        // Mock the CoinGeckoService
        $mockCoinGeckoService = $this->mockCoinGeckoService();

        // Log the getAllCryptoCurrentPrice method of the mock CoinGeckoService
        $allCryptoPrices = $mockCoinGeckoService->getAllCryptoCurrentPrice();
        echo "Mock CoinGeckoService getAllCryptoCurrentPrice result:\n";
        print_r($allCryptoPrices);

        $crawler = $this->client->request('GET', '/en/user/crypto-form?crypto=' . $testCrypto->getCoingeckoId());

        $this->assertResponseIsSuccessful();

        $this->submitForm(
            '/en/user/crypto-form?crypto=' . $testCrypto->getCoingeckoId(),
            'Proceed',
            [
                'crypto_transaction_form[cryptocurrency]' => $testCrypto->getCoingeckoId(),
                'crypto_transaction_form[cryptoAmount]' => '1',
                'crypto_transaction_form[transactionType]' => 'buy'
            ],
        );

        $transaction = $this->transactionRepository->findOneByUser($testUser);

        if ($transaction) {
            echo "Transaction found: " . $transaction->getId() . "\n";
        } else {
            echo "No transaction found\n";
        }

        $this->assertNotNull($transaction, 'Transaction was not created');
    }
}