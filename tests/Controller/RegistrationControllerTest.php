<?php

namespace App\Tests\Controller;

use App\Tests\BaseWebTestCase;

class RegistrationControllerTest extends BaseWebTestCase
{
    public function testRegister()
    {
        $crawler = $this->client->request('GET', '/en/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Register');

        $this->submitForm(
            '/en/register',
            'Send',
            [
                'registration_form[email]' => 'test@example.com',
                'registration_form[username]' => 'testuser',
                'registration_form[plainPassword][first]' => 'TestPassword123!',
                'registration_form[plainPassword][second]' => 'TestPassword123!',
                'registration_form[sponsorCode]' => '',
            ],
        );

        $this->assertResponseRedirectsEventuallyTo('app_user_dashboard');

        $this->assertUserProperties(
            [
                'username'=>'testuser',
                'roles'=>['ROLE_USER'],
                'bank'=>1000.0,
                'preferedLocale'=>'en',
            ],
            'test@example.com');
    }

    public function testRedirectToDashboardIfUserLoggedIn()
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);
        $this->client->request('GET', '/en/register');
        $this->assertResponseRedirectsTo('app_user_dashboard');
    }
}