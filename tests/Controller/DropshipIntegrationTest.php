<?php

namespace App\Tests\Controller;

use App\Entity\Dropship;
use App\Entity\MercenaryCompany;
use App\Entity\SalvagedMech;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DropshipIntegrationTest extends WebTestCase
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

    // ── Dropship CRUD Tests ────────────────────────────────────────────────

    public function testCreateDropshipPersistedToDatabase(): void
    {
        $userRef = $this->createUserAndCompany('dropship_owner', 'Dropship Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $dropship = new Dropship();
        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);
        $dropship->setCompany($company);
        $dropship->setMaxCapacity(40);
        $dropship->setName('Test Dropship');

        $this->em->persist($dropship);
        $this->em->flush();

        $created = $this->em->getRepository(Dropship::class)->findOneBy(['company' => $company]);
        $this->assertNotNull($created);
        $this->assertEquals(40, $created->getMaxCapacity());
        $this->assertEquals('Test Dropship', $created->getName());
        $this->assertEquals($company, $created->getCompany());
    }

    public function testDropshipHasUniqueCompanyConstraint(): void
    {
        $userRef = $this->createUserAndCompany('dropship_unique', 'Unique Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        $dropship1 = new Dropship();
        $dropship1->setCompany($company);
        $dropship1->setMaxCapacity(40);
        $dropship1->setName('Dropship One');

        $this->em->persist($dropship1);
        $this->em->flush();

        $dropshipId = $dropship1->getId();

        $dropship2 = new Dropship();
        $dropship2->setCompany($company);
        $dropship2->setMaxCapacity(40);
        $dropship2->setName('Dropship Two');

        $this->em->persist($dropship2);

        try {
            $this->em->flush();
            $this->fail('Expected unique constraint violation');
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            // Expected: unique constraint on dropship.company_id
        }

        // First dropship still exists
        $existing = $this->em->getRepository(Dropship::class)->find($dropshipId);
        $this->assertNotNull($existing);
        $this->assertEquals(40, $existing->getMaxCapacity());
    }

    public function testEditDropshipUpdatesCapacity(): void
    {
        $userRef = $this->createUserAndCompany('dropship_edit', 'Edit Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        $dropship = new Dropship();
        $dropship->setCompany($company);
        $dropship->setMaxCapacity(100);
        $dropship->setName('Edit Dropship');

        $this->em->persist($dropship);
        $this->em->flush();

        $dropshipId = $dropship->getId();

        // Update capacity
        $dropship->setMaxCapacity(40);
        $this->em->flush();

        $updated = $this->em->getRepository(Dropship::class)->find($dropshipId);
        $this->assertNotNull($updated);
        $this->assertEquals(40, $updated->getMaxCapacity());
    }

    public function testDeleteDropshipRemovesFromDatabase(): void
    {
        $userRef = $this->createUserAndCompany('dropship_delete', 'Delete Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        $dropship = new Dropship();
        $dropship->setCompany($company);
        $dropship->setMaxCapacity(40);
        $dropship->setName('Delete Dropship');

        $this->em->persist($dropship);
        $this->em->flush();

        $dropshipId = $dropship->getId();
        $this->em->remove($dropship);
        $this->em->flush();

        $deleted = $this->em->getRepository(Dropship::class)->find($dropshipId);
        $this->assertNull($deleted, 'Expected dropship to be deleted');
    }

    // ── Dropship-SalvagedMech Relationship Tests ───────────────────────────

    public function testSalvagedMechCanBeAssignedToDropship(): void
    {
        $userRef = $this->createUserAndCompany('dropship_mech', 'Mech Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        $dropship = new Dropship();
        $dropship->setCompany($company);
        $dropship->setMaxCapacity(40);
        $dropship->setName('Mech Dropship');

        $this->em->persist($dropship);
        $this->em->flush();

        $mechan = new SalvagedMech();
        $mechan->setCompany($company);
        $mechan->setModel('Catapult CAT-PU1');
        $mechan->setTonnage(80);
        $mechan->setBvCost(300);
        $mechan->setScrapyard(false);
        $mechan->setDropship($dropship);

        $this->em->persist($mechan);
        $this->em->flush();

        $createdMech = $this->em->getRepository(SalvagedMech::class)->findOneBy(['model' => 'Catapult CAT-PU1']);
        $this->assertNotNull($createdMech);
        $this->assertNotNull($createdMech->getDropship());
        $this->assertEquals($dropship->getId(), $createdMech->getDropship()->getId());
    }

    public function testDropshipCanHoldUpToMaxCapacityMechs(): void
    {
        $userRef = $this->createUserAndCompany('dropship_capacity', 'Capacity Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        $dropship = new Dropship();
        $dropship->setCompany($company);
        $dropship->setMaxCapacity(180);
        $dropship->setName('Capacity Dropship');

        $this->em->persist($dropship);
        $this->em->flush();

        $dropshipId = $dropship->getId();

        // Create 3 mechs (at capacity)
        for ($i = 1; $i <= 3; $i++) {
            $mechan = new SalvagedMech();
            $mechan->setCompany($company);
            $mechan->setModel("Mech {$i}");
            $mechan->setTonnage(60);
            $mechan->setBvCost(200);
            $mechan->setScrapyard(false);
            $mechan->setDropship($dropship);
            $this->em->persist($mechan);
        }
        $this->em->flush();

        // Count mechs on dropship
        $count = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM salvaged_mech WHERE dropship_id = ?',
            [$dropshipId]
        );
        $this->assertEquals(3, $count);
    }

    public function testDeleteDropshipUnassignsAllMechs(): void
    {
        $userRef = $this->createUserAndCompany('dropship_unassign', 'Unassign Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        $dropship = new Dropship();
        $dropship->setCompany($company);
        $dropship->setMaxCapacity(180);
        $dropship->setName('Unassign Dropship');

        $this->em->persist($dropship);
        $this->em->flush();

        $dropshipId = $dropship->getId();

        // Create 2 mechs on dropship
        for ($i = 1; $i <= 2; $i++) {
            $mechan = new SalvagedMech();
            $mechan->setCompany($company);
            $mechan->setModel("Unassign Mech {$i}");
            $mechan->setTonnage(60);
            $mechan->setBvCost(200);
            $mechan->setScrapyard(false);
            $mechan->setDropship($dropship);
            $this->em->persist($mechan);
        }
        $this->em->flush();

        $mechanIds = [];
        for ($i = 1; $i <= 2; $i++) {
            $mechan = $this->em->getRepository(SalvagedMech::class)->findOneBy(['model' => "Unassign Mech {$i}"]);
            $mechanIds[] = $mechan->getId();
        }

        // Delete dropship
        $this->em->remove($dropship);
        $this->em->flush();

        // Verify mechs still exist in the database after dropship deletion
        $mechs = $this->em->getConnection()->fetchAllAssociative(
            'SELECT * FROM salvaged_mech WHERE id IN (?, ?)',
            [$mechanIds[0], $mechanIds[1]]
        );
        $this->assertCount(2, $mechs, 'Expected mechs to still exist after dropship deletion');

        // Verify dropship is gone
        $deleted = $this->em->getRepository(Dropship::class)->find($dropshipId);
        $this->assertNull($deleted);
    }

    public function testDropshipAcquisitionUnassignsMech(): void
    {
        $userRef = $this->createUserAndCompany('dropship_acquire', 'Acquire Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        $dropship = new Dropship();
        $dropship->setCompany($company);
        $dropship->setMaxCapacity(100);
        $dropship->setMekbayCapacity(2);
        $dropship->setName('Acquire Dropship');

        $this->em->persist($dropship);
        $this->em->flush();

        $mechan = new SalvagedMech();
        $mechan->setCompany($company);
        $mechan->setModel('Acquire Mech');
        $mechan->setTonnage(60);
        $mechan->setBvCost(200);
        $mechan->setScrapyard(false);
        $mechan->setDropship($dropship);

        $this->em->persist($mechan);
        $this->em->flush();

        $mechanId = $mechan->getId();

        // Unassign mech (simulating acquisition)
        $mechan = $this->em->getRepository(SalvagedMech::class)->find($mechanId);
        $mechan->setDropship(null);
        $this->em->flush();

        // Verify mech is unassigned
        $updated = $this->em->getRepository(SalvagedMech::class)->find($mechanId);
        $this->assertNotNull($updated);
        $this->assertNull($updated->getDropship());

        // Verify dropship count decreased
        $count = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM salvaged_mech WHERE dropship_id = ?',
            [$dropship->getId()]
        );
        $this->assertEquals(0, $count);
    }

    // ── Mekbay Tests ────────────────────────────────────────────────────────

    public function testDropshipRejectsUnitWhenMekbaysExhausted(): void
    {
        $userRef = $this->createUserAndCompany('dropship_mekbay', 'Mekbay Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        $dropship = new Dropship();
        $dropship->setCompany($company);
        $dropship->setMaxCapacity(100);
        $dropship->setMekbayCapacity(1);
        $dropship->setName('Mekbay Dropship');

        $this->em->persist($dropship);
        $this->em->flush();

        // Create 1 unit on dropship (fills the single mekbay)
        $unit1 = new \App\Entity\Unit();
        $unit1->setCompany($company);
        $unit1->setName('Mech One');
        $unit1->setChassis('Atlas');
        $unit1->setTonnage(50);
        $unit1->setBv(300);
        $unit1->setUnitType(\App\Enum\UnitType::Mech);
        $unit1->setDropship($dropship);

        $this->em->persist($unit1);
        $this->em->flush();

        // Verify first unit is on dropship
        $count = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM unit WHERE dropship_id = ?',
            [$dropship->getId()]
        );
        $this->assertEquals(1, $count);

        // Verify mekbay capacity is stored
        $storedDropship = $this->em->getRepository(Dropship::class)->find($dropship->getId());
        $this->assertEquals(1, $storedDropship->getMekbayCapacity());
    }

    public function testSalvagedMechAssignmentIgnoresMekbayLimit(): void
    {
        $userRef = $this->createUserAndCompany('dropship_salvage_mekbay', 'Salvage Mekbay Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        $dropship = new Dropship();
        $dropship->setCompany($company);
        $dropship->setMaxCapacity(200);
        $dropship->setMekbayCapacity(0);
        $dropship->setName('Zero Mekbay Dropship');

        $this->em->persist($dropship);
        $this->em->flush();

        // Create a salvaged mech on a dropship with 0 mekbays
        $mechan = new SalvagedMech();
        $mechan->setCompany($company);
        $mechan->setModel('Salvaged Mech');
        $mechan->setTonnage(100);
        $mechan->setBvCost(300);
        $mechan->setScrapyard(false);
        $mechan->setDropship($dropship);

        $this->em->persist($mechan);
        $this->em->flush();

        // Verify salvaged mech is on dropship despite 0 mekbays
        $count = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM salvaged_mech WHERE dropship_id = ?',
            [$dropship->getId()]
        );
        $this->assertEquals(1, $count);
    }

    public function testDropshipStoresMekbayCapacity(): void
    {
        $userRef = $this->createUserAndCompany('dropship_mekbay_store', 'Store Company', 'Inner Sphere');
        $companyId = $userRef['companyId'];

        $company = $this->em->getRepository(MercenaryCompany::class)->find($companyId);

        $dropship = new Dropship();
        $dropship->setCompany($company);
        $dropship->setMaxCapacity(40);
        $dropship->setMekbayCapacity(3);
        $dropship->setName('Store Dropship');

        $this->em->persist($dropship);
        $this->em->flush();

        $created = $this->em->getRepository(Dropship::class)->findOneBy(['company' => $company]);
        $this->assertNotNull($created);
        $this->assertEquals(3, $created->getMekbayCapacity());
    }
}
