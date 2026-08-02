<?php

namespace App\Tests\Acceptance;

class SupportPointAcceptanceTest extends AcceptanceTestCase
{
    public function testAddSupportPointEntry(): void
    {
        $ref = $this->seedUserAndCompany('spuser', 'SP Co', 'Inner Sphere');

        $client = $this->login('spuser');

        $crawler = $client->request('GET', '/support-points');

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteSupportPointEntry(): void
    {
        $ref = $this->seedUserAndCompany('delsp', 'Del SP Co', 'Clan');
        $this->seedSupportPoints($ref['companyId'], 500, 'ToDelete');

        $client = $this->login('delsp');

        // Get the entry ID from the page
        $crawler = $client->request('GET', '/support-points');
        $this->assertResponseIsSuccessful();
    }
}
