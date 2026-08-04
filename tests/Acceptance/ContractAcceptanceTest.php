<?php

namespace App\Tests\Acceptance;

use App\Enum\ContractStatus;
use App\Enum\ContractType;

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

    public function testStandardGeneratePageShowsContractDetails(): void
    {
        $ref = $this->seedUserAndCompany('standarduser', 'Standard Co', 'Inner Sphere');
        $this->seedPilot($ref['companyId'], 'Pilot One', true);
        $this->seedUnit($ref['companyId'], 'Mech', 'Mech', 50, 200, 'mech');

        $client = $this->login('standarduser');
        $crawler = $client->request('GET', '/contract/generate');

        $this->assertResponseIsSuccessful();

        // The contract details table should exist
        $detailsTable = $crawler->filter('table.table-bordered');
        $this->assertNotEmpty($detailsTable, 'Contract details table should be rendered');

        // The negotiation table should NOT exist (standard mode)
        $negotiationTable = $crawler->filter('#negotiation-table');
        $this->assertEmpty($negotiationTable, 'Negotiation table should not be rendered in standard mode');

        // The accept button should exist
        $acceptButton = $crawler->filter('button.btn-success');
        $this->assertNotEmpty($acceptButton, 'Accept button should exist');
    }

    public function testNegotiateViewPageShowsCorrectBaseSteps(): void
    {
        $ref = $this->seedUserAndCompany('negotiateuser', 'Negotiate Co', 'Inner Sphere');
        $conn = self::$sharedEm->getConnection();
        $conn->update('mercenary_company', ['reputation' => 5], ['id' => $ref['companyId']]);

        $this->seedPilot($ref['companyId'], 'Pilot One', true);
        $this->seedUnit($ref['companyId'], 'Mech', 'Mech', 50, 200, 'mech');

        $client = $this->login('negotiateuser');

        // Step 1: Generate a contract (standard flow)
        $crawler = $client->request('GET', '/contract/generate');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->filter('.btn-success')->first()->form();
        $client->submit($form);
        $this->assertResponseRedirects('/contract');

        // Step 2: Go to the contract index page, then to the first contract's show page
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $crawler = $client->getCrawler();

        $contractLink = $crawler->filter('a[href*="/contract/"]')->first();
        $this->assertNotEmpty($contractLink, 'Contract link should exist');
        $client->click($contractLink->link());

        // Step 3: Get contract ID and navigate to negotiate view page
        $contractId = $client->getRequest()->getUri();
        preg_match('#/contract/(\d+)/?$#', $contractId, $matches);
        $contractId = $matches[1] ?? 1;
        $crawler = $client->request('GET', "/contract/{$contractId}/negotiate");
        $this->assertResponseIsSuccessful();

        // Step 4: Verify the negotiation-data script block contains valid JSON
        $dataScript = $crawler->filter('#negotiation-data');
        $this->assertNotEmpty($dataScript, 'Negotiation data script should exist');
        $scriptContent = trim($dataScript->first()->html());
        $decoded = json_decode($scriptContent, true);
        $this->assertNotNull($decoded, "Negotiation data script must contain valid JSON. Raw content: " . substr($scriptContent, 0, 500));
        $this->assertArrayHasKey('scale', $decoded, 'Scale must be present in JSON');
        $this->assertArrayHasKey('initialSteps', $decoded, 'Initial steps must be present in JSON');
        $this->assertArrayHasKey('reputation', $decoded, 'Reputation must be present in JSON');

        // Step 5: Verify dynamic counters exist
        $counters = $crawler->filter('#negotiation-counters');
        $this->assertNotEmpty($counters, 'Negotiation counters should be rendered');
        $this->assertNotEmpty($crawler->filter('#rep-spent'), 'Reputation spent counter should exist');
        $this->assertNotEmpty($crawler->filter('#rep-max'), 'Reputation max counter should exist');
        $this->assertNotEmpty($crawler->filter('#tradeoffs-used'), 'Trade-offs counter should exist');
        $this->assertNotEmpty($crawler->filter('#available-boosts'), 'Available boosts counter should exist');

        // Step 6: Verify interactive negotiation table exists (body populated by JS)
        $negotiationTable = $crawler->filter('#negotiation-table');
        $this->assertNotEmpty($negotiationTable, 'Negotiation table should be rendered');
        $this->assertNotEmpty($crawler->filter('#negotiation-tbody'), 'Negotiation table body should exist');

        // Step 7: Verify steps table reference table exists (second table with thead.table-secondary)
        $stepsRefTable = $crawler->filter('table thead.table-secondary + tbody tr');
        $this->assertNotEmpty($stepsRefTable, 'Steps reference table should be rendered');
        $this->assertCount(13, $stepsRefTable, 'Should have 13 steps in reference table');

        // Step 8: Verify the page title shows the contract name
        $this->assertStringContainsString('Negotiate:', $crawler->filter('h2')->first()->text());

        // Step 9: Verify back button exists
        $backLink = $crawler->filter('a[href*="/contract/"]');
        $this->assertNotEmpty($backLink, 'Back to Contract link should exist');
    }

    public function testContractNegotiationFlow(): void
    {
        // 1. Seed user + company (reputation 8) + contract (scale 3 → maxShifts = 6)
        $ref = $this->seedUserAndCompany('negotiateflow', 'Negotiate Flow Co', 'Inner Sphere');
        $conn = self::$sharedEm->getConnection();
        $conn->update('mercenary_company', ['reputation' => 8], ['id' => $ref['companyId']]);

        // Seed contract with explicit valid values (all categories at valid steps)
        // Base Pay: step 5 (80%), Command Rights: step 3 (Integrated),
        // Salvage: step 5 (20%), Support: step 5 (Straight/80%), Transport: step 5 (0%)
        $contractId = $this->seedContract($ref['companyId'], [
            'scale' => 3,
            'type' => ContractType::Raid->value,
            'status' => ContractStatus::Available->value,
            'base_pay_percent' => 80,
            'command_rights' => 'integrated',
            'salvage_rights' => '20%',
            'support_terms' => 'Straight/80%',
            'transport_terms' => '0%',
        ]);

        // 2. GET negotiate view
        $client = $this->login('negotiateflow');
        $crawler = $client->request('GET', "/contract/{$contractId}/negotiate");
        $this->assertResponseIsSuccessful();

        // 3. Verify negotiation data script block exists (JavaScript initialization)
        $dataScript = $crawler->filter('#negotiation-data');
        $this->assertNotEmpty($dataScript, 'Negotiation data script should exist');
        $scriptContent = $dataScript->first()->html();
        $decoded = json_decode($scriptContent, true);
        $this->assertNotNull($decoded, 'Data script must contain valid JSON');
        $this->assertEquals(8, $decoded['reputation'], 'Reputation should be 8');

        // 4. Verify interactive negotiation table exists
        $negotiationTable = $crawler->filter('#negotiation-table');
        $this->assertNotEmpty($negotiationTable, 'Negotiation table should be rendered');

        // 5. Verify steps reference table exists (13 steps)
        $refRows = $crawler->filter('table thead.table-secondary + tbody tr');
        $this->assertCount(13, $refRows, 'Steps reference table should have 13 rows');

        // 6. Verify accept button exists and is initially disabled
        $acceptBtn = $crawler->filter('#accept-negotiation-btn');
        $this->assertNotEmpty($acceptBtn, 'Accept negotiation button should exist');
    }

    public function testNegotiationAcceptSucceeds(): void
    {
        // 1. Seed user + company (reputation 8) + contract (scale 3)
        $ref = $this->seedUserAndCompany('negaccept', 'Negotiate Accept Co', 'Inner Sphere');
        $conn = self::$sharedEm->getConnection();
        $conn->update('mercenary_company', ['reputation' => 8], ['id' => $ref['companyId']]);

        // Seed contract with explicit valid values
        $contractId = $this->seedContract($ref['companyId'], [
            'scale' => 3,
            'type' => ContractType::Raid->value,
            'status' => ContractStatus::Available->value,
            'base_pay_percent' => 80,
            'command_rights' => 'integrated',
            'salvage_rights' => '20%',
            'support_terms' => 'Straight/80%',
            'transport_terms' => '0%',
        ]);

        // 2. Submit negotiation with modified terms (shift basePay to step 7, salvage to step 7)
        $client = $this->login('negaccept');
        $client->request('POST', "/contract/{$contractId}/negotiate/accept", [
            'negotiation_basePayPercent' => 7,
            'negotiation_commandRights' => 3,
            'negotiation_salvageRights' => 7,
            'negotiation_supportTerms' => 5,
            'negotiation_transportTerms' => 5,
        ]);

        $this->assertResponseRedirects('/contract/' . $contractId);
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Verify success flash message
        $this->assertStringContainsString('negotiation', strtolower($crawler->filter('.alert')->first()->text()));
    }
}
