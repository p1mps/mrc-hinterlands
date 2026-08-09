<?php

namespace App\Tests\Acceptance;

class UserSwitchAcceptanceTest extends AcceptanceTestCase
{
    /**
     * Happy path: Admin switches to a regular user, verifies impersonation UI, then exits.
     */
    public function testUserSwitchHappyPath(): void
    {
        // Seed admin (Andrea has ROLE_ALLOWED_TO_SWITCH) and a regular user
        // seedUserAndCompany handles the single-client pattern correctly
        $this->seedUserAndCompany('Andrea', 'Andrea Co', 'Inner Sphere');
        $this->seedUserAndCompany('regularuser', 'Regular Co', 'Inner Sphere');

        // Log in as admin
        $client = $this->login('Andrea');

        // 1. Visit dashboard — the switcher dropdown should be visible
        $client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful('Dashboard should load successfully for admin');

        // The navbar form for user switching should exist
        $switcherForm = $client->getCrawler()->filter('form.d-inline');
        $this->assertGreaterThan(0, $switcherForm->count(), 'User switcher form should be visible in navbar');

        // 2. Simulate selecting "regularuser" from the dropdown
        // The form submits via JS with _switch_user=regularuser
        $client->request('GET', '/dashboard', ['_switch_user' => 'regularuser']);

        // Debug: dump the response to see what's happening
        // echo $client->getResponse()->getContent(); die();

        // 3. Verify impersonation banner is shown
        $crawler = $client->getCrawler();

        $impersonatingBanner = $crawler->filter('span.nav-link.text-warning');
        $this->assertGreaterThan(0, $impersonatingBanner->count(), 'Impersonation banner should be visible');
        $this->assertStringContainsString(
            'Impersonating: regularuser',
            $impersonatingBanner->first()->text(),
            'Banner should display "Impersonating: regularuser"'
        );

        // 4. Verify the URL contains _switch_user parameter
        $requestUrl = $client->getRequest()->getUri();
        $this->assertStringContainsString(
            '_switch_user=regularuser',
            $requestUrl,
            'URL should contain _switch_user parameter'
        );

        // 5. Exit impersonation
        $client->request('GET', '/dashboard', ['_switch_user' => '_exit']);

        // 6. Verify we're back to admin — no impersonation banner
        $crawler = $client->getCrawler();

        $impersonatingBanner = $crawler->filter('span.nav-link.text-warning');
        $this->assertEquals(0, $impersonatingBanner->count(), 'Impersonation banner should be gone after exiting');

        // 7. Verify the URL no longer contains _switch_user
        $requestUrl = $client->getRequest()->getUri();
        $this->assertStringNotContainsString(
            '_switch_user',
            $requestUrl,
            'URL should no longer contain _switch_user after exiting impersonation'
        );
    }
}
