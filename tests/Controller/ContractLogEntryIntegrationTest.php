<?php

namespace App\Tests\Controller;

use App\Entity\Contract;
use App\Entity\ContractLogEntry;
use App\Entity\MercenaryCompany;
use App\Entity\SupportPointEntry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContractLogEntryIntegrationTest extends WebTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);

        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->em);
        try {
            $schemaTool->dropSchema($metadata);
        } catch (\Throwable $e) {
            // Ignore errors during drop
        }
        $schemaTool->createSchema($metadata);
    }

    private function createUserAndCompany(string $username, string $companyName, string $faction): array
    {
        $conn = $this->em->getConnection();

        $existingId = $conn->fetchOne(
            'SELECT id FROM "user" WHERE username = ?',
            [$username]
        );

        if ($existingId) {
            return ['userId' => (int) $existingId, 'companyId' => $this->getCompanyIdForUser($username)];
        }

        $hash = password_hash('testpassword', PASSWORD_BCRYPT, ['cost' => 4]);

        $conn->insert('user', [
            'username' => $username,
            'email' => strtolower($username) . '@test.com',
            'password' => $hash,
            'roles' => '["ROLE_USER"]',
        ]);

        $userId = (int) $conn->lastInsertId();

        $conn->insert('mercenary_company', [
            'user_id' => $userId,
            'name' => $companyName,
            'faction' => $faction,
            'reputation' => 1,
        ]);

        $companyId = (int) $conn->lastInsertId();

        return ['userId' => $userId, 'companyId' => $companyId];
    }

    private function getCompanyIdForUser(string $username): int
    {
        return (int) $this->em->getConnection()->fetchOne(
            'SELECT id FROM mercenary_company WHERE user_id = (SELECT id FROM "user" WHERE username = ?)',
            [$username]
        )['id'];
    }

    /**
     * Create a contract using the entity API (same pattern as FullStackIntegrationTest).
     */
    private function createContractEntity(string $name, string $type, int $companyId): Contract
    {
        $contract = new Contract();
        $contract->setType(constant(\App\Enum\ContractType::class . '::' . $type));
        $contract->setEmployer('Test Employer');
        $contract->setEmployerAffiliation('Test Affiliation');
        $contract->setScale(1);
        $contract->setDurationMonths(6);
        $contract->setBasePayPercent(50);
        $contract->setCommandRights(\App\Enum\CommandRights::Liaison);
        $contract->setSupportTerms('None');
        $contract->setSalvageRights('Exchange');
        $contract->setTransportTerms('—');
        $contract->setNumberOfTracks(1);
        $contract->setName($name);
        $contract->setPlanet('Test Planet');
        $contract->setIntensity('Low');
        $contract->setCompany($this->em->getRepository(MercenaryCompany::class)->find($companyId));

        $this->em->persist($contract);
        $this->em->flush();

        return $contract;
    }

    // ── ContractLogEntry + SupportPointEntry cascade deletion tests ────────

    public function testDeleteLogEntryWithSupportPointRemovesBothFromDatabase(): void
    {
        $userRef = $this->createUserAndCompany('sp_cascade_owner', 'Cascade Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $contract = $this->createContractEntity('Cascade Test Contract', 'Raid', $companyId);
        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        // Create a SupportPointEntry first
        $spEntry = (new SupportPointEntry())
            ->setCompany($company)
            ->setAmount(-500)
            ->setDescription('Test SP entry');
        $this->em->persist($spEntry);
        $this->em->flush();

        $spId = $spEntry->getId();

        // Create a ContractLogEntry referencing that SupportPointEntry
        $logEntry = (new ContractLogEntry())
            ->setContract($contract)
            ->setMonth(1)
            ->setEntryType(\App\Enum\ContractLogEntryType::BasePay)
            ->setDescription('Base pay received: -500 SP')
            ->setSupportPointEntry($spEntry);
        $this->em->persist($logEntry);
        $this->em->flush();

        $logId = $logEntry->getId();

        // Now delete the log entry
        $entryToDelete = $this->em->getRepository(ContractLogEntry::class)->find($logId);
        $this->assertNotNull($entryToDelete);
        $this->assertNotNull($entryToDelete->getSupportPointEntry());

        // Delete via the service (this is what the controller calls)
        $logService = static::getContainer()->get(\App\Service\ContractLogService::class);
        $logService->deleteEntry($contract, $entryToDelete);

        // Verify the ContractLogEntry is gone
        $deletedLog = $this->em->getRepository(ContractLogEntry::class)->find($logId);
        $this->assertNull($deletedLog, 'Expected ContractLogEntry to be deleted');

        // Verify the SupportPointEntry is also gone (this is the cascade behavior)
        $deletedSP = $this->em->getRepository(SupportPointEntry::class)->find($spId);
        $this->assertNull($deletedSP, 'Expected SupportPointEntry to be deleted when ContractLogEntry is deleted');

        // Verify no support point entries remain for this company
        $remainingSP = $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) as cnt FROM support_point_entry WHERE company_id = ?',
            [$companyId]
        );
        $this->assertEquals(0, (int) $remainingSP['cnt'], 'Expected no support point entries remaining');
    }

    public function testDeleteLogEntryWithoutSupportPointDoesNotAffectOtherSPEntries(): void
    {
        $userRef = $this->createUserAndCompany('sp_no_cascade_owner', 'NoCascade Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $contract = $this->createContractEntity('NoCascade Test Contract', 'Raid', $companyId);
        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        // Create a standalone SupportPointEntry (not linked to any log entry)
        $standaloneSP = (new SupportPointEntry())
            ->setCompany($company)
            ->setAmount(1000)
            ->setDescription('Standalone SP entry');
        $this->em->persist($standaloneSP);
        $this->em->flush();

        $standaloneSPId = $standaloneSP->getId();

        // Create a ContractLogEntry WITHOUT a SupportPointEntry
        $logEntry = (new ContractLogEntry())
            ->setContract($contract)
            ->setMonth(1)
            ->setEntryType(\App\Enum\ContractLogEntryType::Downtime)
            ->setDescription('Downtime — waiting for orders');
        $this->em->persist($logEntry);
        $this->em->flush();

        $logId = $logEntry->getId();

        // Delete the log entry
        $entryToDelete = $this->em->getRepository(ContractLogEntry::class)->find($logId);
        $this->assertNotNull($entryToDelete);
        $this->assertNull($entryToDelete->getSupportPointEntry());

        $logService = static::getContainer()->get(\App\Service\ContractLogService::class);
        $logService->deleteEntry($contract, $entryToDelete);

        // Verify the ContractLogEntry is gone
        $deletedLog = $this->em->getRepository(ContractLogEntry::class)->find($logId);
        $this->assertNull($deletedLog, 'Expected ContractLogEntry to be deleted');

        // Verify the standalone SupportPointEntry is STILL there (not affected)
        $remainingSP = $this->em->getRepository(SupportPointEntry::class)->find($standaloneSPId);
        $this->assertNotNull($remainingSP, 'Expected standalone SupportPointEntry to survive');
        $this->assertEquals(1000, $remainingSP->getAmount());
        $this->assertEquals('Standalone SP entry', $remainingSP->getDescription());
    }

    public function testDeleteLogEntryWithPositiveSPRemovesSupportPoint(): void
    {
        $userRef = $this->createUserAndCompany('sp_positive_owner', 'PositiveSP Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $contract = $this->createContractEntity('PositiveSP Test Contract', 'Raid', $companyId);
        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        // Create a SupportPointEntry with a positive amount (e.g., base pay reward)
        $spEntry = (new SupportPointEntry())
            ->setCompany($company)
            ->setAmount(500)
            ->setDescription('Positive SP reward');
        $this->em->persist($spEntry);
        $this->em->flush();

        $spId = $spEntry->getId();

        // Create a ContractLogEntry referencing that SupportPointEntry
        $logEntry = (new ContractLogEntry())
            ->setContract($contract)
            ->setMonth(1)
            ->setEntryType(\App\Enum\ContractLogEntryType::BasePay)
            ->setDescription('Base pay received: 500 SP')
            ->setSupportPointEntry($spEntry);
        $this->em->persist($logEntry);
        $this->em->flush();

        $logId = $logEntry->getId();

        // Delete via the service
        $entryToDelete = $this->em->getRepository(ContractLogEntry::class)->find($logId);
        $this->assertNotNull($entryToDelete);

        $logService = static::getContainer()->get(\App\Service\ContractLogService::class);
        $logService->deleteEntry($contract, $entryToDelete);

        // Verify both are gone
        $deletedLog = $this->em->getRepository(ContractLogEntry::class)->find($logId);
        $this->assertNull($deletedLog, 'Expected ContractLogEntry to be deleted');

        $deletedSP = $this->em->getRepository(SupportPointEntry::class)->find($spId);
        $this->assertNull($deletedSP, 'Expected SupportPointEntry to be deleted when ContractLogEntry is deleted');
    }

    public function testDeleteMultipleLogEntriesWithSupportPointsRemovesAllSP(): void
    {
        $userRef = $this->createUserAndCompany('sp_multi_owner', 'MultiSP Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $contract = $this->createContractEntity('MultiSP Test Contract', 'Raid', $companyId);
        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        $spIds = [];
        $logIds = [];

        // Create 3 ContractLogEntry records, each with its own SupportPointEntry
        for ($i = 1; $i <= 3; $i++) {
            $spEntry = (new SupportPointEntry())
                ->setCompany($company)
                ->setAmount(-($i * 100))
                ->setDescription("SP entry $i");
            $this->em->persist($spEntry);
            $this->em->flush();

            $spIds[] = $spEntry->getId();

            $maintenanceAmount = $i * 100;
            $logEntry = (new ContractLogEntry())
                ->setContract($contract)
                ->setMonth($i)
                ->setEntryType(\App\Enum\ContractLogEntryType::Maintenance)
                ->setDescription("Maintenance deducted: $maintenanceAmount SP")
                ->setSupportPointEntry($spEntry);
            $this->em->persist($logEntry);
            $this->em->flush();

            $logIds[] = $logEntry->getId();
        }

        // Count SP entries before deletion
        $beforeCount = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) as cnt FROM support_point_entry WHERE company_id = ?',
            [$companyId]
        );
        $this->assertEquals(3, $beforeCount, 'Expected 3 support point entries before deletion');

        // Delete all log entries one by one
        $logService = static::getContainer()->get(\App\Service\ContractLogService::class);
        foreach ($logIds as $logId) {
            $entryToDelete = $this->em->getRepository(ContractLogEntry::class)->find($logId);
            $this->assertNotNull($entryToDelete);
            $logService->deleteEntry($contract, $entryToDelete);
        }

        // Verify all SupportPointEntries are gone
        $afterCount = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) as cnt FROM support_point_entry WHERE company_id = ?',
            [$companyId]
        );
        $this->assertEquals(0, $afterCount, 'Expected 0 support point entries after deleting all log entries');

        // Verify all ContractLogEntries are gone
        foreach ($logIds as $logId) {
            $deletedLog = $this->em->getRepository(ContractLogEntry::class)->find($logId);
            $this->assertNull($deletedLog, 'Expected ContractLogEntry ' . $logId . ' to be deleted');
        }
    }

    public function testDeleteLogEntryWithMixedSPEntriesRemovesOnlyLinked(): void
    {
        $userRef = $this->createUserAndCompany('sp_mixed_owner', 'MixedSP Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $contract = $this->createContractEntity('MixedSP Test Contract', 'Raid', $companyId);
        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        // Create a standalone SupportPointEntry (not linked to any log entry)
        $standaloneSP = (new SupportPointEntry())
            ->setCompany($company)
            ->setAmount(2000)
            ->setDescription('Standalone entry');
        $this->em->persist($standaloneSP);
        $this->em->flush();

        $standaloneSPId = $standaloneSP->getId();

        // Create a SupportPointEntry linked to a ContractLogEntry
        $linkedSP = (new SupportPointEntry())
            ->setCompany($company)
            ->setAmount(-300)
            ->setDescription('Linked SP entry');
        $this->em->persist($linkedSP);
        $this->em->flush();

        $linkedSPId = $linkedSP->getId();

        $logEntry = (new ContractLogEntry())
            ->setContract($contract)
            ->setMonth(1)
            ->setEntryType(\App\Enum\ContractLogEntryType::Transport)
            ->setDescription('Transport: 300 SP')
            ->setSupportPointEntry($linkedSP);
        $this->em->persist($logEntry);
        $this->em->flush();

        $logId = $logEntry->getId();

        // Delete the log entry
        $entryToDelete = $this->em->getRepository(ContractLogEntry::class)->find($logId);
        $this->assertNotNull($entryToDelete);

        $logService = static::getContainer()->get(\App\Service\ContractLogService::class);
        $logService->deleteEntry($contract, $entryToDelete);

        // Verify the ContractLogEntry is gone
        $deletedLog = $this->em->getRepository(ContractLogEntry::class)->find($logId);
        $this->assertNull($deletedLog, 'Expected ContractLogEntry to be deleted');

        // Verify the linked SupportPointEntry is gone
        $deletedLinkedSP = $this->em->getRepository(SupportPointEntry::class)->find($linkedSPId);
        $this->assertNull($deletedLinkedSP, 'Expected linked SupportPointEntry to be deleted');

        // Verify the standalone SupportPointEntry is STILL there
        $remainingSP = $this->em->getRepository(SupportPointEntry::class)->find($standaloneSPId);
        $this->assertNotNull($remainingSP, 'Expected standalone SupportPointEntry to survive');
        $this->assertEquals(2000, $remainingSP->getAmount());
        $this->assertEquals('Standalone entry', $remainingSP->getDescription());
    }
}
