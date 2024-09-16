<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\LocaleService;
use App\Service\RegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

class RegistrationServiceTest extends TestCase
{
    /** @var EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private EntityManagerInterface $entityManager;

    /** @var UserPasswordHasherInterface&\PHPUnit\Framework\MockObject\MockObject */
    private UserPasswordHasherInterface $userPasswordHasher;

    /** @var UserRepository&\PHPUnit\Framework\MockObject\MockObject */
    private UserRepository $userRepository;

    /** @var LocaleService&\PHPUnit\Framework\MockObject\MockObject */
    private LocaleService $localeService;

    /** @var RegistrationService&\PHPUnit\Framework\MockObject\MockObject */
    private RegistrationService $registrationService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userPasswordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->localeService = $this->createMock(LocaleService::class);

        $this->registrationService = new RegistrationService(
            $this->entityManager,
            $this->userPasswordHasher,
            $this->userRepository,
            $this->localeService
        );
    }

    public function testRegisterUser(): void
    {
        $generatedSponsorCode = Uuid::v4();
        $user = new User();
        $formData = [
            'plainPassword' => 'password123',
            'sponsorCode' => $generatedSponsorCode,
        ];
        $request = new Request();

        $this->userPasswordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->localeService->expects($this->once())
            ->method('getPreferredLocale')
            ->willReturn('en');

        $this->userRepository->expects($this->once())
            ->method('findOneBySponsorCode')
            ->with($generatedSponsorCode)
            ->willReturn(new User());

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $registeredUser = $this->registrationService->registerUser($user, $formData, $request);

        $this->assertInstanceOf(User::class, $registeredUser);
        $this->assertEquals('hashed_password', $registeredUser->getPassword());
        // As we retrieve the sponsor code as a string, we can't check the type
        $this->assertTrue(Uuid::isValid($registeredUser->getSponsorCode()), 'Sponsor code is not a valid UUID');
        $this->assertEquals(1000.0, $registeredUser->getBank());
        $this->assertEquals('en', $registeredUser->getPreferedLocale());
        $this->assertInstanceOf(User::class, $registeredUser->getSponsor());
    }
}