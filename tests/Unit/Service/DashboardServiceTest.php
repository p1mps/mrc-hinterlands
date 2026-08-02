<?php

namespace App\Tests\Unit\Service;

use App\Entity\Contract;
use App\Entity\MercenaryCompany;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\RepositoryFactory;
use App\Service\DashboardService;
use PHPUnit\Framework\TestCase;

class DashboardServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private DashboardService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new DashboardService($this->em);
    }

    private function makeCompany(int $id = 1, string $name = 'Test Co', int $supportPoints = 100): MercenaryCompany
    {
        $company = $this->createStub(MercenaryCompany::class);
        $company->method('getId')->willReturn($id);
        $company->method('getName')->willReturn($name);
        $company->method('getSupportPointsBalance')->willReturn($supportPoints);
        return $company;
    }

    private function makeContract(string $status = 'active'): Contract
    {
        $contract = $this->createStub(Contract::class);
        $contract->method('getStatus')->willReturn(\App\Enum\ContractStatus::from($status));
        return $contract;
    }

    // ── getActiveContracts ────────────────────────────────────────────────

    public function testGetActiveContractsReturnsOnlyActive(): void
    {
        $repo = $this->createStub(\Doctrine\ORM\EntityRepository::class);
        $repo->method('findBy')->willReturn([$this->makeContract('active')]);
        $this->em->method('getRepository')->willReturn($repo);

        $result = $this->service->getActiveContracts();
        $this->assertCount(1, $result);
    }

    public function testGetActiveContractsReturnsEmptyWhenNone(): void
    {
        $repo = $this->createStub(\Doctrine\ORM\EntityRepository::class);
        $repo->method('findBy')->willReturn([]);
        $this->em->method('getRepository')->willReturn($repo);

        $this->assertEquals([], $this->service->getActiveContracts());
    }

    // ── getAllCompanies ───────────────────────────────────────────────────

    public function testGetAllCompaniesReturnsAll(): void
    {
        $companies = [$this->makeCompany(1, 'Alpha', 200), $this->makeCompany(2, 'Beta', 150)];
        $repo = $this->createStub(\Doctrine\ORM\EntityRepository::class);
        $repo->method('findAll')->willReturn($companies);
        $this->em->method('getRepository')->willReturn($repo);

        $result = $this->service->getAllCompanies();
        $this->assertCount(2, $result);
        $this->assertEquals('Alpha', $result[0]->getName());
    }

    public function testGetAllCompaniesReturnsEmpty(): void
    {
        $repo = $this->createStub(\Doctrine\ORM\EntityRepository::class);
        $repo->method('findAll')->willReturn([]);
        $this->em->method('getRepository')->willReturn($repo);

        $this->assertEquals([], $this->service->getAllCompanies());
    }

    // ── getCompaniesWithSupportPoints ─────────────────────────────────────

    public function testGetCompaniesWithSupportPointsSingleCompany(): void
    {
        $companies = [$this->makeCompany(1, 'Solo', 500)];
        $result = $this->service->getCompaniesWithSupportPoints($companies);
        $this->assertCount(1, $result);
        $this->assertEquals('Solo', $result[0]['company']->getName());
        $this->assertEquals(500, $result[0]['supportPoints']);
    }

    public function testGetCompaniesWithSupportPointsMultipleSorted(): void
    {
        $companies = [
            $this->makeCompany(1, 'Low', 100),
            $this->makeCompany(2, 'High', 500),
            $this->makeCompany(3, 'Mid', 300),
        ];
        $result = $this->service->getCompaniesWithSupportPoints($companies);
        $this->assertCount(3, $result);
        $this->assertEquals('High', $result[0]['company']->getName());
        $this->assertEquals(500, $result[0]['supportPoints']);
        $this->assertEquals('Mid', $result[1]['company']->getName());
        $this->assertEquals(300, $result[1]['supportPoints']);
        $this->assertEquals('Low', $result[2]['company']->getName());
        $this->assertEquals(100, $result[2]['supportPoints']);
    }

    public function testGetCompaniesWithSupportPointsWithTies(): void
    {
        $companies = [
            $this->makeCompany(1, 'A', 100),
            $this->makeCompany(2, 'B', 100),
            $this->makeCompany(3, 'C', 200),
        ];
        $result = $this->service->getCompaniesWithSupportPoints($companies);
        $this->assertCount(3, $result);
        $this->assertEquals('C', $result[0]['company']->getName());
        $this->assertEquals(200, $result[0]['supportPoints']);
        // A and B are tied; order is stable from usort
        $this->assertEquals(100, $result[1]['supportPoints']);
        $this->assertEquals(100, $result[2]['supportPoints']);
    }

    public function testGetCompaniesWithSupportPointsEmpty(): void
    {
        $result = $this->service->getCompaniesWithSupportPoints([]);
        $this->assertEquals([], $result);
    }
}
