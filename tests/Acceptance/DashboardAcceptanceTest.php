<?php

namespace App\Tests\Acceptance;

class DashboardAcceptanceTest extends AcceptanceTestCase
{
    public function testDashboardLoadsForAuthenticatedUser(): void
    {
        $ref = $this->seedUserAndCompany('dashuser', 'Dash Company', 'Inner Sphere');
        $companyId = $ref['companyId'];

        // Seed some data for the dashboard
        $this->seedPilot($companyId, 'Pilot One', true);
        $this->seedUnit($companyId, 'Mech One', 'Gravino GRV-NI1', 35, 150, 'mech');
        $this->seedContract($companyId, ['name' => 'Test Contract', 'planet' => 'Tharkad']);
        $this->seedSupportPoints($companyId, 500, 'Funding');

        $client = $this->login('dashuser');
        $crawler = $client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertContainsText($crawler, 'Dash Company');
    }
}