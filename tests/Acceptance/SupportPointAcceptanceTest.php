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

        $conn = self::$sharedEm->getConnection();
        $entryId = (int) $conn->lastInsertId();
        $this->assertGreaterThan(0, $entryId, 'Support point entry should have been created');

        $client = $this->login('delsp');

        $client->request('POST', '/support-points/' . $entryId . '/delete');
        $this->assertResponseRedirects('/support-points');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }
}
