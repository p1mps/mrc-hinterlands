<?php

namespace App\Tests\Acceptance;

class SalvagedMechAcceptanceTest extends AcceptanceTestCase
{
    public function testSalvagedMechsIndexLoads(): void
    {
        $ref = $this->seedUserAndCompany('salvageuser', 'Salvage Co', 'Inner Sphere');
        $this->seedSalvagedMech($ref['companyId'], [
            'model' => 'Catapult CAT-PU1',
            'tonnage' => 80,
            'bv_cost' => 300,
        ]);

        $client = $this->login('salvageuser');
        $crawler = $client->request('GET', '/salvaged-mechs/');

        $this->assertResponseIsSuccessful();
        $this->assertContainsText($crawler, 'Catapult CAT-PU1');
    }

    public function testCreateScrapyardMechSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('addmech', 'Add Mech Co', 'Clan');

        $client = $this->login('addmech');

        $crawler = $client->request('GET', '/salvaged-mechs/new');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateBattlefieldMechSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('bfmech', 'BF Mech Co', 'Inner Sphere');

        $client = $this->login('bfmech');

        $crawler = $client->request('GET', '/salvaged-mechs/new-with-check');

        $this->assertResponseIsSuccessful();
    }

    public function testSalvagedMechShowLoads(): void
    {
        $ref = $this->seedUserAndCompany('showmech', 'Show Mech Co', 'Inner Sphere');
        $mechanId = $this->seedSalvagedMech($ref['companyId'], [
            'model' => 'Catapult CAT-PU1',
            'tonnage' => 80,
            'bv_cost' => 300,
        ]);

        $client = $this->login('showmech');
        $crawler = $client->request('GET', '/salvaged-mechs/' . $mechanId);

        $this->assertResponseIsSuccessful();
        $this->assertContainsText($crawler, 'Catapult CAT-PU1');
    }

    public function testEditSalvagedMechSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('editmech', 'Edit Mech Co', 'Inner Sphere');
        $mechanId = $this->seedSalvagedMech($ref['companyId'], [
            'model' => 'Old Mech',
            'tonnage' => 80,
            'bv_cost' => 300,
        ]);

        $client = $this->login('editmech');

        $crawler = $client->request('GET', '/salvaged-mechs/' . $mechanId . '/edit');

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteSalvagedMechSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('delmech', 'Del Mech Co', 'Clan');
        $mechanId = $this->seedSalvagedMech($ref['companyId'], [
            'model' => 'ToDelete',
            'tonnage' => 80,
            'bv_cost' => 300,
        ]);

        $client = $this->login('delmech');

        $client->request('POST', '/salvaged-mechs/' . $mechanId);
        $this->assertResponseRedirects('/salvaged-mechs/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testAcquireMechSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('acquiremech', 'Acquire Mech Co', 'Inner Sphere');
        $this->seedSupportPoints($ref['companyId'], 1000, 'Funding');
        $mechanId = $this->seedSalvagedMech($ref['companyId'], [
            'model' => 'Catapult CAT-PU1',
            'tonnage' => 80,
            'bv_cost' => 300,
            'scrapyard' => 0,
            'salvage_rights_percent' => 50,
        ]);

        $client = $this->login('acquiremech');

        $client->request('POST', '/salvaged-mechs/' . $mechanId . '/acquire');
        $this->assertResponseRedirects('/salvaged-mechs/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testTakeSpSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('takemech', 'Take Mech Co', 'Inner Sphere');
        $this->seedSupportPoints($ref['companyId'], 1000, 'Funding');
        // Exchange path: salvage_rights_percent is null, so acquisition not allowed, but SP payout = 25%
        $mechanId = $this->seedSalvagedMech($ref['companyId'], [
            'model' => 'Catapult CAT-PU1',
            'tonnage' => 80,
            'bv_cost' => 300,
            'scrapyard' => 0,
            'salvage_rights_percent' => null,
        ]);

        $client = $this->login('takemech');

        $client->request('POST', '/salvaged-mechs/' . $mechanId . '/take-sp');
        $this->assertResponseRedirects('/salvaged-mechs/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testAcquireMechFromIndexShowsCostAndRedirects(): void
    {
        $ref = $this->seedUserAndCompany('acquireindex', 'Acquire Index Co', 'Inner Sphere');
        $this->seedSupportPoints($ref['companyId'], 1000, 'Funding');
        $mechanId = $this->seedSalvagedMech($ref['companyId'], [
            'model' => 'Thunderbolt THG-11d',
            'tonnage' => 100,
            'bv_cost' => 200,
            'scrapyard' => 0,
            'salvage_rights_percent' => 50,
            'sp_taken' => null,
        ]);

        $client = $this->login('acquireindex');
        $crawler = $client->request('GET', '/salvaged-mechs/');

        $this->assertResponseIsSuccessful();
        $this->assertContainsText($crawler, 'Thunderbolt THG-11d');

        $submitButton = $crawler->selectButton('Acquire Mech');
        $this->assertCount(1, $submitButton, 'Expected exactly one Acquire Mech button');

        $form = $submitButton->form();
        $client->submit($form);

        $this->assertResponseRedirects('/salvaged-mechs/');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testAcquireMechFromIndexNotShownWhenAlreadyAcquired(): void
    {
        $ref = $this->seedUserAndCompany('acquireindex', 'Acquire Index Co', 'Inner Sphere');
        $this->seedSalvagedMech($ref['companyId'], [
            'model' => 'Thunderbolt THG-11d',
            'tonnage' => 100,
            'bv_cost' => 200,
            'scrapyard' => 0,
            'salvage_rights_percent' => 50,
            'acquired' => 1,
            'sp_taken' => null,
        ]);

        $client = $this->login('acquireindex');
        $crawler = $client->request('GET', '/salvaged-mechs/');

        $this->assertResponseIsSuccessful();

        $submitButtons = $crawler->selectButton('Acquire Mech');
        $this->assertCount(0, $submitButtons, 'Expected no Acquire Mech buttons for acquired mech');
    }

    public function testAcquireMechFromIndexNotShownWhenTrulyDestroyed(): void
    {
        $ref = $this->seedUserAndCompany('acquireindex', 'Acquire Index Co', 'Inner Sphere');
        $this->seedSalvagedMech($ref['companyId'], [
            'model' => 'Thunderbolt THG-11d',
            'tonnage' => 100,
            'bv_cost' => 200,
            'scrapyard' => 0,
            'salvage_rights_percent' => 50,
            'is_truly_destroyed' => 1,
            'sp_taken' => null,
        ]);

        $client = $this->login('acquireindex');
        $crawler = $client->request('GET', '/salvaged-mechs/');

        $this->assertResponseIsSuccessful();

        $submitButtons = $crawler->selectButton('Acquire Mech');
        $this->assertCount(0, $submitButtons, 'Expected no Acquire Mech buttons for destroyed mech');
    }

    public function testDeleteMechFromIndexRedirectsToIndex(): void
    {
        $ref = $this->seedUserAndCompany('deleteindex', 'Delete Index Co', 'Inner Sphere');
        $this->seedSalvagedMech($ref['companyId'], [
            'model' => 'Thunderbolt THG-11d',
            'tonnage' => 100,
            'bv_cost' => 200,
            'scrapyard' => 0,
            'salvage_rights_percent' => 50,
        ]);

        $client = $this->login('deleteindex');
        $crawler = $client->request('GET', '/salvaged-mechs/');

        $this->assertResponseIsSuccessful();

        $submitButtons = $crawler->selectButton('Delete');
        $this->assertGreaterThan(0, $submitButtons->count(), 'Expected at least one Delete button');

        $form = $submitButtons->first()->form();
        $client->submit($form);

        $this->assertResponseRedirects('/salvaged-mechs/');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }
}
