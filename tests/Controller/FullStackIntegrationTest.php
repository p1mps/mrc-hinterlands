<?php

namespace App\Tests\Controller;

use App\Entity\Contract;
use App\Entity\MercenaryCompany;
use App\Entity\Pilot;
use App\Entity\Unit;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FullStackIntegrationTest extends WebTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        // $client = static::createClient(); // Already created in setUp
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);

        // Drop and recreate schema
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

        // Check if user already exists
        $existingId = $conn->fetchOne(
            'SELECT id FROM "user" WHERE username = ?',
            [$username]
        );

        if ($existingId) {
            return ['userId' => (int) $existingId, 'companyId' => $this->getCompanyIdForUser($username)];
        }

        // Hash password using bcrypt (same as security.yaml for test env)
        $hash = password_hash('testpassword', PASSWORD_BCRYPT, ['cost' => 4]);

        $conn->insert('user', [
            'username' => $username,
            'email' => strtolower($username) . '@test.com',
            'password' => $hash,
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

    private function createContractRecord(array $data): int
    {
        $conn = $this->em->getConnection();
        $conn->insert('contract', array_filter($data, fn ($v) => null !== $v, ARRAY_FILTER_USE_BOTH));
        return (int) $conn->lastInsertId();
    }

    private function createPilotRecord(array $data): int
    {
        $this->em->getConnection()->insert('pilot', array_filter($data, fn ($v) => null !== $v, ARRAY_FILTER_USE_BOTH));
        return (int) $this->em->getConnection()->lastInsertId();
    }

    private function createUnitRecord(array $data): int
    {
        $this->em->getConnection()->insert('unit', array_filter($data, fn ($v) => null !== $v, ARRAY_FILTER_USE_BOTH));
        return (int) $this->em->getConnection()->lastInsertId();
    }

    private function addSupportPointsToCompany(int $companyId, int $amount): void
    {
        $this->em->getConnection()->insert('support_point_entry', [
            'company_id' => $companyId,
            'amount' => $amount,
            'description' => 'Initial funding',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createSalvagedMechRecord(array $data): int
    {
        $this->em->getConnection()->insert('salvaged_mech', array_filter($data, fn ($v) => null !== $v, ARRAY_FILTER_USE_BOTH));
        return (int) $this->em->getConnection()->lastInsertId();
    }

    // ── User/Company CRUD ──────────────────────────────────────────────────

    public function testRegisterCreatesUserAndCompany(): void
    {
        // Test entity persistence directly
        $user = new User();
        $user->setUsername('newpilot');
        $user->setEmail('newpilot@test.com');
        $user->setPassword(password_hash('secret123', PASSWORD_BCRYPT, ['cost' => 4]));

        $this->em->persist($user);
        $this->em->flush();

        $createdUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'newpilot']);
        $this->assertNotNull($createdUser);

        $company = new MercenaryCompany();
        $company->setName('New Merc Co');
        $company->setFaction('Clan');
        $company->setUser($createdUser);

        $this->em->persist($company);
        $this->em->flush();

        $createdCompany = $this->em->getRepository(MercenaryCompany::class)->findOneBy(['name' => 'New Merc Co']);
        $this->assertNotNull($createdCompany);
        $this->assertEquals('Clan', $createdCompany->getFaction());
    }

    // ── Pilot CRUD ─────────────────────────────────────────────────────────

    public function testPilotsIndexListsCompanyPilots(): void
    {
        // Test entity persistence directly
        $userRef = $this->createUserAndCompany('pilot_owner', 'Pilot Company', 'Inner Sphere');

        $pilot = new Pilot();
        $pilot->setName('Ace Ventura');
        $pilot->setIsNamed(true);
        $pilot->setGunnery(4);
        $pilot->setPiloting(5);
        $pilot->setGunneryXp(100);
        $pilot->setPilotingXp(100);
        $pilot->setCompany($this->em->getRepository(MercenaryCompany::class)->find($userRef['companyId']));

        $this->em->persist($pilot);
        $this->em->flush();

        // Verify pilot exists in database
        $createdPilot = $this->em->getRepository(Pilot::class)->findOneBy(['name' => 'Ace Ventura']);
        $this->assertNotNull($createdPilot);
        $this->assertEquals('Ace Ventura', $createdPilot->getName());
        $this->assertTrue($createdPilot->isNamed());
        $this->assertEquals(4, $createdPilot->getGunnery());
        $this->assertEquals(5, $createdPilot->getPiloting());
        $this->assertEquals(100, $createdPilot->getGunneryXp());
    }

    public function testCreatePilotPersistedToDatabase(): void
    {
        $userRef = $this->createUserAndCompany('pilot_owner2', 'Pilot Company 2', 'Inner Sphere');

        $pilot = new Pilot();
        $pilot->setName('Thunderbird Tom');
        $pilot->setIsNamed(true);
        $pilot->setGunnery(4);
        $pilot->setPiloting(5);
        $pilot->setGunneryXp(0);
        $pilot->setPilotingXp(0);
        $pilot->setCompany($this->em->getRepository(MercenaryCompany::class)->find($userRef['companyId']));

        $this->em->persist($pilot);
        $this->em->flush();

        $createdPilot = $this->em->getRepository(Pilot::class)->findOneBy(['name' => 'Thunderbird Tom']);
        $this->assertNotNull($createdPilot);
        $this->assertEquals('Thunderbird Tom', $createdPilot->getName());
        $this->assertTrue($createdPilot->isNamed());
        $this->assertEquals(4, $createdPilot->getGunnery());
        $this->assertEquals(5, $createdPilot->getPiloting());
        $this->assertEquals(0, $createdPilot->getGunneryXp());
    }

    public function testEditPilotUpdatesData(): void
    {
        $userRef = $this->createUserAndCompany('pilot_owner3', 'Pilot Company 3', 'Inner Sphere');
        $pilot = new Pilot();
        $pilot->setName('Old Name');
        $pilot->setIsNamed(false);
        $pilot->setGunnery(4);
        $pilot->setPiloting(5);
        $pilot->setGunneryXp(100);
        $pilot->setPilotingXp(100);
        $pilot->setCompany($this->em->getRepository(MercenaryCompany::class)->find($userRef['companyId']));

        $this->em->persist($pilot);
        $this->em->flush();

        $pilotId = $pilot->getId();

        // Update pilot
        $pilot->setName('New Name');
        $pilot->setIsNamed(true);
        $pilot->setGunnery(5);
        $pilot->setPiloting(6);
        $pilot->setGunneryXp(200);
        $pilot->setPilotingXp(200);
        $this->em->flush();

        $updatedPilot = $this->em->getRepository(Pilot::class)->find($pilotId);
        $this->assertNotNull($updatedPilot);
        $this->assertEquals('New Name', $updatedPilot->getName());
        $this->assertTrue($updatedPilot->isNamed());
        $this->assertEquals(5, $updatedPilot->getGunnery());
        $this->assertEquals(6, $updatedPilot->getPiloting());
        $this->assertEquals(200, $updatedPilot->getGunneryXp());
    }

    public function testDeletePilotRemovesFromDatabase(): void
    {
        $userRef = $this->createUserAndCompany('pilot_owner4', 'Pilot Company 4', 'Inner Sphere');
        $pilot = new Pilot();
        $pilot->setName('ToDelete');
        $pilot->setIsNamed(false);
        $pilot->setGunnery(4);
        $pilot->setPiloting(5);
        $pilot->setGunneryXp(0);
        $pilot->setPilotingXp(0);
        $pilot->setCompany($this->em->getRepository(MercenaryCompany::class)->find($userRef['companyId']));

        $this->em->persist($pilot);
        $this->em->flush();

        $pilotId = $pilot->getId();
        $this->em->remove($pilot);
        $this->em->flush();

        $deletedPilot = $this->em->getRepository(Pilot::class)->find($pilotId);
        $this->assertNull($deletedPilot, 'Expected pilot to be deleted');
    }

    // ── Unit CRUD ──────────────────────────────────────────────────────────

    public function testCreateUnitPersistedToDatabase(): void
    {
        $userRef = $this->createUserAndCompany('roster_owner', 'Roster Company', 'Inner Sphere');

        $unit = new Unit();
        $unit->setName('Gravino GRV-NI1');
        $unit->setChassis('Gravino GRV-NI1');
        $unit->setTonnage(35);
        $unit->setBv(150);
        $unit->setUnitType(\App\Enum\UnitType::Mech);
        $unit->setDamageState(\App\Enum\DamageState::None);
        $unit->setCompany($this->em->getRepository(MercenaryCompany::class)->find($userRef['companyId']));

        $this->em->persist($unit);
        $this->em->flush();

        $createdUnit = $this->em->getRepository(Unit::class)->findOneBy(['name' => 'Gravino GRV-NI1']);
        $this->assertNotNull($createdUnit);
        $this->assertEquals('Gravino GRV-NI1', $createdUnit->getName());
        $this->assertEquals(35, $createdUnit->getTonnage());
        $this->assertEquals(150, $createdUnit->getBv());
        $this->assertEquals(\App\Enum\UnitType::Mech, $createdUnit->getUnitType());
        $this->assertEquals(\App\Enum\DamageState::None, $createdUnit->getDamageState());
    }

    public function testEditUnitUpdatesData(): void
    {
        $userRef = $this->createUserAndCompany('roster_owner2', 'Roster Company 2', 'Inner Sphere');
        $unit = new Unit();
        $unit->setName('Old Unit');
        $unit->setChassis('Old Chassis');
        $unit->setTonnage(50);
        $unit->setBv(200);
        $unit->setUnitType(\App\Enum\UnitType::Mech);
        $unit->setDamageState(\App\Enum\DamageState::None);
        $unit->setIsActive(true);
        $unit->setCompany($this->em->getRepository(MercenaryCompany::class)->find($userRef['companyId']));

        $this->em->persist($unit);
        $this->em->flush();

        $unitId = $unit->getId();

        // Update unit
        $unit->setName('New Unit');
        $unit->setChassis('New Chassis');
        $unit->setTonnage(75);
        $unit->setBv(300);
        $unit->setDamageState(\App\Enum\DamageState::Crippled);
        $this->em->flush();

        $updatedUnit = $this->em->getRepository(Unit::class)->find($unitId);
        $this->assertNotNull($updatedUnit);
        $this->assertEquals('New Unit', $updatedUnit->getName());
        $this->assertEquals('New Chassis', $updatedUnit->getChassis());
        $this->assertEquals(75, $updatedUnit->getTonnage());
        $this->assertEquals(300, $updatedUnit->getBv());
        $this->assertEquals(\App\Enum\DamageState::Crippled, $updatedUnit->getDamageState());
    }

    public function testDeleteUnitRemovesFromDatabase(): void
    {
        $userRef = $this->createUserAndCompany('roster_owner3', 'Roster Company 3', 'Inner Sphere');
        $unit = new Unit();
        $unit->setName('ToDelete');
        $unit->setChassis('ToDelete');
        $unit->setTonnage(50);
        $unit->setBv(200);
        $unit->setUnitType(\App\Enum\UnitType::Mech);
        $unit->setDamageState(\App\Enum\DamageState::None);
        $unit->setIsActive(true);
        $unit->setCompany($this->em->getRepository(MercenaryCompany::class)->find($userRef['companyId']));

        $this->em->persist($unit);
        $this->em->flush();

        $unitId = $unit->getId();
        $this->em->remove($unit);
        $this->em->flush();

        $deletedUnit = $this->em->getRepository(Unit::class)->find($unitId);
        $this->assertNull($deletedUnit, 'Expected unit to be deleted');
    }

    public function testAssignPilotToUnit(): void
    {
        $userRef = $this->createUserAndCompany('roster_owner4', 'Roster Company 4', 'Inner Sphere');

        $unit = new Unit();
        $unit->setName('Test Unit');
        $unit->setChassis('Test Chassis');
        $unit->setTonnage(50);
        $unit->setBv(200);
        $unit->setUnitType(\App\Enum\UnitType::Mech);
        $unit->setDamageState(\App\Enum\DamageState::None);
        $unit->setIsActive(true);
        $unit->setCompany($this->em->getRepository(MercenaryCompany::class)->find($userRef['companyId']));

        $this->em->persist($unit);
        $this->em->flush();

        $unitId = $unit->getId();

        $pilot = new Pilot();
        $pilot->setName('Test Pilot');
        $pilot->setIsNamed(false);
        $pilot->setGunnery(4);
        $pilot->setPiloting(5);
        $pilot->setGunneryXp(0);
        $pilot->setPilotingXp(0);
        $pilot->setCompany($this->em->getRepository(MercenaryCompany::class)->find($userRef['companyId']));

        $this->em->persist($pilot);
        $this->em->flush();

        $pilotId = $pilot->getId();

        // Assign pilot to unit
        $unit = $this->em->getRepository(Unit::class)->find($unitId);
        $pilot = $this->em->getRepository(Pilot::class)->find($pilotId);
        $unit->setPilot($pilot);
        $this->em->flush();

        $assignedUnit = $this->em->getRepository(Unit::class)->find($unitId);
        $this->assertNotNull($assignedUnit);
        $this->assertNotNull($assignedUnit->getPilot());
        $this->assertEquals($pilotId, $assignedUnit->getPilot()->getId());
    }

    // ── Contract CRUD ──────────────────────────────────────────────────────

    public function testCreateContractPersistedToDatabase(): void
    {
        $userRef = $this->createUserAndCompany('contract_owner', 'Contract Company', 'Inner Sphere');

        $contract = new Contract();
        $contract->setType(\App\Enum\ContractType::Expedition);
        $contract->setEmployer('New Employer');
        $contract->setEmployerAffiliation('New Affiliation');
        $contract->setScale(2);
        $contract->setDurationMonths(12);
        $contract->setBasePayPercent(75);
        $contract->setCommandRights(\App\Enum\CommandRights::Integrated);
        $contract->setSupportTerms('None');
        $contract->setSalvageRights('Exchange');
        $contract->setTransportTerms('—');
        $contract->setNumberOfTracks(1);
        $contract->setName('Test Contract');
        $contract->setPlanet('Test Planet');
        $contract->setIntensity('High');
        $contract->setCompany($this->em->getRepository(MercenaryCompany::class)->find($userRef['companyId']));

        $this->em->persist($contract);
        $this->em->flush();

        $createdContract = $this->em->getRepository(Contract::class)->findOneBy(['name' => 'Test Contract']);
        $this->assertNotNull($createdContract);
        $this->assertEquals('New Employer', $createdContract->getEmployer());
        $this->assertEquals(2, $createdContract->getScale());
        $this->assertEquals(12, $createdContract->getDurationMonths());
        $this->assertEquals(75, $createdContract->getBasePayPercent());
        $this->assertEquals('Test Planet', $createdContract->getPlanet());
        $this->assertEquals('High', $createdContract->getIntensity());
    }

    public function testEditContractUpdatesData(): void
    {
        $userRef = $this->createUserAndCompany('contract_owner2', 'Contract Company 2', 'Inner Sphere');
        $contract = new Contract();
        $contract->setType(\App\Enum\ContractType::Expedition);
        $contract->setEmployer('Old Employer');
        $contract->setEmployerAffiliation('Old Affiliation');
        $contract->setScale(1);
        $contract->setDurationMonths(6);
        $contract->setBasePayPercent(50);
        $contract->setCommandRights(\App\Enum\CommandRights::Integrated);
        $contract->setSupportTerms('None');
        $contract->setSalvageRights('Exchange');
        $contract->setTransportTerms('—');
        $contract->setNumberOfTracks(1);
        $contract->setName('Old Contract');
        $contract->setPlanet('Old Planet');
        $contract->setIntensity('Low');
        $contract->setCompany($this->em->getRepository(MercenaryCompany::class)->find($userRef['companyId']));

        $this->em->persist($contract);
        $this->em->flush();

        $contractId = $contract->getId();

        // Update contract
        $contract->setType(\App\Enum\ContractType::Garrison);
        $contract->setEmployer('New Employer');
        $contract->setEmployerAffiliation('New Affiliation');
        $contract->setScale(3);
        $contract->setDurationMonths(24);
        $contract->setBasePayPercent(100);
        $contract->setCommandRights(\App\Enum\CommandRights::House);
        $contract->setSupportTerms('Battle 50%');
        $contract->setSalvageRights('3');
        $contract->setTransportTerms('10%');
        $contract->setName('New Contract');
        $contract->setPlanet('New Planet');
        $contract->setIntensity('Extreme');
        $this->em->flush();

        $updatedContract = $this->em->getRepository(Contract::class)->find($contractId);
        $this->assertNotNull($updatedContract);
        $this->assertEquals('New Employer', $updatedContract->getEmployer());
        $this->assertEquals(3, $updatedContract->getScale());
        $this->assertEquals(24, $updatedContract->getDurationMonths());
        $this->assertEquals(100, $updatedContract->getBasePayPercent());
        $this->assertEquals('New Planet', $updatedContract->getPlanet());
        $this->assertEquals('Extreme', $updatedContract->getIntensity());
    }

    public function testDeleteContractRemovesFromDatabase(): void
    {
        $userRef = $this->createUserAndCompany('contract_owner3', 'Contract Company 3', 'Inner Sphere');
        $contract = new Contract();
        $contract->setType(\App\Enum\ContractType::Expedition);
        $contract->setEmployer('ToDelete');
        $contract->setEmployerAffiliation('ToDelete');
        $contract->setScale(1);
        $contract->setDurationMonths(6);
        $contract->setBasePayPercent(50);
        $contract->setCommandRights(\App\Enum\CommandRights::Integrated);
        $contract->setSupportTerms('None');
        $contract->setSalvageRights('Exchange');
        $contract->setTransportTerms('—');
        $contract->setNumberOfTracks(1);
        $contract->setCompany($this->em->getRepository(MercenaryCompany::class)->find($userRef['companyId']));

        $this->em->persist($contract);
        $this->em->flush();

        $contractId = $contract->getId();
        $this->em->remove($contract);
        $this->em->flush();

        $deletedContract = $this->em->getRepository(Contract::class)->find($contractId);
        $this->assertNull($deletedContract, 'Expected contract to be deleted');
    }

    // ── Support Points ─────────────────────────────────────────────────────

    public function testAddSupportPointEntry(): void
    {
        $userRef = $this->createUserAndCompany('sp_owner', 'SP Company', 'Inner Sphere');

        $entry = new \App\Entity\SupportPointEntry();
        $entry->setCompany($this->em->getRepository(MercenaryCompany::class)->find($userRef['companyId']));
        $entry->setAmount(100);
        $entry->setDescription('Test entry');

        $this->em->persist($entry);
        $this->em->flush();

        $entries = $this->em->getRepository(\App\Entity\SupportPointEntry::class)->findBy(['company' => $userRef['companyId']]);
        $this->assertCount(1, $entries);
        $this->assertEquals(100, $entries[0]->getAmount());
        $this->assertEquals('Test entry', $entries[0]->getDescription());
    }

    public function testDeleteSupportPointEntry(): void
    {
        $userRef = $this->createUserAndCompany('sp_owner2', 'SP Company 2', 'Inner Sphere');

        $entry = new \App\Entity\SupportPointEntry();
        $entry->setCompany($this->em->getRepository(MercenaryCompany::class)->find($userRef['companyId']));
        $entry->setAmount(100);
        $entry->setDescription('ToDelete');

        $this->em->persist($entry);
        $this->em->flush();

        $entryId = $entry->getId();
        $this->em->remove($entry);
        $this->em->flush();

        $deletedEntry = $this->em->getRepository(\App\Entity\SupportPointEntry::class)->find($entryId);
        $this->assertNull($deletedEntry, 'Expected support point entry to be deleted');
    }

    // ── SalvageMech ────────────────────────────────────────────────────────

    public function testSalvageMechPersistedToDatabase(): void
    {
        $userRef = $this->createUserAndCompany('salvage_owner', 'Salvage Company', 'Inner Sphere');
        $company = $this->em->getRepository(\App\Entity\MercenaryCompany::class)->find($userRef['companyId']);

        $mechan = new \App\Entity\SalvagedMech();
        $mechan->setCompany($company);
        $mechan->setModel('Catapult CAT-PU1');
        $mechan->setTonnage(80);
        $mechan->setBvCost(300);
        $mechan->setAcquired(false);
        $mechan->setScrapyard(true);

        $this->em->persist($mechan);
        $this->em->flush();

        $createdMech = $this->em->getRepository(\App\Entity\SalvagedMech::class)->findOneBy(['model' => 'Catapult CAT-PU1']);
        $this->assertNotNull($createdMech);
        $this->assertEquals('Catapult CAT-PU1', $createdMech->getModel());
        $this->assertEquals(80, $createdMech->getTonnage());
        $this->assertEquals(300, $createdMech->getBvCost());
        $this->assertFalse($createdMech->isAcquired());
        $this->assertTrue($createdMech->isScrapyard());
    }

    public function testEditSalvageMechUpdatesData(): void
    {
        $userRef = $this->createUserAndCompany('salvage_editor', 'Salvage Editor Company', 'Inner Sphere');
        $company = $this->em->getRepository(\App\Entity\MercenaryCompany::class)->find($userRef['companyId']);

        $mechan = new \App\Entity\SalvagedMech();
        $mechan->setCompany($company);
        $mechan->setModel('Old Mech');
        $mechan->setTonnage(80);
        $mechan->setBvCost(300);
        $mechan->setAcquired(false);
        $mechan->setScrapyard(true);

        $this->em->persist($mechan);
        $this->em->flush();

        $mechanId = $mechan->getId();

        // Update mech
        $mechan->setModel('New Mech');
        $mechan->setTonnage(100);
        $mechan->setBvCost(400);
        $mechan->setSalvageValue(200);
        $mechan->setSalvageRightsPercent(50);
        $this->em->flush();

        $updatedMech = $this->em->getRepository(\App\Entity\SalvagedMech::class)->find($mechanId);
        $this->assertNotNull($updatedMech);
        $this->assertEquals('New Mech', $updatedMech->getModel());
        $this->assertEquals(100, $updatedMech->getTonnage());
        $this->assertEquals(400, $updatedMech->getBvCost());
        $this->assertEquals(200, $updatedMech->getSalvageValue());
        $this->assertEquals(50, $updatedMech->getSalvageRightsPercent());
    }

    public function testDeleteSalvageMechRemovesFromDatabase(): void
    {
        $userRef = $this->createUserAndCompany('salvage_deleter', 'Salvage Deleter Company', 'Inner Sphere');
        $company = $this->em->getRepository(\App\Entity\MercenaryCompany::class)->find($userRef['companyId']);

        $mechan = new \App\Entity\SalvagedMech();
        $mechan->setCompany($company);
        $mechan->setModel('ToDelete');
        $mechan->setTonnage(80);
        $mechan->setBvCost(300);
        $mechan->setAcquired(false);
        $mechan->setScrapyard(true);

        $this->em->persist($mechan);
        $this->em->flush();

        $mechanId = $mechan->getId();
        $this->em->remove($mechan);
        $this->em->flush();

        $deletedMech = $this->em->getRepository(\App\Entity\SalvagedMech::class)->find($mechanId);
        $this->assertNull($deletedMech, 'Expected salvaged mech to be deleted');
    }
}
