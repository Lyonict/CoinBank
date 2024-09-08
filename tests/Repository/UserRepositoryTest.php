<?php

namespace App\Tests\Repository;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepositoryTest extends KernelTestCase
{
    private $entityManager;
    private $userRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->userRepository = $this->entityManager->getRepository(User::class);
    }

    public function testFindBySponsorCode(): void
    {
        // Create a test user with a sponsor code
        // Create a test user
        $testUser = new User();
        $testUser->setEmail('test2@example.com');
        $testUser->setUsername('test2user');
        $testUser->setPassword('$2y$13$hK7Xq0qXNSPyZZzUgfLW3.QOi0lJRQQVtbFk.4wO1eNgxKLGvv7Oi'); // hashed password
        $testUser->setPreferedLocale('en');
        $testUser->setBank(1000); // Initial balance
        $testUser->setSponsorCode('TEST123');

        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        // Test finding the user by sponsor code
        $foundUser = $this->userRepository->findBySponsorCode('TEST123');
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals('TEST123', $foundUser->getSponsorCode());

        // Test with non-existent sponsor code
        $nonExistentUser = $this->userRepository->findBySponsorCode('NONEXISTENT');
        $this->assertNull($nonExistentUser);
    }

    public function testUpgradePassword(): void
    {
        // Create a test user
        $testUser = new User();
        $testUser->setEmail('test3@example.com');
        $testUser->setUsername('test3user');
        $testUser->setPassword('old_hashed_password');
        $testUser->setPreferedLocale('en');
        $testUser->setBank(1000);
        $testUser->setSponsorCode('TEST1234');

        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        // Get the user's ID
        $userId = $testUser->getId();

        // New hashed password
        $newHashedPassword = 'new_hashed_password';

        // Upgrade the password
        $this->userRepository->upgradePassword($testUser, $newHashedPassword);

        // Clear the entity manager to ensure we're getting a fresh instance from the database
        $this->entityManager->clear();

        // Fetch the user from the database
        $updatedUser = $this->userRepository->find($userId);

        // Assert that the password has been updated
        $this->assertEquals($newHashedPassword, $updatedUser->getPassword());
    }

    public function testUpgradePasswordThrowsExceptionForUnsupportedUser(): void
    {
        // Create a mock user that implements PasswordAuthenticatedUserInterface
        // but is not an instance of your User class
        $unsupportedUser = $this->createMock(PasswordAuthenticatedUserInterface::class);

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Instances of "' . get_class($unsupportedUser) . '" are not supported.');

        // Attempt to upgrade password for unsupported user
        $this->userRepository->upgradePassword($unsupportedUser, 'new_hashed_password');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up the database
        $this->entityManager->close();
        $this->entityManager = null;
    }
}