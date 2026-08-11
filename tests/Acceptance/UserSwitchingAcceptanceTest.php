<?php

namespace App\Tests\Acceptance;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class UserSwitchingAcceptanceTest extends AcceptanceTestCase
{
    public function testSwitchUserDropdownVisibleForAdmin(): void
    {
        // Create an admin user with a username that gets ROLE_ALLOWED_TO_SWITCH
        $adminRef = $this->seedUserAndCompany('Andrea', 'Admin Co', 'Inner Sphere');
        $this->seedUnit($adminRef['companyId'], 'Patrol Mech', 'Patrol', 45, 200, 'mech');
        $this->seedPilot($adminRef['companyId'], 'Pilot One', true);

        // Create a second user to switch to
        $targetRef = $this->seedUserAndCompany('targetuser', 'Target Co', 'Clan');
        $this->seedUnit($targetRef['companyId'], 'Target Mech', 'Target', 55, 300, 'mech');
        $this->seedPilot($targetRef['companyId'], 'Target Pilot', true);

        // Log in as admin (Andrea gets ROLE_ALLOWED_TO_SWITCH automatically)
        $client = $this->login('Andrea');

        // Request the roster page (or any page with the navbar)
        $crawler = $client->request('GET', '/roster');

        $this->assertResponseIsSuccessful();

        // Check that the switch user dropdown exists in the response
        $html = $crawler->filter('body')->html();
        $this->assertStringContainsString('Switch user...', $html, 'Switch user dropdown should be visible for admin');

        // Verify the target user appears in the dropdown options
        $this->assertStringContainsString('targetuser', $html, 'Target user should appear in the switch dropdown');
    }

    public function testSwitchUserDropdownNotVisibleForRegularUser(): void
    {
        // Create a regular user (without ROLE_ALLOWED_TO_SWITCH)
        $regularRef = $this->seedUserAndCompany('regularuser', 'Regular Co', 'Inner Sphere');
        $this->seedUnit($regularRef['companyId'], 'Regular Mech', 'Regular', 50, 250, 'mech');

        // Log in as regular user
        $client = $this->login('regularuser');

        // Request the roster page
        $crawler = $client->request('GET', '/roster');

        $this->assertResponseIsSuccessful();

        // Check that the switch user dropdown does NOT exist
        $html = $crawler->filter('body')->html();
        $this->assertStringNotContainsString('Switch user...', $html, 'Switch user dropdown should NOT be visible for regular users');
    }

    public function testSwitchUserToAnotherUser(): void
    {
        // Create admin user with a username that gets ROLE_ALLOWED_TO_SWITCH
        $adminRef = $this->seedUserAndCompany('Zidahya', 'Admin Co 2', 'Inner Sphere');
        $this->seedUnit($adminRef['companyId'], 'Admin Mech', 'Admin', 50, 250, 'mech');
        $this->seedPilot($adminRef['companyId'], 'Admin Pilot', true);
        $this->seedSupportPoints($adminRef['companyId'], 1000, 'Funding');

        // Create target user
        $targetRef = $this->seedUserAndCompany('targetuser2', 'Target Co 2', 'Clan');
        $targetMechName = 'Target Mech 2';
        $this->seedUnit($targetRef['companyId'], $targetMechName, 'Target', 60, 350, 'mech');
        $this->seedPilot($targetRef['companyId'], 'Target Pilot 2', true);
        $this->seedSupportPoints($targetRef['companyId'], 500, 'Funding');

        // Log in as admin (Zidahya gets ROLE_ALLOWED_TO_SWITCH automatically)
        $client = $this->login('Zidahya');

        // Request the roster page
        $crawler = $client->request('GET', '/roster');

        $this->assertResponseIsSuccessful();

        // Verify we see the admin's unit
        $html = $crawler->filter('body')->html();
        $this->assertStringContainsString('Admin Mech', $html, 'Should see admin\'s mech before switching');
        $this->assertStringNotContainsString($targetMechName, $html, 'Should NOT see target\'s mech before switching');

        // Now switch to the target user by submitting the switch form
        $client->request('GET', '/roster?_switch_user=targetuser2');

        // Follow the redirect to get the actual rendered page
        $crawler = $client->followRedirect();

        // After switching, we should see the target user's data

        $html = $crawler->filter('body')->html();
        $this->assertStringContainsString($targetMechName, $html, 'Should see target mech after switching');
        $this->assertStringNotContainsString('Admin Mech', $html, 'Should NOT see admin\'s mech after switching');

        // Verify we're in impersonation mode (should see "Impersonating: targetuser2")
        $this->assertStringContainsString('Impersonating: targetuser2', $html, 'Should show impersonation banner');

        // Verify the "Stop Impersonating" link exists
        $this->assertStringContainsString('_switch_user=_exit', $html, 'Should have stop impersonating link');
    }

    public function testStopImpersonatingReturnsToAdmin(): void
    {
        // Create admin user with a username that gets ROLE_ALLOWED_TO_SWITCH
        $adminRef = $this->seedUserAndCompany('MitchellWelsh', 'Admin Co 3', 'Inner Sphere');
        $adminMechName = 'Admin Mech 3';
        $this->seedUnit($adminRef['companyId'], $adminMechName, 'Admin', 50, 250, 'mech');
        $this->seedPilot($adminRef['companyId'], 'Admin Pilot 3', true);

        // Create target user
        $targetRef = $this->seedUserAndCompany('targetuser3', 'Target Co 3', 'Clan');
        $targetMechName = 'Target Mech 3';
        $this->seedUnit($targetRef['companyId'], $targetMechName, 'Target', 60, 350, 'mech');
        $this->seedPilot($targetRef['companyId'], 'Target Pilot 3', true);

        // Log in as admin (MitchellWelsh gets ROLE_ALLOWED_TO_SWITCH automatically)
        $client = $this->login('MitchellWelsh');

        // Switch to target user
        $client->request('GET', '/roster?_switch_user=targetuser3');

        // Follow the redirect to get the actual rendered page
        $crawler = $client->followRedirect();

        $html = $crawler->filter('body')->html();
        $this->assertStringContainsString($targetMechName, $html, 'Should see target mech after switching');

        // Now stop impersonating by following the _exit link
        $client->request('GET', '/roster?_switch_user=_exit');

        // Follow the redirect since Symfony redirects after exiting impersonation
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $html = $crawler->filter('body')->html();
        $this->assertStringContainsString($adminMechName, $html, 'Should see admin mech after stopping impersonation');
        $this->assertStringNotContainsString('Impersonating:', $html, 'Should NOT show impersonation banner after exiting');
        $this->assertStringNotContainsString('_exit', $html, 'Should NOT have _exit parameter');
    }
}
