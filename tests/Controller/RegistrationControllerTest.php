<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class RegistrationControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
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

        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testRegister()
    {
        $crawler = $this->client->request('GET', '/en/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Register');

        $form = $crawler->selectButton('Send')->form([
            'registration_form[email]' => 'test@example.com',
            'registration_form[username]' => 'testuser',
            'registration_form[plainPassword][first]' => 'TestPassword123!',
            'registration_form[plainPassword][second]' => 'TestPassword123!',
            'registration_form[sponsorCode]' => '',
        ]);

        $this->client->submit($form);

        // Follow redirects
        do {
            $this->client->followRedirect();
        } while ($this->client->getResponse()->isRedirect());

        // Assert that we've eventually landed on the user's dashboard (and are therefore logged in)
        $this->assertRouteSame('app_user_dashboard');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
        $this->assertEquals(1000.0, $user->getBank());
        $this->assertEquals('en', $user->getPreferedLocale());
    }

    public function testRedirectToDashboardIfUserLoggedIn()
    {
        // Create a test user
        $user = new User();
        $user->setEmail('logged_in_user@example.com');
        $user->setUsername('loggedinuser');
        $user->setPassword('$2y$13$hK7tHM0pWASNuRGM7Tn4Oe3Ue8P6kCjKmxwXj8Yl6Eo8.qoK9Fluy');
        $user->setRoles(['ROLE_USER']);
        $user->setPreferedLocale('en');
        $user->setSponsorCode(Uuid::v4());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Log in the user
        $this->client->loginUser($user);

        // Try to access the registration page
        $this->client->request('GET', '/en/register');

        // Assert that we're redirected to the dashboard
        $this->assertResponseRedirects('/en/user');

        // Follow the redirect
        $this->client->followRedirect();
    }
}