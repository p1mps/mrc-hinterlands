<?php

namespace App\Tests\Acceptance;

class DropshipActionsAcceptanceTest extends AcceptanceTestCase
{
    public function testAssignUnitToDropshipSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('assignunit', 'Assign Unit Co', 'Inner Sphere');
        $dropshipId = $this->seedDropship($ref['companyId'], 'Valkyrie VLK-KNT', 5, 2);
        $unitId = $this->seedUnit($ref['companyId'], 'Thunderbird THB-XQ', 'Thunderbird THB-XQ', 60, 200, 'mech');

        $client = $this->login('assignunit');

        $client->request('POST', '/dropship/' . $dropshipId . '/assign-unit/' . $unitId);
        $this->assertResponseRedirects('/dropship/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testAssignUnitToDropshipFailsWhenNotEnoughMekbays(): void
    {
        $ref = $this->seedUserAndCompany('nomkbay', 'No Mekbay Co', 'Inner Sphere');
        $dropshipId = $this->seedDropship($ref['companyId'], 'Valkyrie VLK-KNT', 5, 0);
        $unitId = $this->seedUnit($ref['companyId'], 'Thunderbird THB-XQ', 'Thunderbird THB-XQ', 60, 200, 'mech');

        $client = $this->login('nomkbay');

        $client->request('POST', '/dropship/' . $dropshipId . '/assign-unit/' . $unitId);
        $this->assertResponseRedirects('/dropship/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertContainsText($crawler, 'No mekbays');
    }

    public function testUnassignUnitFromDropshipSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('unassignunit', 'Unassign Unit Co', 'Inner Sphere');
        $dropshipId = $this->seedDropship($ref['companyId'], 'Valkyrie VLK-KNT', 5, 2);
        $unitId = $this->seedUnit($ref['companyId'], 'Gravino GRV-NI1', 'Gravino GRV-NI1', 35, 150, 'mech');

        // First assign unit to dropship
        $client = $this->login('unassignunit');
        $client->request('POST', '/dropship/' . $dropshipId . '/assign-unit/' . $unitId);
        $this->assertResponseRedirects('/dropship/');
        $client->followRedirect();

        // Now unassign
        $client->request('POST', '/dropship/unassign-unit/' . $dropshipId . '/' . $unitId);
        $this->assertResponseRedirects('/dropship/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testAssignMechToDropshipSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('assignmech', 'Assign Mech Co', 'Inner Sphere');
        $dropshipId = $this->seedDropship($ref['companyId'], 'Valkyrie VLK-KNT', 5, 2);
        $mechanId = $this->seedSalvagedMech($ref['companyId'], [
            'model' => 'Catapult CAT-PU1',
            'tonnage' => 80,
            'bv_cost' => 300,
            'scrapyard' => 0,
        ]);

        $client = $this->login('assignmech');

        $client->request('POST', '/dropship/' . $dropshipId . '/assign-mech/' . $mechanId);
        $this->assertResponseRedirects('/dropship/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testUnassignMechFromDropshipSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('unassignmech', 'Unassign Mech Co', 'Inner Sphere');
        $dropshipId = $this->seedDropship($ref['companyId'], 'Valkyrie VLK-KNT', 5, 2);
        $mechanId = $this->seedSalvagedMech($ref['companyId'], [
            'model' => 'Catapult CAT-PU1',
            'tonnage' => 80,
            'bv_cost' => 300,
            'scrapyard' => 0,
        ]);

        // First assign mech to dropship
        $client = $this->login('unassignmech');
        $client->request('POST', '/dropship/' . $dropshipId . '/assign-mech/' . $mechanId);
        $this->assertResponseRedirects('/dropship/');
        $client->followRedirect();

        // Now unassign
        $client->request('POST', '/dropship/unassign-mech/' . $dropshipId . '/' . $mechanId);
        $this->assertResponseRedirects('/dropship/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testAssignUnitFailsWhenUnitAlreadyOnDropship(): void
    {
        $ref = $this->seedUserAndCompany('alreadyboarded', 'Already Boarded Co', 'Inner Sphere');
        $dropshipId = $this->seedDropship($ref['companyId'], 'Valkyrie VLK-KNT', 5, 2);
        $unitId = $this->seedUnit($ref['companyId'], 'Gravino GRV-NI1', 'Gravino GRV-NI1', 35, 150, 'mech');

        $client = $this->login('alreadyboarded');

        // Assign unit first
        $client->request('POST', '/dropship/' . $dropshipId . '/assign-unit/' . $unitId);
        $this->assertResponseRedirects('/dropship/');
        $client->followRedirect();

        // Try to assign again
        $client->request('POST', '/dropship/' . $dropshipId . '/assign-unit/' . $unitId);
        $this->assertResponseRedirects('/dropship/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }
}
