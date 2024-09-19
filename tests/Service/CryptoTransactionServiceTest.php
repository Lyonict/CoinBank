<?php

namespace App\Tests\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\Cryptocurrency;
use App\Enum\TransactionType;
use App\Repository\TransactionRepository;
use App\Service\CryptoTransactionService;
use App\Service\CoinGeckoService;
use App\Service\GlobalStateService;
use App\Service\UserBankService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class CryptoTransactionServiceTest extends TestCase
{
    /** @var EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var TransactionRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $transactionRepository;

    /** @var CoinGeckoService&\PHPUnit\Framework\MockObject\MockObject */
    private $coinGeckoService;

    /** @var UserBankService&\PHPUnit\Framework\MockObject\MockObject */
    private $userBankService;

    /** @var TranslatorInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var GlobalStateService&\PHPUnit\Framework\MockObject\MockObject */
    private $globalStateService;

    private CryptoTransactionService $cryptoTransactionService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->transactionRepository = $this->createMock(TransactionRepository::class);
        $this->coinGeckoService = $this->createMock(CoinGeckoService::class);
        $this->userBankService = $this->createMock(UserBankService::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->globalStateService = $this->createMock(GlobalStateService::class);
        $this->cryptoTransactionService = new CryptoTransactionService(
            $this->entityManager,
            $this->transactionRepository,
            $this->coinGeckoService,
            $this->userBankService,
            $this->translator,
            $this->globalStateService
        );
    }

    public function testCreateTransactionBuy()
    {
        $user = new User();
        $cryptocurrency = new Cryptocurrency();
        $cryptocurrency->setCoingeckoId('bitcoin');

        $transaction = new Transaction();
        $transaction->setCryptocurrency($cryptocurrency);
        $transaction->setCryptoAmount(1);
        $transaction->setTransactionType(TransactionType::BUY);

        $this->coinGeckoService->expects($this->once())
            ->method('getAllCryptoCurrentPrice')
            ->willReturn(['bitcoin' => 50000]);

        $this->userBankService->expects($this->once())
            ->method('processCryptoBuy')
            ->with($user, 50000);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($transaction);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->cryptoTransactionService->createTransaction($transaction, $user);

        $this->assertEquals($user, $transaction->getUser());
        $this->assertInstanceOf(\DateTimeImmutable::class, $transaction->getDate());
        $this->assertEquals(50000, $transaction->getDollarAmount());
    }

    public function testCreateTransactionSell()
    {
        $user = new User();
        $cryptocurrency = new Cryptocurrency();
        $cryptocurrency->setCoingeckoId('bitcoin');

        $transaction = new Transaction();
        $transaction->setCryptocurrency($cryptocurrency);
        $transaction->setCryptoAmount(1);
        $transaction->setTransactionType(TransactionType::SELL);

        $this->coinGeckoService->expects($this->once())
            ->method('getAllCryptoCurrentPrice')
            ->willReturn(['bitcoin' => 50000]);

        $this->userBankService->expects($this->once())
            ->method('processCryptoSell')
            ->with($user, $cryptocurrency, 1, 50000);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($transaction);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->cryptoTransactionService->createTransaction($transaction, $user);

        $this->assertEquals($user, $transaction->getUser());
        $this->assertInstanceOf(\DateTimeImmutable::class, $transaction->getDate());
        $this->assertEquals(50000, $transaction->getDollarAmount());
    }

    public function testGetCryptoBalances()
    {
        $user = new User();
        $cryptosData = [
            ['coingecko_id' => 'bitcoin', 'cryptoBalance' => 1, 'dollarBalance' => 40000],
            ['coingecko_id' => 'ethereum', 'cryptoBalance' => 10, 'dollarBalance' => 30000],
        ];

        $this->transactionRepository->expects($this->once())
            ->method('getCryptosOfUserWithBalance')
            ->with($user)
            ->willReturn($cryptosData);

        $this->coinGeckoService->expects($this->once())
            ->method('getAllCryptoCurrentPrice')
            ->willReturn(['bitcoin' => 50000, 'ethereum' => 3000]);

        $result = $this->cryptoTransactionService->getCryptoBalances($user);

        $this->assertCount(2, $result);
        $this->assertEquals(50000, $result[0]['currentPrice']);
        $this->assertEquals(3000, $result[1]['currentPrice']);
        $this->assertEquals(25, $result[0]['profitPercentage']);
        $this->assertEquals(0, $result[1]['profitPercentage']);
        $this->assertEquals('50.00k', $result[0]['formattedCurrentValue']);
        $this->assertEquals('30.00k', $result[1]['formattedCurrentValue']);
    }

    public function testGetSingleCryptoBalanceThousands()
    {
        $user = new User();
        $coingeckoId = 'bitcoin';
        $cryptoData = ['coingecko_id' => 'bitcoin', 'cryptoBalance' => 1, 'dollarBalance' => 40000];

        $this->transactionRepository->expects($this->once())
            ->method('getCryptoBalanceForUserAndCrypto')
            ->with($user, $coingeckoId)
            ->willReturn($cryptoData);

        $this->coinGeckoService->expects($this->once())
            ->method('getCryptoCurrentPrice')
            ->with($coingeckoId)
            ->willReturn(50000);

        $result = $this->cryptoTransactionService->getSingleCryptoBalance($user, $coingeckoId);

        $this->assertNotNull($result);
        $this->assertEquals(50000, $result['currentPrice']);
        $this->assertEquals(25, $result['profitPercentage']);
        $this->assertEquals('50.00k', $result['formattedCurrentValue']);
    }

    public function testGetSingleCryptoBalanceMillions()
    {
        $user = new User();
        $coingeckoId = 'bitcoin';
        $cryptoData = ['coingecko_id' => 'bitcoin', 'cryptoBalance' => 100, 'dollarBalance' => 4000000];

        $this->transactionRepository->expects($this->once())
            ->method('getCryptoBalanceForUserAndCrypto')
            ->with($user, $coingeckoId)
            ->willReturn($cryptoData);

        $this->coinGeckoService->expects($this->once())
            ->method('getCryptoCurrentPrice')
            ->with($coingeckoId)
            ->willReturn(50000);

        $result = $this->cryptoTransactionService->getSingleCryptoBalance($user, $coingeckoId);

        $this->assertNotNull($result);
        $this->assertEquals(50000, $result['currentPrice']);
        $this->assertEquals(25, $result['profitPercentage']);
        $this->assertEquals('5.00M', $result['formattedCurrentValue']);
    }

    public function testGetSingleCryptoBalanceLessThanThousand()
    {
        $user = new User();
        $coingeckoId = 'some-cheap-coin';
        $cryptoData = ['coingecko_id' => 'some-cheap-coin', 'cryptoBalance' => 100, 'dollarBalance' => 800];

        $this->transactionRepository->expects($this->once())
            ->method('getCryptoBalanceForUserAndCrypto')
            ->with($user, $coingeckoId)
            ->willReturn($cryptoData);

        $this->coinGeckoService->expects($this->once())
            ->method('getCryptoCurrentPrice')
            ->with($coingeckoId)
            ->willReturn(9);

        $result = $this->cryptoTransactionService->getSingleCryptoBalance($user, $coingeckoId);

        $this->assertNotNull($result);
        $this->assertEquals(9, $result['currentPrice']);
        $this->assertEquals(12.5, $result['profitPercentage']);
        $this->assertEquals('900.00', $result['formattedCurrentValue']);
    }

    public function testGetSingleCryptoBalanceNotFound()
    {
        $user = new User();
        $coingeckoId = 'nonexistent';

        $this->transactionRepository->expects($this->once())
            ->method('getCryptoBalanceForUserAndCrypto')
            ->with($user, $coingeckoId)
            ->willReturn(null);

        $result = $this->cryptoTransactionService->getSingleCryptoBalance($user, $coingeckoId);

        $this->assertNull($result);
    }
}