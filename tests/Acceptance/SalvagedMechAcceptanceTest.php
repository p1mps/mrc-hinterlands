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

        // The scrapyard generation automatically creates a mech and redirects to the show page
        $client->request('GET', '/salvaged-mechs/new');
        $this->assertResponseRedirects('/salvaged-mechs/');
        
        // Follow the redirect to the show page
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
        
        // Verify the newly created mech appears on the show page
        $this->assertContainsText($crawler, 'Scrapyard');
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
        $company = self::$sharedEm->getRepository(\App\Entity\MercenaryCompany::class)->find($ref['companyId']);

        $conn = self::$sharedEm->getConnection();
        $conn->insert('contract', [
            'name' => 'Test Contract',
            'type' => 'expedition',
            'employer' => 'Test Employer',
            'employer_affiliation' => 'Inner Sphere',
            'scale' => 1,
            'duration_months' => 12,
            'status' => 'available',
            'is_opposing' => false,
            'command_rights' => 'integrated',
            'support_terms' => 'None',
            'salvage_rights' => 'None',
            'transport_terms' => '—',
            'number_of_tracks' => 1,
            'tracks_completed' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $contractId = (int) $conn->lastInsertId('contract_id_seq');

        $mechan = new \App\Entity\SalvagedMech();
        $mechan->setCompany($company);
        $mechan->setModel('Thunderbolt THG-11d');
        $mechan->setTonnage(100);
        $mechan->setBvCost(200);
        $mechan->setSalvageRightsPercent(50);
        $mechan->setScrapyard(false);
        $mechan->setContractId($contractId);
        self::$sharedEm->persist($mechan);
        self::$sharedEm->flush();

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

    // ── Sell Tests ──────────────────────────────────────────────────────────

    public function testSellMechSucceedsWithNumericSalvageRights(): void
    {
        $ref = $this->seedUserAndCompany('selluser', 'Sell Mech Co', 'Inner Sphere');
        $companyId = $ref['companyId'];
        $this->seedSupportPoints($companyId, 1000, 'Funding');

        // Seed a contract with numeric salvage rights (e.g., "3" = 3%)
        $this->seedContract($companyId, [
            'status' => 'active',
            'salvage_rights' => '3',
        ]);

        $mechanId = $this->seedSalvagedMech($companyId, [
            'model' => 'Warhammer WHM-6M',
            'tonnage' => 90,
            'bv_cost' => 200,
            'salvage_rights_percent' => 50,
            'is_truly_destroyed' => 0,
            'sp_taken' => null,
        ]);

        $client = $this->login('selluser');

        // Submit the Sell form
        $client->request('POST', '/salvaged-mechs/' . $mechanId . '/sell');
        $this->assertResponseRedirects('/salvaged-mechs/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Verify success flash message
        $this->assertFlashMessage($crawler, 'Sold Warhammer WHM-6M for');
    }

    public function testSellMechFailsWithExchangeSalvageTerms(): void
    {
        $ref = $this->seedUserAndCompany('sellfail', 'Sell Fail Co', 'Inner Sphere');
        $companyId = $ref['companyId'];
        $this->seedSupportPoints($companyId, 1000, 'Funding');

        // Seed a contract with "Exchange" salvage terms (prohibits selling)
        $this->seedContract($companyId, [
            'status' => 'active',
            'salvage_rights' => 'Exchange',
        ]);

        $mechanId = $this->seedSalvagedMech($companyId, [
            'model' => 'Warhammer WHM-6M',
            'tonnage' => 90,
            'bv_cost' => 200,
            'salvage_rights_percent' => 50,
            'is_truly_destroyed' => 0,
            'sp_taken' => null,
        ]);

        $client = $this->login('sellfail');

        // Submit the Sell form — should fail with error about Exchange terms
        $client->request('POST', '/salvaged-mechs/' . $mechanId . '/sell');
        $this->assertResponseRedirects('/salvaged-mechs/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Verify error flash message about Exchange terms
        $this->assertFlashMessage($crawler, 'Exchange');
    }

    public function testSellMechFailsWithExchangePercentSalvageTerms(): void
    {
        $ref = $this->seedUserAndCompany('sellfail2', 'Sell Fail 2 Co', 'Inner Sphere');
        $companyId = $ref['companyId'];
        $this->seedSupportPoints($companyId, 1000, 'Funding');

        // Seed a contract with "Exchange/50%" salvage terms (prohibits selling)
        $this->seedContract($companyId, [
            'status' => 'active',
            'salvage_rights' => 'Exchange/50%',
        ]);

        $mechanId = $this->seedSalvagedMech($companyId, [
            'model' => 'Warhammer WHM-6M',
            'tonnage' => 90,
            'bv_cost' => 200,
            'salvage_rights_percent' => 50,
            'is_truly_destroyed' => 0,
            'sp_taken' => null,
        ]);

        $client = $this->login('sellfail2');

        // Submit the Sell form — should fail with error about Exchange terms
        $client->request('POST', '/salvaged-mechs/' . $mechanId . '/sell');
        $this->assertResponseRedirects('/salvaged-mechs/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Verify error flash message about Exchange terms
        $this->assertFlashMessage($crawler, 'Exchange');
    }

    public function testSellMechFailsWithNoSalvageRights(): void
    {
        $ref = $this->seedUserAndCompany('sellfail3', 'Sell Fail 3 Co', 'Inner Sphere');
        $companyId = $ref['companyId'];
        $this->seedSupportPoints($companyId, 1000, 'Funding');

        // Seed a contract with "None" salvage terms (no salvage rights)
        $this->seedContract($companyId, [
            'status' => 'active',
            'salvage_rights' => 'None',
        ]);

        $mechanId = $this->seedSalvagedMech($companyId, [
            'model' => 'Warhammer WHM-6M',
            'tonnage' => 90,
            'bv_cost' => 200,
            'salvage_rights_percent' => 50,
            'is_truly_destroyed' => 0,
            'sp_taken' => null,
        ]);

        $client = $this->login('sellfail3');

        // Submit the Sell form — should fail with error about no salvage rights
        $client->request('POST', '/salvaged-mechs/' . $mechanId . '/sell');
        $this->assertResponseRedirects('/salvaged-mechs/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Verify error flash message about no salvage rights
        $this->assertFlashMessage($crawler, 'no salvage rights');
    }

    public function testSellMechFailsWhenNoActiveContract(): void
    {
        $ref = $this->seedUserAndCompany('sellfail4', 'Sell Fail 4 Co', 'Inner Sphere');
        $companyId = $ref['companyId'];
        $this->seedSupportPoints($companyId, 1000, 'Funding');

        // No active contract seeded

        $mechanId = $this->seedSalvagedMech($companyId, [
            'model' => 'Warhammer WHM-6M',
            'tonnage' => 90,
            'bv_cost' => 200,
            'salvage_rights_percent' => 50,
            'is_truly_destroyed' => 0,
            'sp_taken' => null,
        ]);

        $client = $this->login('sellfail4');

        // Submit the Sell form — should fail because no active contract
        $client->request('POST', '/salvaged-mechs/' . $mechanId . '/sell');
        $this->assertResponseRedirects('/salvaged-mechs/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Verify error flash message about no active contract
        $this->assertFlashMessage($crawler, 'no active contract');
    }

    public function testSellMechNotShownWhenAlreadyAcquired(): void
    {
        $ref = $this->seedUserAndCompany('sellfail5', 'Sell Fail 5 Co', 'Inner Sphere');
        $companyId = $ref['companyId'];

        $mechanId = $this->seedSalvagedMech($companyId, [
            'model' => 'Warhammer WHM-6M',
            'tonnage' => 90,
            'bv_cost' => 200,
            'salvage_rights_percent' => 50,
            'is_truly_destroyed' => 0,
            'sp_taken' => null,
        ]);

        // Manually set contract_id to simulate already acquired
        $conn = self::$sharedEm->getConnection();
        $conn->update('salvaged_mech', ['contract_id' => 1], ['id' => $mechanId]);

        $client = $this->login('sellfail5');
        $crawler = $client->request('GET', '/salvaged-mechs/');

        $this->assertResponseIsSuccessful();

        $submitButtons = $crawler->selectButton('Sell');
        $this->assertCount(0, $submitButtons, 'Expected no Sell buttons for acquired mech');
    }

    public function testSellMechNotShownWhenAlreadySpTaken(): void
    {
        $ref = $this->seedUserAndCompany('sellfail6', 'Sell Fail 6 Co', 'Inner Sphere');
        $companyId = $ref['companyId'];

        $mechanId = $this->seedSalvagedMech($companyId, [
            'model' => 'Warhammer WHM-6M',
            'tonnage' => 90,
            'bv_cost' => 200,
            'salvage_rights_percent' => 50,
            'is_truly_destroyed' => 0,
            'sp_taken' => 75,
        ]);

        $client = $this->login('sellfail6');
        $crawler = $client->request('GET', '/salvaged-mechs/');

        $this->assertResponseIsSuccessful();

        $submitButtons = $crawler->selectButton('Sell');
        $this->assertCount(0, $submitButtons, 'Expected no Sell buttons for SP-taken mech');
    }

    public function testSellButtonVisibleOnIndexForAvailableMech(): void
    {
        $ref = $this->seedUserAndCompany('sellvisible', 'Sell Visible Co', 'Inner Sphere');
        $this->seedSupportPoints($ref['companyId'], 1000, 'Funding');

        $mechanId = $this->seedSalvagedMech($ref['companyId'], [
            'model' => 'Warhammer WHM-6M',
            'tonnage' => 90,
            'bv_cost' => 200,
            'salvage_rights_percent' => 50,
            'is_truly_destroyed' => 0,
            'sp_taken' => null,
        ]);

        $client = $this->login('sellvisible');
        $crawler = $client->request('GET', '/salvaged-mechs/');

        $this->assertResponseIsSuccessful();

        $submitButtons = $crawler->selectButton('Sell');
        $this->assertCount(1, $submitButtons, 'Expected exactly one Sell button for available mech');
    }
}
