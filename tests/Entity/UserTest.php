<?php

namespace App\Entity\Tests;

use App\Entity\Transaction;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testDefaultValues(): void
    {
        $newUser = new User();
        $this->assertEquals(['ROLE_USER'], $newUser->getRoles());
        $this->assertNull($newUser->getBank());
        $this->assertNull($newUser->getPreferedLocale());
        $this->assertNull($newUser->getSponsorCode());
        $this->assertNull($newUser->getSponsor());
        $this->assertEmpty($newUser->getSponsoredUsers());
        $this->assertFalse($newUser->getIsFrozen());
    }

    public function testToString(): void
    {
        $this->assertEquals('', $this->user->__toString());

        $email = 'test@example.com';
        $this->user->setEmail($email);
        $this->assertEquals($email, $this->user->__toString());
    }

    public function testEmail(): void
    {
        $email = 'testemail@gmail.com';
        $this->user->setEmail($email);
        $this->assertEquals($email, $this->user->getEmail());
        $this->assertEquals($email, $this->user->getUserIdentifier());
    }

    public function testUsername(): void
    {
        $username = 'SomeUserName';
        $this->user->setUsername($username);
        $this->assertEquals($username, $this->user->getUsername());
    }

    public function testRoles(): void
    {
        $this->assertEquals(['ROLE_USER'], $this->user->getRoles());

        $roles = ['ROLE_ADMIN'];
        $this->user->setRoles($roles);
        $this->assertEquals(['ROLE_ADMIN'], $this->user->getRoles());
    }

    public function testPassword(): void
    {
        $password = 'somePassword';
        $this->user->setPassword($password);
        $this->assertEquals($password, $this->user->getPassword());
    }

    public function testBank(): void
    {
        $this->assertNull($this->user->getBank());

        $bankAmount = 1000.50;
        $this->user->setBank($bankAmount);
        $this->assertEquals($bankAmount, $this->user->getBank());
    }

    public function testPreferedLocale(): void
    {
        $preferedLocale = "en";
        $this->user->setPreferedLocale($preferedLocale);
        $this->assertEquals($preferedLocale, $this->user->getPreferedLocale());
    }

    public function testSponsorCode(): void
    {
        $sponsorCode = Uuid::v4();
        $this->user->setSponsorCode($sponsorCode);
        $this->assertEquals($sponsorCode, $this->user->getSponsorCode());
    }

    public function testSponsor(): void
    {
        $sponsor = new User;
        $this->user->setSponsor($sponsor);
        $this->assertEquals($sponsor, $this->user->getSponsor());
    }

    public function testSponsoredUsers(): void
    {
        // Create sponsored users
        $sponsoredUser1 = new User();
        $sponsoredUser2 = new User();
        // Initially, the sponsored users array should be empty
        $this->assertEmpty($this->user->getSponsoredUsers());
        // Add sponsored users
        $this->user->addSponsoree($sponsoredUser1);
        $this->user->addSponsoree($sponsoredUser2);
        // Check if the sponsored users were added correctly
        $sponsoredUsers = $this->user->getSponsoredUsers();
        $this->assertCount(2, $sponsoredUsers);
        $this->assertContains($sponsoredUser1, $sponsoredUsers);
        $this->assertContains($sponsoredUser2, $sponsoredUsers);
            // Test removing a sponsored user
        $this->user->removeSponsoree($sponsoredUser1);
        $this->assertCount(1, $this->user->getSponsoredUsers());
        $this->assertNotContains($sponsoredUser1, $this->user->getSponsoredUsers());
        $this->assertContains($sponsoredUser2, $this->user->getSponsoredUsers());
    }

    public function testEraseCredentials(): void
    {
        $password = 'somePassword';
        $this->user->setPassword($password);
        $this->user->eraseCredentials();
        // After erasing credentials, the password should remain unchanged
        $this->assertSame($password, $this->user->getPassword());
    }

    public function testTransactions(): void
    {
        $transaction1 = new Transaction();
        $transaction2 = new Transaction();

        // Initially, the transactions collection should be empty
        $this->assertEmpty($this->user->getTransactions());

        // Add transactions
        $this->user->addTransaction($transaction1);
        $this->user->addTransaction($transaction2);

        // Check if the transactions were added correctly
        $transactions = $this->user->getTransactions();
        $this->assertCount(2, $transactions);
        $this->assertContains($transaction1, $transactions);
        $this->assertContains($transaction2, $transactions);

        // Test removing a transaction
        $this->user->removeTransaction($transaction1);
        $this->assertCount(1, $this->user->getTransactions());
        $this->assertNotContains($transaction1, $this->user->getTransactions());
        $this->assertContains($transaction2, $this->user->getTransactions());

        // Test that adding the same transaction twice doesn't duplicate it
        $this->user->addTransaction($transaction2);
        $this->assertCount(1, $this->user->getTransactions());
    }

    public function testIsFrozen(): void
    {
        $this->assertFalse($this->user->getIsFrozen());

        $this->user->setIsFrozen(true);
        $this->assertTrue($this->user->getIsFrozen());

        $this->user->setIsFrozen(false);
        $this->assertFalse($this->user->getIsFrozen());
    }
}
