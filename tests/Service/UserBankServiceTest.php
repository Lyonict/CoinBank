<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserBankService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserBankServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private TranslatorInterface $translator;
    private UserBankService $userBankService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->userBankService = new UserBankService($this->entityManager, $this->translator);
    }

    /**
     * @dataProvider bankOperationProvider
     */
    public function testUpdateUserBank(int $initialBalance, int $amount, string $mode, int $expectedBalance): void
    {
        $user = new User();
        $user->setBank($initialBalance);

        $this->userBankService->updateUserBank($user, $amount, $mode);

        $this->assertEquals($expectedBalance, $user->getBank());
    }

    public function bankOperationProvider(): array
    {
        return [
            'deposit' => [100, 50, 'deposit', 150],
            'withdraw' => [100, 50, 'withdraw', 50],
        ];
    }

    /**
     * @dataProvider invalidOperationProvider
     */
    public function testUpdateUserBankInvalidOperations(int $initialBalance, int $amount, string $mode): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = new User();
        $user->setBank($initialBalance);

        $this->userBankService->updateUserBank($user, $amount, $mode);
    }

    public function invalidOperationProvider(): array
    {
        return [
            'negative amount' => [100, -50, 'deposit'],
            'invalid mode' => [100, 50, 'invalid_mode'],
            'overdraw' => [100, 150, 'withdraw'],
        ];
    }

    public function testDepositMoreThan100000ThrowsException(): void
{
    $user = new User();
    $user->setBank(50000);

    $this->expectException(\InvalidArgumentException::class);

    $this->userBankService->updateUserBank($user, 51000, 'deposit');
}
}