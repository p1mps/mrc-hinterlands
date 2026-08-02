<?php

namespace App\Tests\Acceptance;

class PilotAcceptanceTest extends AcceptanceTestCase
{
    public function testPilotsIndexLoads(): void
    {
        $ref = $this->seedUserAndCompany('pilotuser', 'Pilot Co', 'Inner Sphere');
        $this->seedPilot($ref['companyId'], 'Ace Ventura', true);
        $this->seedPilot($ref['companyId'], 'Norman Normal', false);

        $client = $this->login('pilotuser');
        $crawler = $client->request('GET', '/pilots');

        $this->assertResponseIsSuccessful();
        $this->assertContainsText($crawler, 'Ace Ventura');
        $this->assertContainsText($crawler, 'Norman Normal');
    }

    public function testCreatePilotSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('addpilot', 'Add Pilot Co', 'Clan');

        $client = $this->login('addpilot');

        $crawler = $client->request('GET', '/pilots/new');
        $form = $crawler->selectButton('Save')->form([
            'pilot_form[name]' => 'Thunderbird Tom',
            'pilot_form[gunnery]' => 4,
            'pilot_form[piloting]' => 5,
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/pilots');

        $crawler = $client->followRedirect();
        $this->assertContainsText($crawler, 'Thunderbird Tom');
    }

    public function testEditPilotSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('editpilot', 'Edit Pilot Co', 'Inner Sphere');
        $pilotId = $this->seedPilot($ref['companyId'], 'Old Name', false);

        $client = $this->login('editpilot');

        $crawler = $client->request('GET', '/pilots/' . $pilotId . '/edit');
        $form = $crawler->selectButton('Save')->form([
            'pilot_form[name]' => 'New Name',
            'pilot_form[gunnery]' => 3,
            'pilot_form[piloting]' => 4,
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/pilots');

        $crawler = $client->followRedirect();
        $this->assertContainsText($crawler, 'New Name');
    }

    public function testDeletePilotSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('delpilot', 'Del Pilot Co', 'Clan');
        $pilotId = $this->seedPilot($ref['companyId'], 'ToDelete', false);

        $client = $this->login('delpilot');

        $client->request('POST', '/pilots/' . $pilotId . '/delete');
        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }
}
