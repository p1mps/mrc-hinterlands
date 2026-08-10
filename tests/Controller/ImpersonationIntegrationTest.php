<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ImpersonationIntegrationTest extends WebTestCase
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

    public function testFindAllUsersWithCompanyOnlyReturnsUsersWithCompanies(): void
    {
        $conn = $this->em->getConnection();

        $hash = password_hash('testpassword', PASSWORD_BCRYPT, ['cost' => 4]);

        $conn->insert('user', [
            'username' => 'user_with_company',
            'email' => 'with@test.com',
            'password' => $hash,
            'roles' => '[]',
        ]);
        $companyId = (int) $conn->lastInsertId();

        $conn->insert('mercenary_company', [
            'user_id' => $companyId,
            'name' => 'With Company',
            'faction' => 'Inner Sphere',
            'reputation' => 1,
        ]);

        $conn->insert('user', [
            'username' => 'user_without_company',
            'email' => 'without@test.com',
            'password' => $hash,
            'roles' => '[]',
        ]);

        $conn->insert('user', [
            'username' => 'another_with_company',
            'email' => 'another@with.com',
            'password' => $hash,
            'roles' => '[]',
        ]);
        $companyId2 = (int) $conn->lastInsertId();

        $conn->insert('mercenary_company', [
            'user_id' => $companyId2,
            'name' => 'Another With Company',
            'faction' => 'Inner Sphere',
            'reputation' => 1,
        ]);

        $users = $this->em->getRepository(User::class)->findAllUsersWithCompany();

        $this->assertCount(2, $users);
        $usernames = array_map(fn($u) => $u->getUsername(), $users);
        $this->assertContains('user_with_company', $usernames);
        $this->assertContains('another_with_company', $usernames);
        $this->assertNotContains('user_without_company', $usernames);
    }

    public function testFindAllUsersWithCompanyOrdersByUsername(): void
    {
        $conn = $this->em->getConnection();

        $hash = password_hash('testpassword', PASSWORD_BCRYPT, ['cost' => 4]);

        $conn->insert('user', [
            'username' => 'zeta_pilot',
            'email' => 'zeta@test.com',
            'password' => $hash,
            'roles' => '[]',
        ]);
        $zetaId = (int) $conn->lastInsertId();

        $conn->insert('mercenary_company', [
            'user_id' => $zetaId,
            'name' => 'Zeta Company',
            'faction' => 'Clan',
            'reputation' => 1,
        ]);

        $conn->insert('user', [
            'username' => 'alpha_pilot',
            'email' => 'alpha@test.com',
            'password' => $hash,
            'roles' => '[]',
        ]);
        $alphaId = (int) $conn->lastInsertId();

        $conn->insert('mercenary_company', [
            'user_id' => $alphaId,
            'name' => 'Alpha Company',
            'faction' => 'Inner Sphere',
            'reputation' => 1,
        ]);

        $conn->insert('user', [
            'username' => 'beta_pilot',
            'email' => 'beta@test.com',
            'password' => $hash,
            'roles' => '[]',
        ]);
        $betaId = (int) $conn->lastInsertId();

        $conn->insert('mercenary_company', [
            'user_id' => $betaId,
            'name' => 'Beta Company',
            'faction' => 'Free Worlds',
            'reputation' => 1,
        ]);

        $users = $this->em->getRepository(User::class)->findAllUsersWithCompany();

        $this->assertCount(3, $users);
        $this->assertEquals('alpha_pilot', $users[0]->getUsername());
        $this->assertEquals('beta_pilot', $users[1]->getUsername());
        $this->assertEquals('zeta_pilot', $users[2]->getUsername());
    }

    public function testFindAllUsersWithCompanyReturnsEmptyWhenNoCompanies(): void
    {
        $conn = $this->em->getConnection();

        $hash = password_hash('testpassword', PASSWORD_BCRYPT, ['cost' => 4]);

        $conn->insert('user', [
            'username' => 'orphan_user',
            'email' => 'orphan@test.com',
            'password' => $hash,
            'roles' => '[]',
        ]);

        $users = $this->em->getRepository(User::class)->findAllUsersWithCompany();

        $this->assertCount(0, $users);
    }

    public function testDashboardControllerPassesUsersToTemplate(): void
    {
        $conn = $this->em->getConnection();

        $hash = password_hash('testpassword', PASSWORD_BCRYPT, ['cost' => 4]);

        $conn->insert('user', [
            'username' => 'dashboard_test_user',
            'email' => 'dashboard@test.com',
            'password' => $hash,
            'roles' => '[]',
        ]);
        $companyId = (int) $conn->lastInsertId();

        $conn->insert('mercenary_company', [
            'user_id' => $companyId,
            'name' => 'Dashboard Company',
            'faction' => 'Inner Sphere',
            'reputation' => 1,
        ]);

        $users = $this->em->getRepository(User::class)->findAllUsersWithCompany();

        $this->assertCount(1, $users);
        $this->assertEquals('dashboard_test_user', $users[0]->getUsername());
        $this->assertNotNull($users[0]->getCompany());
        $this->assertEquals('Dashboard Company', $users[0]->getCompany()->getName());
    }

    public function testDashboardControllerPassesMultipleUsers(): void
    {
        $conn = $this->em->getConnection();

        $hash = password_hash('testpassword', PASSWORD_BCRYPT, ['cost' => 4]);

        for ($i = 1; $i <= 5; $i++) {
            $conn->insert('user', [
                'username' => "multi_user_{$i}",
                'email' => "multi{$i}@test.com",
                'password' => $hash,
                'roles' => '[]',
            ]);
            $companyId = (int) $conn->lastInsertId();

            $conn->insert('mercenary_company', [
                'user_id' => $companyId,
                'name' => "Multi Company {$i}",
                'faction' => 'Inner Sphere',
                'reputation' => 1,
            ]);
        }

        $users = $this->em->getRepository(User::class)->findAllUsersWithCompany();

        $this->assertCount(5, $users);
        $usernames = array_map(fn($u) => $u->getUsername(), $users);
        sort($usernames);
        $expected = ['multi_user_1', 'multi_user_2', 'multi_user_3', 'multi_user_4', 'multi_user_5'];
        $this->assertEquals($expected, $usernames);
    }
}
