<?php

namespace App\Tests\Acceptance;

class RosterAcceptanceTest extends AcceptanceTestCase
{
    public function testRosterIndexLoads(): void
    {
        $ref = $this->seedUserAndCompany('rosteruser', 'Roster Co', 'Inner Sphere');
        $this->seedUnit($ref['companyId'], 'Gravino GRV-NI1', 'Gravino GRV-NI1', 35, 150, 'mech');
        $this->seedContract($ref['companyId'], ['status' => 'active']);

        $client = $this->login('rosteruser');
        $crawler = $client->request('GET', '/roster');

        $this->assertResponseIsSuccessful();
        $this->assertContainsText($crawler, 'Gravino GRV-NI1');
    }

    public function testCreateUnitSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('addunit', 'Add Unit Co', 'Clan');
        $this->seedContract($ref['companyId'], ['status' => 'active']);

        $client = $this->login('addunit');

        $crawler = $client->request('GET', '/roster/new');
        $form = $crawler->selectButton('Save')->form([
            'unit_form[name]' => 'Thunderbird THB-XQ',
            'unit_form[chassis]' => 'Thunderbird THB-XQ',
            'unit_form[tonnage]' => 60,
            'unit_form[bv]' => 200,
            'unit_form[unitType]' => 'mech',
            'unit_form[damageState]' => 'none',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/roster');

        $crawler = $client->followRedirect();
        $this->assertContainsText($crawler, 'Thunderbird THB-XQ');
    }

    public function testEditUnitSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('editunit', 'Edit Unit Co', 'Inner Sphere');
        $unitId = $this->seedUnit($ref['companyId'], 'Old Mech', 'Old Chassis', 50, 200, 'mech');
        $this->seedContract($ref['companyId'], ['status' => 'active']);

        $client = $this->login('editunit');

        $crawler = $client->request('GET', '/roster/' . $unitId . '/edit');
        $form = $crawler->selectButton('Save')->form([
            'unit_form[name]' => 'New Mech',
            'unit_form[chassis]' => 'New Chassis',
            'unit_form[tonnage]' => 75,
            'unit_form[bv]' => 300,
            'unit_form[unitType]' => 'mech',
            'unit_form[damageState]' => 'structural',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/roster');

        $crawler = $client->followRedirect();
        $this->assertContainsText($crawler, 'New Mech');
    }

    public function testAssignPilotToUnitSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('assignuser', 'Assign Co', 'Inner Sphere');
        $pilotId = $this->seedPilot($ref['companyId'], 'Ace', true);
        $unitId = $this->seedUnit($ref['companyId'], 'Mech', 'Mech', 50, 200, 'mech');
        $this->seedContract($ref['companyId'], ['status' => 'active']);

        $client = $this->login('assignuser');

        $client->request('POST', '/roster/' . $unitId . '/assign-pilot', [
            'pilot_id' => $pilotId,
        ]);

        $this->assertResponseRedirects('/roster');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testDeleteUnitSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('delunit', 'Del Unit Co', 'Clan');
        $unitId = $this->seedUnit($ref['companyId'], 'ToDelete', 'ToDelete', 50, 200, 'mech');
        $this->seedContract($ref['companyId'], ['status' => 'active']);

        $client = $this->login('delunit');

        $client->request('POST', '/roster/' . $unitId . '/delete');
        $this->assertResponseRedirects('/roster');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testRepairUnitSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('repairuser', 'Repair Co', 'Inner Sphere');
        $this->seedSupportPoints($ref['companyId'], 500, 'Funding');
        $unitId = $this->seedUnit($ref['companyId'], 'Damaged Mech', 'Mech', 50, 200, 'mech', 'structural');
        $this->seedContract($ref['companyId'], ['status' => 'active']);

        $client = $this->login('repairuser');

        $client->request('POST', '/roster/' . $unitId . '/repair');
        $this->assertResponseRedirects('/roster');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }
}
