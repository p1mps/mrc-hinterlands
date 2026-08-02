<?php

namespace App\Tests\Acceptance;

class ContractAcceptanceTest extends AcceptanceTestCase
{
    public function testContractsIndexLoads(): void
    {
        $ref = $this->seedUserAndCompany('contractuser', 'Contract Co', 'Inner Sphere');
        $this->seedContract($ref['companyId'], ['name' => 'Test Contract']);

        $client = $this->login('contractuser');
        $crawler = $client->request('GET', '/contract');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateContractSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('addcontract', 'Add Contract Co', 'Inner Sphere');
        $this->seedSupportPoints($ref['companyId'], 1000, 'Funding');

        $client = $this->login('addcontract');

        $crawler = $client->request('GET', '/contract/new');

        $this->assertResponseIsSuccessful();
    }

    public function testContractShowPageLoads(): void
    {
        $ref = $this->seedUserAndCompany('showcontract', 'Show Contract Co', 'Inner Sphere');
        $contractId = $this->seedContract($ref['companyId'], ['name' => 'Visible Contract']);

        $client = $this->login('showcontract');
        $crawler = $client->request('GET', '/contract/' . $contractId);

        $this->assertResponseIsSuccessful();
        $this->assertContainsText($crawler, 'Visible Contract');
    }

    public function testEditContractSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('editcontract', 'Edit Contract Co', 'Inner Sphere');
        $contractId = $this->seedContract($ref['companyId'], ['name' => 'Old Contract', 'employer' => 'Old Client']);

        $client = $this->login('editcontract');

        $crawler = $client->request('GET', '/contract/' . $contractId . '/edit');
        $form = $crawler->selectButton('Save Changes')->form([
            'contract_edit_form[name]' => 'New Contract',
            'contract_edit_form[status]' => 'available',
            'contract_edit_form[type]' => 'garrison',
            'contract_edit_form[employer]' => 'New Client',
            'contract_edit_form[employerAffiliation]' => 'House Davion',
            'contract_edit_form[scale]' => 2,
            'contract_edit_form[durationMonths]' => 12,
            'contract_edit_form[commandRights]' => 'house',
            'contract_edit_form[supportTerms]' => 'Battle 50%',
            'contract_edit_form[salvageRights]' => '3',
            'contract_edit_form[transportTerms]' => '10%',
            'contract_edit_form[numberOfTracks]' => 2,
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/contract');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testAcceptAvailableContract(): void
    {
        $ref = $this->seedUserAndCompany('acceptcontract', 'Accept Contract Co', 'Inner Sphere');
        $this->seedSupportPoints($ref['companyId'], 1000, 'Funding');
        $contractId = $this->seedContract($ref['companyId'], ['status' => 'available']);

        $client = $this->login('acceptcontract');

        $client->request('POST', '/contract/' . $contractId . '/accept');
        $this->assertResponseRedirects('/contract');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testGenerateOpposingContract(): void
    {
        $ref = $this->seedUserAndCompany('opposing', 'Opposing Co', 'Inner Sphere');
        $contractId = $this->seedContract($ref['companyId'], ['is_opposing' => 0]);

        $client = $this->login('opposing');

        $client->request('POST', '/contract/' . $contractId . '/generate-opposing');
        $this->assertResponseRedirects('/contract');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testDeleteContractSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('delcontract', 'Del Contract Co', 'Clan');
        $contractId = $this->seedContract($ref['companyId'], ['name' => 'ToDelete']);

        $client = $this->login('delcontract');

        $client->request('POST', '/contract/' . $contractId . '/delete');
        $this->assertResponseRedirects('/contract');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testContractGeneratePageLoads(): void
    {
        $ref = $this->seedUserAndCompany('genuser', 'Gen Co', 'Inner Sphere');
        $this->seedPilot($ref['companyId'], 'Pilot One', true);
        $this->seedUnit($ref['companyId'], 'Mech', 'Mech', 50, 200, 'mech');

        $client = $this->login('genuser');
        $crawler = $client->request('GET', '/contract/generate');

        $this->assertResponseIsSuccessful();
    }

    public function testAcceptGeneratedContract(): void
    {
        $ref = $this->seedUserAndCompany('genaccept', 'Gen Accept Co', 'Inner Sphere');
        $this->seedSupportPoints($ref['companyId'], 1000, 'Funding');
        $this->seedPilot($ref['companyId'], 'Pilot One', true);
        $this->seedUnit($ref['companyId'], 'Mech', 'Mech', 50, 200, 'mech');

        $client = $this->login('genaccept');

        // Submit a generated contract
        $client->request('POST', '/contract/generate/accept', [
            'type' => 'expedition',
            'employer' => 'Client',
            'affiliation' => 'House Davion',
            'scale' => 1,
            'duration' => 6,
            'basePayPercent' => '75',
            'commandRights' => 'integrated',
            'supportTerms' => 'None',
            'salvageRights' => 'Exchange',
            'transportTerms' => '—',
            'numberOfTracks' => 1,
        ]);

        $this->assertResponseRedirects('/contract');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testDiscardGeneratedContract(): void
    {
        $ref = $this->seedUserAndCompany('gendiscard', 'Gen Discard Co', 'Inner Sphere');

        $client = $this->login('gendiscard');

        $client->request('POST', '/contract/generate/discard');
        $this->assertResponseRedirects('/contract');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testTrackSetupSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('trackuser', 'Track Co', 'Inner Sphere');
        $contractId = $this->seedContractWithTracks($ref['companyId'], 1, ['status' => 'active']);

        $client = $this->login('trackuser');

        $client->request('POST', '/contract/' . $contractId . '/track-setup', [
            'month' => 1,
        ]);

        $this->assertResponseRedirects('/contract');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testPostTrackSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('posttrack', 'Post Track Co', 'Inner Sphere');
        $this->seedSupportPoints($ref['companyId'], 500, 'Funding');
        $contractId = $this->seedContractWithTracks($ref['companyId'], 1, ['status' => 'active']);

        $client = $this->login('posttrack');

        $client->request('POST', '/contract/' . $contractId . '/post-track', [
            'combatPayTier' => 'none',
            'salvageClaimed' => false,
        ]);

        $this->assertResponseRedirects('/contract');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testDowntimeSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('downtime', 'Downtime Co', 'Inner Sphere');
        $contractId = $this->seedContract($ref['companyId'], ['status' => 'active']);

        $client = $this->login('downtime');

        $client->request('POST', '/contract/' . $contractId . '/downtime', [
            'month' => 1,
            'amount' => 0,
            'note' => 'Training',
        ]);

        $this->assertResponseRedirects('/contract');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testSalvageRecorded(): void
    {
        $ref = $this->seedUserAndCompany('salvagecontract', 'Salvage Contract Co', 'Inner Sphere');
        $contractId = $this->seedContract($ref['companyId'], ['status' => 'active']);

        $client = $this->login('salvagecontract');

        $client->request('POST', '/contract/' . $contractId . '/salvage', [
            'month' => 1,
            'amount' => 0,
            'note' => 'Nothing found',
        ]);

        $this->assertResponseRedirects('/contract');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testNegotiateGeneratePageShowsCorrectBaseSteps(): void
    {
        $ref = $this->seedUserAndCompany('negotiateuser', 'Negotiate Co', 'Inner Sphere');
        // Set reputation to 5 so availableSteps = min(5, 2*1) = 2
        $conn = self::$sharedEm->getConnection();
        $conn->update('mercenary_company', ['reputation' => 5], ['id' => $ref['companyId']]);

        $this->seedPilot($ref['companyId'], 'Pilot One', true);
        $this->seedUnit($ref['companyId'], 'Mech', 'Mech', 50, 200, 'mech');

        $client = $this->login('negotiateuser');
        $crawler = $client->request('GET', '/contract/generate?negotiate=true');

        $this->assertResponseIsSuccessful();

        // The negotiation table should exist
        $negotiationTable = $crawler->filter('#negotiation-table');
        $this->assertNotEmpty($negotiationTable, 'Negotiation table should be rendered');

        // The base-step cells should exist and match current-step cells
        $baseStepCells = $crawler->filter('#negotiation-table .base-step');
        $this->assertNotEmpty($baseStepCells, 'Base step cells should exist');

        $baseStepTexts = [];
        foreach ($baseStepCells as $cell) {
            $baseStepTexts[] = (int) trim($cell->textContent);
        }

        // Verify that not all base steps are 1 (which was the bug - template defaulting)
        // With random 2d6 rolls, the probability of all 5 categories landing on step 1
        // (requiring roll=2 with heavy negative modifiers) is astronomically low.
        // The acceptance test uses employer='Client' with no affiliation, so modifiers are ~0.
        $allOnes = array_filter($baseStepTexts, fn($s) => $s === 1);
        $this->assertNotEquals(
            count($baseStepTexts),
            count($allOnes),
            'All base steps being 1 indicates the template is defaulting to 1 instead of reading from rolls (bug)'
        );

        // The current-step values should match the base-step values (initial state)
        $currentStepCells = $crawler->filter('#negotiation-table .current-step');
        $this->assertCount($baseStepCells->count(), $currentStepCells);

        $currentStepTexts = [];
        foreach ($currentStepCells as $cell) {
            $currentStepTexts[] = (int) trim($cell->textContent);
        }
        $this->assertEquals($baseStepTexts, $currentStepTexts, 'Current steps should match base steps initially');

        // Verify that the base steps come from actual rolls, not all defaulting to 1.
        // With 5 categories and random 2d6 rolls (2-12), the probability that ALL 5
        // happen to produce step 1 is astronomically low (step 1 requires roll=2 with
        // heavy negative modifiers that don't apply here). If all 5 are 1, the bug
        // is present (data.rolls was missing, template defaulted to 1).
        $allOnes = array_filter($baseStepTexts, fn($s) => $s === 1);
        $this->assertNotEquals(
            count($baseStepTexts),
            count($allOnes),
            'All base steps being 1 indicates the template is defaulting to 1 instead of reading from rolls (bug)'
        );
    }
}
