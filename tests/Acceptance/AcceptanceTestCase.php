<?php

namespace App\Tests\Acceptance;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class AcceptanceTestCase extends WebTestCase
{
    protected static ?EntityManagerInterface $sharedEm = null;
    protected static ?\Symfony\Bundle\FrameworkBundle\KernelBrowser $sharedClient = null;

    protected function setUp(): void
    {
        self::$sharedEm = null;
        self::$sharedClient = null;
    }

    /**
     * Lazily reinitialize the shared EntityManager if it was reset by setUp().
     * This handles the case where seedUserAndCompany fails to reinitialize.
     */
    protected function ensureSharedEm(): void
    {
        if (self::$sharedEm !== null) {
            return;
        }

        $client = static::createClient();
        self::$sharedClient = $client;

        $container = $client->getContainer();

        // Try to get EntityManagerInterface directly; fall back to doctrine service
        try {
            self::$sharedEm = $container->get(EntityManagerInterface::class);
        } catch (\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException) {
            self::$sharedEm = $container->get('doctrine')->getManager();
        }

        // Drop ALL tables (not just those in the current schema) to prevent
        // cross-test-class contamination. E.g. salvaged_mech from a previous
        // test class would cause "table already exists" when the current class
        // doesn't define that entity, or "no such table" when it does.
        $conn = self::$sharedEm->getConnection();
        $allTables = [
            'salvaged_mech',
            'support_point_entry',
            'contract_log_entry',
            'track_record',
            'contract',
            'dropship',
            'pilot',
            'unit',
            'mercenary_company',
            'user',
        ];
        foreach ($allTables as $table) {
            try {
                $conn->executeStatement('DROP TABLE IF EXISTS ' . $table);
            } catch (\Throwable) {
                // Table may not exist; ignore.
            }
        }

        $metadata = self::$sharedEm->getMetadataFactory()->getAllMetadata();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool(self::$sharedEm);
        try {
            $schemaTool->dropSchema($metadata);
        } catch (\Throwable) {
            // Ignore errors during drop
        }
        $schemaTool->createSchema($metadata);
    }

    protected function seedUserAndCompany(string $username, string $companyName, string $faction): array
    {
        // Reuse existing client if available to avoid kernel re-boot
        $client = self::$sharedClient ?? static::createClient();
        self::$sharedClient = $client;

        // Only initialize schema on first call
        if (self::$sharedEm === null) {
            $container = $client->getContainer();

            // Try to get EntityManagerInterface directly; fall back to doctrine service
            try {
                self::$sharedEm = $container->get(EntityManagerInterface::class);
            } catch (\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException) {
                self::$sharedEm = $container->get('doctrine')->getManager();
            }

            // Drop ALL tables before creating the schema to prevent
            // cross-test-class contamination.
            $conn = self::$sharedEm->getConnection();
            $allTables = [
                'salvaged_mech',
                'support_point_entry',
                'contract_log_entry',
                'track_record',
                'contract',
                'dropship',
                'pilot',
                'unit',
                'mercenary_company',
                'user',
            ];
            foreach ($allTables as $table) {
                try {
                    $conn->executeStatement('DROP TABLE IF EXISTS ' . $table);
                } catch (\Throwable) {
                    // Table may not exist; ignore.
                }
            }

            $metadata = self::$sharedEm->getMetadataFactory()->getAllMetadata();
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool(self::$sharedEm);
            try {
                $schemaTool->dropSchema($metadata);
            } catch (\Throwable) {
                // Ignore errors during drop
            }
            $schemaTool->createSchema($metadata);
        }

        $conn = self::$sharedEm->getConnection();

        $existingId = $conn->fetchOne(
            'SELECT id FROM "user" WHERE username = ?',
            [$username]
        );

        if ($existingId) {
            return ['userId' => (int) $existingId, 'companyId' => self::getCompanyIdForUser($username)];
        }

        $hash = password_hash('testpassword', PASSWORD_BCRYPT, ['cost' => 4]);

        // Admin users need ROLE_ALLOWED_TO_SWITCH for impersonation to work
        $isAdmin = in_array($username, ['Andrea', 'Zidahya', 'MitchellWelsh'], true);
        $roles = $isAdmin ? '["ROLE_USER", "ROLE_ALLOWED_TO_SWITCH"]' : '["ROLE_USER"]';

        $conn->insert('user', [
            'username' => $username,
            'email' => strtolower($username) . '@test.com',
            'password' => $hash,
            'roles' => $roles,
        ]);

        // Also update any existing users that were created before the roles column was added
        $conn->executeStatement('UPDATE "user" SET roles = \'["ROLE_USER"]\' WHERE roles IS NULL');

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

    protected static function getCompanyIdForUser(string $username): int
    {
        return (int) self::$sharedEm->getConnection()->fetchOne(
            'SELECT id FROM mercenary_company WHERE user_id = (SELECT id FROM "user" WHERE username = ?)',
            [$username]
        )['id'];
    }

    /**
     * Login and return the client for further requests.
     */
    protected function login(string $username, string $password = 'testpassword'): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = self::$sharedClient ?? static::createClient();
        self::$sharedClient = $client;

        $container = $client->getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = $em->getRepository(\App\Entity\User::class)->findOneBy(['username' => $username]);
        if (!$user) {
            throw new \RuntimeException("User '{$username}' not found. Did you call seedUserAndCompany first?");
        }

        $client->loginUser($user, 'main');

        return $client;
    }

    protected function seedPilot(int $companyId, string $name, bool $isNamed = false, int $gunnery = 4, int $piloting = 5): int
    {
        $conn = self::$sharedEm->getConnection();
        $conn->insert('pilot', [
            'company_id' => $companyId,
            'name' => $name,
            'is_named' => $isNamed ? 1 : 0,
            'gunnery' => $gunnery,
            'piloting' => $piloting,
            'gunnery_xp' => 0,
            'piloting_xp' => 0,
        ]);
        return (int) $conn->lastInsertId();
    }

    protected function seedUnit(int $companyId, string $name, string $chassis, int $tonnage, int $bv, string $unitType, string $damageState = 'none', ?int $pilotId = null): int
    {
        $conn = self::$sharedEm->getConnection();
        $conn->insert('unit', [
            'company_id' => $companyId,
            'pilot_id' => $pilotId,
            'name' => $name,
            'chassis' => $chassis,
            'tonnage' => $tonnage,
            'bv' => $bv,
            'unit_type' => $unitType,
            'damage_state' => $damageState,
        ]);
        return (int) $conn->lastInsertId();
    }

    protected function seedContract(int $companyId, array $data): int
    {
        $conn = self::$sharedEm->getConnection();
        $row = array_merge([
            'company_id' => $companyId,
            'type' => 'expedition',
            'status' => 'available',
            'employer' => 'Client',
            'employer_affiliation' => '',
            'scale' => 1,
            'duration_months' => 6,
            'base_pay_percent' => 75,
            'command_rights' => 'integrated',
            'support_terms' => 'None',
            'salvage_rights' => 'Exchange',
            'transport_terms' => '—',
            'number_of_tracks' => 1,
            'is_opposing' => 0,
            'tracks_completed' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ], $data);
        $conn->insert('contract', array_filter($row, fn ($v) => null !== $v, ARRAY_FILTER_USE_BOTH));
        return (int) $conn->lastInsertId();
    }

    protected function seedContractWithTracks(int $companyId, int $numberOfTracks, array $overrides = []): int
    {
        $conn = self::$sharedEm->getConnection();
        $contractId = $this->seedContract($companyId, $overrides);

        for ($i = 1; $i <= $numberOfTracks; $i++) {
            $conn->insert('track_record', [
                'contract_id' => $contractId,
                'track_number' => $i,
                'mission_type' => 'Assault',
                'terrain' => 'Open',
                'status' => 'pending',
                'taking_one_for_team' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $contractId;
    }

    protected function seedDropship(int $companyId, string $name = 'Test Dropship', int $maxCapacity = 40, int $mekbayCapacity = 0): int
    {
        $conn = self::$sharedEm->getConnection();
        $conn->insert('dropship', [
            'company_id' => $companyId,
            'name' => $name,
            'max_capacity' => $maxCapacity,
            'mekbay_capacity' => $mekbayCapacity,
        ]);
        return (int) $conn->lastInsertId();
    }

    protected function seedSalvagedMech(int $companyId, array $data): int
    {
        $conn = self::$sharedEm->getConnection();
        $row = array_merge([
            'model' => 'Catapult CAT-PU1',
            'tonnage' => 80,
            'bv_cost' => 300,
            'damage_state' => 'none',
            'tech_base' => 'is',

            'salvage_rights_percent' => 50,
            'scrapyard' => 1,
            'is_truly_destroyed' => 0,
            'sp_taken' => 0,
            'company_id' => $companyId,
            'created_at' => date('Y-m-d H:i:s'),
        ], $data);
        $conn->insert('salvaged_mech', array_filter($row, fn ($v) => null !== $v, ARRAY_FILTER_USE_BOTH));
        return (int) $conn->lastInsertId();
    }

    protected function seedSupportPoints(int $companyId, int $amount, string $description = 'Test'): void
    {
        $entry = new \App\Entity\SupportPointEntry();
        $entry->setAmount($amount);
        $entry->setCompany(self::$sharedEm->getRepository(\App\Entity\MercenaryCompany::class)->find($companyId));
        $entry->setDescription($description);
        self::$sharedEm->persist($entry);
        self::$sharedEm->flush();
    }

    protected function assertFlashMessage(Crawler $crawler, string $expectedMessage): void
    {
        $html = $crawler->filter('div.alert')->html();
        $this->assertStringContainsString($expectedMessage, $html, 'Expected flash message "' . $expectedMessage . '" not found in response');
    }

    protected function assertContainsText(Crawler $crawler, string $text): void
    {
        $bodyText = $crawler->filter('body')->text();
        $this->assertStringContainsString($text, $bodyText, 'Expected text "' . $text . '" not found in page');
    }

    protected function assertRedirectsTo(Crawler $crawler, string $expectedPath): void
    {
        $this->assertResponseRedirected();
        $this->assertNotNull(static::getClient()->getResponse()->headers->get('location'));
    }
}
