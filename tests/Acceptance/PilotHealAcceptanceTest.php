<?php

namespace App\Tests\Acceptance;

use Doctrine\ORM\EntityManagerInterface;

class PilotHealAcceptanceTest extends AcceptanceTestCase
{
    private EntityManagerInterface $em;
    
    protected function tearDown(): void
    {
        // Clean up contracts from all companies to ensure test isolation
        $conn = $this->getEm()->getConnection();
        $conn->executeStatement('DELETE FROM support_point_entry');
        $conn->executeStatement('DELETE FROM pilot');
        $conn->executeStatement('DELETE FROM contract');
        parent::tearDown();
    }

    // ── Helper Methods ────────────────────────────────────────────────────

    /**
     * Get the EntityManager from the shared state (initialized by seedUserAndCompany).
     */
    private function getEm(): EntityManagerInterface
    {
        if (self::$sharedEm === null) {
            // Trigger initialization by seeding a dummy user
            $this->seedUserAndCompany('_init', '_init', '_init');
        }
        return self::$sharedEm;
    }

    private function seedWoundedPilot(int $companyId, string $name = 'Wounded Pilot'): int
    {
        $conn = $this->getEm()->getConnection();
        $conn->insert('pilot', [
            'company_id' => $companyId,
            'name' => $name,
            'is_named' => 0,
            'gunnery' => 4,
            'piloting' => 5,
            'gunnery_xp' => 0,
            'piloting_xp' => 0,
            'wounded' => 1,
        ]);
        return (int) $conn->lastInsertId();
    }

    private function getCurrentSupportPoints(int $companyId): int
    {
        // Use direct PDO to bypass Doctrine's transaction isolation.
        // In acceptance tests, the controller's EM may be in a different
        // transaction than the test's EM, causing visibility issues.
        $dbPath = dirname(__DIR__, 2) . '/var/test_test.db';
        $pdo = new \PDO('sqlite:' . $dbPath);
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM support_point_entry WHERE company_id = ?'
        );
        $stmt->execute([$companyId]);
        return (int) $stmt->fetchColumn();
    }

    // ── Heal with No Active Contract ──────────────────────────────────────

    public function testHealWithNoContractDeductsFullCost(): void
    {
        $ref = $this->seedUserAndCompany('healNoContract', 'No Contract Co', 'Inner Sphere');
        $companyId = $ref['companyId'];

        // Seed support points (enough to heal)
        $this->seedSupportPoints($companyId, 100, 'Initial funds');

        // Seed a wounded pilot
        $pilotId = $this->seedWoundedPilot($companyId, 'Wounded Without Contract');

        // Get initial SP balance
        $initialSp = $this->getCurrentSupportPoints($companyId);

        // Heal the pilot
        $client = $this->login('healNoContract');
        $client->request('POST', '/pilots/' . $pilotId . '/heal');

        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();

        // Verify flash message
        $this->assertFlashMessage($crawler, 'Pilot healed.');

        // Verify SP was deducted (30 SP base cost)
        $finalSp = $this->getCurrentSupportPoints($companyId);
        $this->assertEquals($initialSp - 30, $finalSp, 'Expected 30 SP deduction for heal with no contract');

        // Verify pilot is no longer wounded
        $conn = $this->getEm()->getConnection();
        $pilot = $conn->fetchOne('SELECT wounded FROM pilot WHERE id = ?', [$pilotId]);
        $this->assertFalse((bool) $pilot['wounded'], 'Pilot should be healed (wounded = false)');
    }

    public function testHealWithNoContractFailsOnInsufficientSP(): void
    {
        $ref = $this->seedUserAndCompany('healInsufficient', 'Insufficient SP Co', 'Liao');
        $companyId = $ref['companyId'];

        // Seed insufficient support points (only 15 SP, heal costs 30)
        $this->seedSupportPoints($companyId, 15, 'Low funds');

        // Seed a wounded pilot
        $pilotId = $this->seedWoundedPilot($companyId, 'Cannot Heal');

        // Try to heal
        $client = $this->login('healInsufficient');
        $client->request('POST', '/pilots/' . $pilotId . '/heal');

        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();

        // Verify error flash message
        $this->assertStringContainsString(
            'Insufficient support points',
            $crawler->filter('div.alert.alert-danger')->first()->html(),
            'Expected insufficient SP error message'
        );

        // Verify pilot is still wounded (use direct PDO to bypass transaction isolation)
        $dbPath = dirname(__DIR__, 2) . '/var/test_test.db';
        $pdo = new \PDO('sqlite:' . $dbPath);
        $stmt = $pdo->prepare('SELECT wounded FROM pilot WHERE id = ?');
        $stmt->execute([$pilotId]);
        $wounded = (bool) $stmt->fetchColumn();
        $this->assertTrue($wounded, 'Pilot should still be wounded after failed heal');
    }

    // ── Heal with Battle Support (0 SP) ────────────────────────────────────

    public function testHealWithBattleSupportDeductsZeroSP(): void
    {
        $ref = $this->seedUserAndCompany('healBattle', 'Battle Support Co', 'Inner Sphere');
        $companyId = $ref['companyId'];

        // Seed support points
        $this->seedSupportPoints($companyId, 100, 'Initial funds');

        // Seed a wounded pilot
        $pilotId = $this->seedWoundedPilot($companyId, 'Battle Support Pilot');

        // Seed an active contract with Battle support (0 cost)
        $this->seedContract($companyId, [
            'status' => 'active',
            'support_terms' => 'Battle/100%',
            'type' => 'expedition',
            'employer' => 'Battle Client',
        ]);

        $initialSp = $this->getCurrentSupportPoints($companyId);

        // Heal the pilot
        $client = $this->login('healBattle');
        $client->request('POST', '/pilots/' . $pilotId . '/heal');

        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();

        // Verify flash message
        $this->assertFlashMessage($crawler, 'Pilot healed.');

        // Verify NO SP was deducted (Battle support = 0 cost)
        $finalSp = $this->getCurrentSupportPoints($companyId);
        $this->assertEquals($initialSp, $finalSp, 'Expected 0 SP deduction for Battle support heal');

        // Verify pilot is no longer wounded
        $conn = $this->getEm()->getConnection();
        $pilot = $conn->fetchOne('SELECT wounded FROM pilot WHERE id = ?', [$pilotId]);
        $this->assertFalse((bool) $pilot['wounded'], 'Pilot should be healed with Battle support');
    }

    public function testHealWithBattleSupportWorksEvenWithZeroSP(): void
    {
        $ref = $this->seedUserAndCompany('healBattleZero', 'Zero SP Battle Co', 'ComStar');
        $companyId = $ref['companyId'];

        // No support points at all
        // Seed a wounded pilot
        $pilotId = $this->seedWoundedPilot($companyId, 'Zero SP Battle Pilot');

        // Seed an active contract with Battle support
        $this->seedContract($companyId, [
            'status' => 'active',
            'support_terms' => 'Battle/100%',
            'type' => 'expedition',
            'employer' => 'Battle Client',
        ]);

        // Heal should succeed even with 0 SP (Battle support = free)
        $client = $this->login('healBattleZero');
        $client->request('POST', '/pilots/' . $pilotId . '/heal');

        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();

        // Verify success
        $this->assertFlashMessage($crawler, 'Pilot healed.');

        // Verify pilot is healed
        $conn = $this->getEm()->getConnection();
        $pilot = $conn->fetchOne('SELECT wounded FROM pilot WHERE id = ?', [$pilotId]);
        $this->assertFalse((bool) $pilot['wounded'], 'Pilot should be healed with Battle support even with 0 SP');
    }

    // ── Heal with Straight Support (percentage-based) ──────────────────────

    public function testHealWithStraight50PctSupportDeductsHalfCost(): void
    {
        $ref = $this->seedUserAndCompany('healStraight50', 'Straight 50% Co', 'Davion');
        $companyId = $ref['companyId'];

        // Seed support points (enough for 15 SP heal)
        $this->seedSupportPoints($companyId, 50, 'Initial funds');

        // Seed a wounded pilot
        $pilotId = $this->seedWoundedPilot($companyId, 'Straight 50% Pilot');

        // Seed an active contract with Straight/50% support
        $this->seedContract($companyId, [
            'status' => 'active',
            'support_terms' => 'Straight/50%',
            'type' => 'expedition',
            'employer' => 'Straight Client',
        ]);

        $initialSp = $this->getCurrentSupportPoints($companyId);

        // Heal the pilot
        $client = $this->login('healStraight50');
        $client->request('POST', '/pilots/' . $pilotId . '/heal');

        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();

        // Verify flash message
        $this->assertFlashMessage($crawler, 'Pilot healed.');

        // Verify SP was deducted (30 * (1 - 50/100) = 15 SP)
        $finalSp = $this->getCurrentSupportPoints($companyId);
        $this->assertEquals($initialSp - 15, $finalSp, 'Expected 15 SP deduction for Straight/50% support');

        // Verify pilot is no longer wounded
        $conn = $this->getEm()->getConnection();
        $pilot = $conn->fetchOne('SELECT wounded FROM pilot WHERE id = ?', [$pilotId]);
        $this->assertFalse((bool) $pilot['wounded'], 'Pilot should be healed with Straight/50% support');
    }

    public function testHealWithStraight75PctSupportDeductsQuarterCost(): void
    {
        $ref = $this->seedUserAndCompany('healStraight75', 'Straight 75% Co', 'Lyrans');
        $companyId = $ref['companyId'];

        $this->seedSupportPoints($companyId, 100, 'Initial funds');
        $pilotId = $this->seedWoundedPilot($companyId, 'Straight 75% Pilot');

        // Seed an active contract with Straight/75% support
        $this->seedContract($companyId, [
            'status' => 'active',
            'support_terms' => 'Straight/75%',
            'type' => 'expedition',
            'employer' => 'Straight Client',
        ]);

        $initialSp = $this->getCurrentSupportPoints($companyId);

        // Heal the pilot
        $client = $this->login('healStraight75');
        $client->request('POST', '/pilots/' . $pilotId . '/heal');

        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();

        // Verify flash message
        $this->assertFlashMessage($crawler, 'Pilot healed.');

        // Verify SP was deducted (30 * (1 - 75/100) = 7 SP, floor)
        $finalSp = $this->getCurrentSupportPoints($companyId);
        $this->assertEquals($initialSp - 7, $finalSp, 'Expected 7 SP deduction for Straight/75% support (floor)');
    }

    public function testHealWithStraight100PctSupportDeductsZeroSP(): void
    {
        $ref = $this->seedUserAndCompany('healStraight100', 'Straight 100% Co', 'ComGuard');
        $companyId = $ref['companyId'];

        $this->seedSupportPoints($companyId, 100, 'Initial funds');
        $pilotId = $this->seedWoundedPilot($companyId, 'Straight 100% Pilot');

        // Seed an active contract with Straight/100% support
        $this->seedContract($companyId, [
            'status' => 'active',
            'support_terms' => 'Straight/100%',
            'type' => 'expedition',
            'employer' => 'Straight Client',
        ]);

        $initialSp = $this->getCurrentSupportPoints($companyId);

        // Heal the pilot
        $client = $this->login('healStraight100');
        $client->request('POST', '/pilots/' . $pilotId . '/heal');

        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();

        // Verify flash message
        $this->assertFlashMessage($crawler, 'Pilot healed.');

        // Verify NO SP was deducted (Straight/100% = 0 cost)
        $finalSp = $this->getCurrentSupportPoints($companyId);
        $this->assertEquals($initialSp, $finalSp, 'Expected 0 SP deduction for Straight/100% support');
    }

    public function testHealWithStraightSupportFailsOnInsufficientSP(): void
    {
        $ref = $this->seedUserAndCompany('healStraightInsufficient', 'Straight Insufficient Co', 'Word of Blake');
        $companyId = $ref['companyId'];

        // Seed only 5 SP (Straight/50% needs 15 SP)
        $this->seedSupportPoints($companyId, 5, 'Low funds');
        $pilotId = $this->seedWoundedPilot($companyId, 'Straight Insufficient Pilot');

        // Seed an active contract with Straight/50% support (needs 15 SP, has 5)
        $this->seedContract($companyId, [
            'status' => 'active',
            'support_terms' => 'Straight/50%',
            'type' => 'expedition',
            'employer' => 'Straight Client',
        ]);

        // Try to heal
        $client = $this->login('healStraightInsufficient');
        $client->request('POST', '/pilots/' . $pilotId . '/heal');

        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();

        // Verify error flash message
        $this->assertStringContainsString(
            'Insufficient support points',
            $crawler->filter('div.alert.alert-danger')->first()->html(),
            'Expected insufficient SP error message for Straight support'
        );

        // Verify pilot is still wounded (use direct PDO to bypass transaction isolation)
        $dbPath = dirname(__DIR__, 2) . '/var/test_test.db';
        $pdo = new \PDO('sqlite:' . $dbPath);
        $stmt = $pdo->prepare('SELECT wounded FROM pilot WHERE id = ?');
        $stmt->execute([$pilotId]);
        $wounded = (bool) $stmt->fetchColumn();
        $this->assertTrue($wounded, 'Pilot should still be wounded after failed Straight support heal');
    }

    // ── Heal with Accepted (not yet Active) Contract ───────────────────────

    public function testHealWithAcceptedContractAppliesSupportTerms(): void
    {
        $ref = $this->seedUserAndCompany('healAccepted', 'Accepted Contract Co', 'Covenant');
        $companyId = $ref['companyId'];

        $this->seedSupportPoints($companyId, 50, 'Initial funds');
        $pilotId = $this->seedWoundedPilot($companyId, 'Accepted Contract Pilot');

        // Seed an accepted (not yet active) contract with Straight/60% support
        $this->seedContract($companyId, [
            'status' => 'accepted',
            'support_terms' => 'Straight/60%',
            'type' => 'expedition',
            'employer' => 'Accepted Client',
        ]);

        $initialSp = $this->getCurrentSupportPoints($companyId);

        // Heal the pilot
        $client = $this->login('healAccepted');
        $client->request('POST', '/pilots/' . $pilotId . '/heal');

        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();

        // Verify flash message
        $this->assertFlashMessage($crawler, 'Pilot healed.');

        // Verify SP was deducted (30 * (1 - 60/100) = 12 SP)
        $finalSp = $this->getCurrentSupportPoints($companyId);
        $this->assertEquals($initialSp - 12, $finalSp, 'Expected 12 SP deduction for Straight/60% accepted contract');
    }

    // ── Heal Edge Cases ────────────────────────────────────────────────────

    public function testHealAlreadyHealedPilotSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('healAlreadyHealed', 'Already Healed Co', 'Clan');
        $companyId = $ref['companyId'];

        $this->seedSupportPoints($companyId, 100, 'Initial funds');

        // Seed a pilot who is NOT wounded
        $conn = $this->getEm()->getConnection();
        $conn->insert('pilot', [
            'company_id' => $companyId,
            'name' => 'Already Healed',
            'is_named' => 0,
            'gunnery' => 4,
            'piloting' => 5,
            'gunnery_xp' => 0,
            'piloting_xp' => 0,
            'wounded' => 0,
        ]);
        $pilotId = (int) $conn->lastInsertId();

        // Try to heal (idempotent - should succeed even if not wounded)
        $client = $this->login('healAlreadyHealed');
        $client->request('POST', '/pilots/' . $pilotId . '/heal');

        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();

        // Verify success (healing already-healed pilot is fine)
        $this->assertFlashMessage($crawler, 'Pilot healed.');

        // Verify pilot is still not wounded
        $pilot = $conn->fetchOne('SELECT wounded FROM pilot WHERE id = ?', [$pilotId]);
        $this->assertFalse((bool) $pilot['wounded'], 'Pilot should remain not wounded');
    }

    public function testHealWithNoSupportTermsDeductsFullCost(): void
    {
        $ref = $this->seedUserAndCompany('healNoSupportTerms', 'No Support Terms Co', 'Free Worlds');
        $companyId = $ref['companyId'];

        $this->seedSupportPoints($companyId, 100, 'Initial funds');
        $pilotId = $this->seedWoundedPilot($companyId, 'No Support Terms Pilot');

        // Seed an active contract with "None" support terms (no benefit)
        $this->seedContract($companyId, [
            'status' => 'active',
            'support_terms' => 'None',
            'type' => 'expedition',
            'employer' => 'None Client',
        ]);

        $initialSp = $this->getCurrentSupportPoints($companyId);

        // Heal the pilot
        $client = $this->login('healNoSupportTerms');
        $client->request('POST', '/pilots/' . $pilotId . '/heal');

        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();

        // Verify flash message
        $this->assertFlashMessage($crawler, 'Pilot healed.');

        // Verify SP was deducted (30 SP base cost, no support benefit)
        $finalSp = $this->getCurrentSupportPoints($companyId);
        $this->assertEquals($initialSp - 30, $finalSp, 'Expected 30 SP deduction for "None" support terms');
    }

    public function testHealWithCompletedContractUsesBaseCost(): void
    {
        $ref = $this->seedUserAndCompany('healCompleted', 'Completed Contract Co', 'Liao');
        $companyId = $ref['companyId'];

        $this->seedSupportPoints($companyId, 100, 'Initial funds');
        $pilotId = $this->seedWoundedPilot($companyId, 'Completed Contract Pilot');

        // Seed a completed contract (not active, so no support benefit)
        $this->seedContract($companyId, [
            'status' => 'completed',
            'support_terms' => 'Straight/50%',
            'type' => 'expedition',
            'employer' => 'Completed Client',
        ]);

        $initialSp = $this->getCurrentSupportPoints($companyId);

        // Heal the pilot
        $client = $this->login('healCompleted');
        $client->request('POST', '/pilots/' . $pilotId . '/heal');

        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();

        // Verify flash message
        $this->assertFlashMessage($crawler, 'Pilot healed.');

        // Verify SP was deducted (30 SP base cost, completed contract ignored)
        $finalSp = $this->getCurrentSupportPoints($companyId);
        $this->assertEquals($initialSp - 30, $finalSp, 'Expected 30 SP deduction when contract is completed');
    }

    public function testHealWithMultipleActiveContractsUsesMostRecent(): void
    {
        $ref = $this->seedUserAndCompany('healMultipleContracts', 'Multiple Contracts Co', 'Davion');
        $companyId = $ref['companyId'];

        $this->seedSupportPoints($companyId, 100, 'Initial funds');
        $pilotId = $this->seedWoundedPilot($companyId, 'Multiple Contracts Pilot');

        // Seed two active contracts (older one with Straight/50%, newer one with Battle/100%)
        $this->seedContract($companyId, [
            'status' => 'active',
            'support_terms' => 'Straight/50%',
            'type' => 'expedition',
            'employer' => 'Old Client',
            'created_at' => '2024-01-01 00:00:00',
        ]);

        $this->seedContract($companyId, [
            'status' => 'active',
            'support_terms' => 'Battle/100%',
            'type' => 'expedition',
            'employer' => 'New Client',
            'created_at' => '2024-12-31 23:59:59',
        ]);

        $initialSp = $this->getCurrentSupportPoints($companyId);

        // Heal the pilot (should use most recent active contract = Battle/100% = 0 cost)
        $client = $this->login('healMultipleContracts');
        $client->request('POST', '/pilots/' . $pilotId . '/heal');

        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();

        // Verify flash message
        $this->assertFlashMessage($crawler, 'Pilot healed.');

        // Verify NO SP was deducted (most recent contract is Battle/100% = 0 cost)
        $finalSp = $this->getCurrentSupportPoints($companyId);
        $this->assertEquals($initialSp, $finalSp, 'Expected 0 SP deduction using most recent active contract');
    }

    public function testHealWithBrokenContractUsesBaseCost(): void
    {
        $ref = $this->seedUserAndCompany('healBroken', 'Broken Contract Co', 'ComStar');
        $companyId = $ref['companyId'];

        $this->seedSupportPoints($companyId, 100, 'Initial funds');
        $pilotId = $this->seedWoundedPilot($companyId, 'Broken Contract Pilot');

        // Seed a broken contract (not active, so no support benefit)
        $this->seedContract($companyId, [
            'status' => 'broken',
            'support_terms' => 'Battle/100%',
            'type' => 'expedition',
            'employer' => 'Broken Client',
        ]);

        $initialSp = $this->getCurrentSupportPoints($companyId);

        // Heal the pilot
        $client = $this->login('healBroken');
        $client->request('POST', '/pilots/' . $pilotId . '/heal');

        $this->assertResponseRedirects('/pilots');
        $crawler = $client->followRedirect();

        // Verify flash message
        $this->assertFlashMessage($crawler, 'Pilot healed.');

        // Verify SP was deducted (30 SP base cost, broken contract ignored)
        $finalSp = $this->getCurrentSupportPoints($companyId);
        $this->assertEquals($initialSp - 30, $finalSp, 'Expected 30 SP deduction when contract is broken');
    }
}
