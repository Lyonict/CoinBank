<?php

namespace App\Tests\Service;

use App\Entity\Cryptocurrency;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\CryptocurrencyRepository;
use App\Service\CryptoFormService;
use App\Service\CryptoTransactionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

class CryptoFormServiceTest extends TestCase
{
    /** @var CryptocurrencyRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $cryptocurrencyRepository;

    /** @var CryptoTransactionService&\PHPUnit\Framework\MockObject\MockObject */
    private $cryptoTransactionService;

    /** @var CryptoFormService&\PHPUnit\Framework\MockObject\MockObject */
    private $cryptoFormService;

    protected function setUp(): void
    {
        $this->cryptocurrencyRepository = $this->createMock(CryptocurrencyRepository::class);
        $this->cryptoTransactionService = $this->createMock(CryptoTransactionService::class);

        $this->cryptoFormService = new CryptoFormService(
            $this->cryptocurrencyRepository,
            $this->cryptoTransactionService
        );
    }

    public function testHandleCryptoSelectionWithValidCrypto()
    {
        $crypto = 'bitcoin';
        $cryptocurrency = new Cryptocurrency();

        /** @var FormInterface&\PHPUnit\Framework\MockObject\MockObject */
        $form = $this->createMock(FormInterface::class);
        $cryptoField = $this->createMock(FormInterface::class);

        $form->expects($this->once())
            ->method('get')
            ->with('cryptocurrency')
            ->willReturn($cryptoField);

        $this->cryptocurrencyRepository->expects($this->once())
            ->method('findOneByCoingeckoId')
            ->with($crypto)
            ->willReturn($cryptocurrency);

        $cryptoField->expects($this->once())
            ->method('setData')
            ->with($cryptocurrency);

        $this->cryptoFormService->handleCryptoSelection($form, $crypto);
    }

    public function testHandleCryptoSelectionWithInvalidCrypto()
    {
        $crypto = 'invalid_crypto';

        /** @var FormInterface&\PHPUnit\Framework\MockObject\MockObject */
        $form = $this->createMock(FormInterface::class);

        $this->cryptocurrencyRepository->expects($this->once())
            ->method('findOneByCoingeckoId')
            ->with($crypto)
            ->willReturn(null);

        $form->expects($this->never())
            ->method('get');

        $this->cryptoFormService->handleCryptoSelection($form, $crypto);
    }

    public function testHandleCryptoSelectionWithNullCrypto()
    {
        /** @var FormInterface&\PHPUnit\Framework\MockObject\MockObject */
        $form = $this->createMock(FormInterface::class);

        $form->expects($this->never())
            ->method('get');

        $this->cryptocurrencyRepository->expects($this->never())
            ->method('findOneByCoingeckoId');

        $this->cryptoFormService->handleCryptoSelection($form, null);
    }

    public function testProcessFormWithValidSubmission()
    {
        /** @var FormInterface&\PHPUnit\Framework\MockObject\MockObject */
        $form = $this->createMock(FormInterface::class);
        $user = new User();
        $transaction = new Transaction();

        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $form->expects($this->once())
            ->method('getData')
            ->willReturn($transaction);

        $this->cryptoTransactionService->expects($this->once())
            ->method('createTransaction')
            ->with($transaction, $user);

        $result = $this->cryptoFormService->processForm($form, $user);

        $this->assertTrue($result);
    }

    public function testProcessFormWithInvalidSubmission()
    {
        /** @var FormInterface&\PHPUnit\Framework\MockObject\MockObject */
        $form = $this->createMock(FormInterface::class);
        $user = new User();

        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->cryptoTransactionService->expects($this->never())
            ->method('createTransaction');

        $result = $this->cryptoFormService->processForm($form, $user);

        $this->assertFalse($result);
    }

    public function testProcessFormWithException()
    {
        /** @var FormInterface&\PHPUnit\Framework\MockObject\MockObject */
        $form = $this->createMock(FormInterface::class);
        $user = new User();
        $transaction = new Transaction();

        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $form->expects($this->once())
            ->method('getData')
            ->willReturn($transaction);

        $this->cryptoTransactionService->expects($this->once())
            ->method('createTransaction')
            ->with($transaction, $user)
            ->willThrowException(new \Exception('Test exception'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->cryptoFormService->processForm($form, $user);
    }
}