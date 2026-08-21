<?php

namespace App\Tests\Acceptance;

class RosterBattlefieldLossAcceptanceTest extends AcceptanceTestCase
{
    /**
     * Test that a unit can be marked as a battlefield loss when the company
     * has an active contract with Battle support that includes a percentage.
     */
    public function testBattlefieldLossSucceedsWithBattleSupport(): void
    {
        $ref = $this->seedUserAndCompany('bltest', 'Battle Loss Co', 'Inner Sphere');
        $companyId = $ref['companyId'];

        // Create a unit with BV 500
        $unitId = $this->seedUnit($companyId, 'Atlas AC/2', 'Atlas AC/2', 150, 500, 'mech', 'none');

        // Create an active contract with Battle/50% support
        $contractId = $this->seedContract($companyId, [
            'status' => 'active',
            'support_terms' => 'Battle/50%',
        ]);

        $client = $this->login('bltest');

        // Verify initial support points (should be 0 since no entries)
        $conn = $client->getContainer()->get('doctrine')->getManager()->getConnection();
        $initialBalance = (int) $conn->fetchOne(
            'SELECT COALESCE(SUM(amount), 0) FROM support_point_entry WHERE company_id = ?',
            [$companyId]
        );
        $this->assertEquals(0, $initialBalance);

        // Submit battlefield loss
        $client->request('POST', '/roster/' . $unitId . '/battlefield-lose');
        $this->assertResponseRedirects('/roster');

        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Verify success flash message
        $this->assertFlashMessage($crawler, 'Unit marked as battlefield loss. Support points credited.');

        // Verify unit was removed
        $conn = $client->getContainer()->get('doctrine')->getManager()->getConnection();
        $unitCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM unit WHERE company_id = ?',
            [$companyId]
        );
        $this->assertEquals(0, $unitCount, 'Unit should be removed after battlefield loss');

        // Verify support points were credited: floor(500 * 50 / 100) = 250
        $finalBalance = (int) $conn->fetchOne(
            'SELECT COALESCE(SUM(amount), 0) FROM support_point_entry WHERE company_id = ?',
            [$companyId]
        );
        $this->assertEquals(250, $finalBalance, 'Expected 250 support points (floor(500 * 50 / 100))');
    }

    /**
     * Test that battlefield loss fails when there is no active contract.
     */
    public function testBattlefieldLossFailsWithoutActiveContract(): void
    {
        $ref = $this->seedUserAndCompany('blnocontract', 'No Contract Co', 'Inner Sphere');
        $companyId = $ref['companyId'];

        $unitId = $this->seedUnit($companyId, 'Mech', 'Mech', 50, 200, 'mech', 'none');

        $client = $this->login('blnocontract');

        // Submit battlefield loss without an active contract
        $client->request('POST', '/roster/' . $unitId . '/battlefield-lose');
        $this->assertResponseRedirects('/roster');

        $crawler = $client->followRedirect();
        $this->assertFlashMessage($crawler, 'No active contract found. Battlefield loss requires an active support contract.');

        // Verify unit still exists
        $conn = $client->getContainer()->get('doctrine')->getManager()->getConnection();
        $unitCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM unit WHERE company_id = ?',
            [$companyId]
        );
        $this->assertEquals(1, $unitCount, 'Unit should still exist');
    }

    /**
     * Test that battlefield loss fails when the active contract has no Battle support.
     */
    public function testBattlefieldLossFailsWithNonBattleSupport(): void
    {
        $ref = $this->seedUserAndCompany('blnonsupport', 'No Support Co', 'Inner Sphere');
        $companyId = $ref['companyId'];

        $unitId = $this->seedUnit($companyId, 'Mech', 'Mech', 50, 200, 'mech', 'none');

        // Create an active contract with Straight support (not Battle)
        $this->seedContract($companyId, [
            'status' => 'active',
            'support_terms' => 'Straight/75%',
        ]);

        $client = $this->login('blnonsupport');

        $client->request('POST', '/roster/' . $unitId . '/battlefield-lose');
        $this->assertResponseRedirects('/roster');

        $crawler = $client->followRedirect();
        $this->assertFlashMessage($crawler, 'Your active contract does not include Battle support. Battlefield loss is not available.');

        // Verify unit still exists
        $conn = $client->getContainer()->get('doctrine')->getManager()->getConnection();
        $unitCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM unit WHERE company_id = ?',
            [$companyId]
        );
        $this->assertEquals(1, $unitCount);
    }

    /**
     * Test that battlefield loss fails when support terms is "None".
     */
    public function testBattlefieldLossFailsWithNoneSupport(): void
    {
        $ref = $this->seedUserAndCompany('blnone', 'None Support Co', 'Inner Sphere');
        $companyId = $ref['companyId'];

        $unitId = $this->seedUnit($companyId, 'Mech', 'Mech', 50, 200, 'mech', 'none');

        // Create an active contract with None support
        $this->seedContract($companyId, [
            'status' => 'active',
            'support_terms' => 'None',
        ]);

        $client = $this->login('blnone');

        $client->request('POST', '/roster/' . $unitId . '/battlefield-lose');
        $this->assertResponseRedirects('/roster');

        $crawler = $client->followRedirect();
        $this->assertFlashMessage($crawler, 'Your active contract does not include Battle support. Battlefield loss is not available.');

        // Verify unit still exists
        $conn = $client->getContainer()->get('doctrine')->getManager()->getConnection();
        $unitCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM unit WHERE company_id = ?',
            [$companyId]
        );
        $this->assertEquals(1, $unitCount);
    }

    /**
     * Test that battlefield loss works with different Battle support percentages.
     */
    public function testBattlefieldLossWithDifferentPercentages(): void
    {
        $ref = $this->seedUserAndCompany('blpercent', 'Percent Co', 'Inner Sphere');
        $companyId = $ref['companyId'];

        // Unit with BV 300, contract Battle/33% → floor(300 * 33 / 100) = 99
        $unitId = $this->seedUnit($companyId, 'Mech', 'Mech', 50, 300, 'mech', 'none');

        $this->seedContract($companyId, [
            'status' => 'active',
            'support_terms' => 'Battle/33%',
        ]);

        $client = $this->login('blpercent');

        $client->request('POST', '/roster/' . $unitId . '/battlefield-lose');
        $this->assertResponseRedirects('/roster');

        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Verify support points: floor(300 * 33 / 100) = 99
        $conn = $client->getContainer()->get('doctrine')->getManager()->getConnection();
        $finalBalance = (int) $conn->fetchOne(
            'SELECT COALESCE(SUM(amount), 0) FROM support_point_entry WHERE company_id = ?',
            [$companyId]
        );
        $this->assertEquals(99, $finalBalance, 'Expected 99 support points (floor(300 * 33 / 100))');
    }

    /**
     * Test that battlefield loss fails when BV is too low for the percentage.
     */
    public function testBattlefieldLossFailsWhenBvTooLow(): void
    {
        $ref = $this->seedUserAndCompany('bllowbv', 'Low BV Co', 'Inner Sphere');
        $companyId = $ref['companyId'];

        // Unit with BV 10, contract Battle/50% → floor(10 * 50 / 100) = 5, which is > 0
        // Let's use BV 1, Battle/50% → floor(1 * 50 / 100) = 0
        $unitId = $this->seedUnit($companyId, 'Tiny Mech', 'Tiny Mech', 5, 1, 'mech', 'none');

        $this->seedContract($companyId, [
            'status' => 'active',
            'support_terms' => 'Battle/50%',
        ]);

        $client = $this->login('bllowbv');

        $client->request('POST', '/roster/' . $unitId . '/battlefield-lose');
        $this->assertResponseRedirects('/roster');

        $crawler = $client->followRedirect();
        $this->assertFlashMessage($crawler, 'Unit BV is too low for the current Battle support percentage.');

        // Verify unit still exists
        $conn = $client->getContainer()->get('doctrine')->getManager()->getConnection();
        $unitCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM unit WHERE company_id = ?',
            [$companyId]
        );
        $this->assertEquals(1, $unitCount);
    }

    /**
     * Test that battlefield loss fails for a unit not owned by the requesting company.
     */
    public function testBattlefieldLossFailsForForeignUnit(): void
    {
        // Create two companies
        $ref1 = $this->seedUserAndCompany('blown', 'Own Co', 'Inner Sphere');
        $ref2 = $this->seedUserAndCompany('blforeign', 'Foreign Co', 'Inner Sphere');

        $unitId = $this->seedUnit($ref1['companyId'], 'Enemy Mech', 'Mech', 50, 200, 'mech', 'none');

        // Try to mark as battlefield loss as the foreign company
        $client = $this->login('blforeign');

        $client->request('POST', '/roster/' . $unitId . '/battlefield-lose');
        $this->assertResponseRedirects('/roster');

        $crawler = $client->followRedirect();
        $this->assertFlashMessage($crawler, 'You do not own this unit.');

        // Verify unit still exists in original company
        $conn = $client->getContainer()->get('doctrine')->getManager()->getConnection();
        $unitCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM unit WHERE company_id = ?',
            [$ref1['companyId']]
        );
        $this->assertEquals(1, $unitCount);
    }

    /**
     * Test that the Battlefield Loss button appears in the roster index.
     */
    public function testBattlefieldLossButtonAppearsInRosterIndex(): void
    {
        $ref = $this->seedUserAndCompany('blbutton', 'Button Co', 'Inner Sphere');
        $this->seedUnit($ref['companyId'], 'Test Mech', 'Mech', 50, 200, 'mech', 'none');

        $client = $this->login('blbutton');
        $crawler = $client->request('GET', '/roster');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Battlefield Loss', $crawler->filter('body')->text());
    }
}
