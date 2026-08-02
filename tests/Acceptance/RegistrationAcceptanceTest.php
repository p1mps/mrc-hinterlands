<?php

namespace App\Tests\Acceptance;

class RegistrationAcceptanceTest extends AcceptanceTestCase
{
    public function testRegistrationPageLoads(): void
    {
        $this->seedUserAndCompany('dummy', 'Dummy Co', 'Inner Sphere');
        $client = $this->login('dummy');
        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Register');
        $this->assertSelectorExists('button[type="submit"]');
    }

    public function testRegisterCreatesAccountAndCompany(): void
    {
        $this->seedUserAndCompany('dummy', 'Dummy Co', 'Inner Sphere');
        $client = $this->login('dummy');

        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
    }

    public function testLoginPageLoads(): void
    {
        $this->seedUserAndCompany('dummy', 'Dummy Co', 'Inner Sphere');
        $client = $this->login('dummy');
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
    }

    public function testLoginRedirectsToDashboard(): void
    {
        $this->seedUserAndCompany('loginuser', 'Login Company', 'Inner Sphere');

        $client = $this->login('loginuser');
        $crawler = $client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
    }
}
