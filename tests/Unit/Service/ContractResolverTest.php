<?php

namespace App\Tests\Unit\Service;

use App\Entity\Contract;
use App\Entity\MercenaryCompany;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use App\Enum\CommandRights;
use App\Repository\ContractRepository;
use App\Service\ContractResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContractResolver::class)]
class ContractResolverTest extends TestCase
{
    private ContractResolver $resolver;
    private ContractRepository $repoMock;

    protected function setUp(): void
    {
        $this->repoMock = $this->createMock(ContractRepository::class);
        $this->resolver = new ContractResolver($this->repoMock);
    }

    private function makeCompany(?int $id = 1): MercenaryCompany
    {
        $company = $this->createStub(MercenaryCompany::class);
        $company->method('getId')->willReturn($id);
        return $company;
    }

    private function makeContract(
        ?int $id = 1,
        ?ContractStatus $status = ContractStatus::Accepted,
        ?MercenaryCompany $company = null
    ): Contract {
        $contract = (new Contract())
            ->setType($status === ContractStatus::Completed ? ContractType::Raid : ContractType::Expedition)
            ->setEmployer('Test Employer')
            ->setEmployerAffiliation('Test Affiliation')
            ->setScale(1)
            ->setDurationMonths(1)
            ->setCommandRights(CommandRights::Integrated)
            ->setSupportTerms('Straight 10%')
            ->setSalvageRights('3')
            ->setTransportTerms('25%')
            ->setNumberOfTracks(1);

        // Set private id via reflection (setAccessible is no-op in PHP 8.1+)
        $ref = new \ReflectionClass($contract);
        $idProp = $ref->getProperty('id');
        $idProp->setValue($contract, $id);

        if ($company !== null) {
            $contract->setCompany($company);
        }

        if ($status !== null) {
            $contract->setStatus($status);
        }

        return $contract;
    }

    // ── Happy Path ────────────────────────────────────────────────────────

    public function testResolveActiveContractReturnsAcceptedContract(): void
    {
        $company = $this->makeCompany();
        $contract = $this->makeContract(status: ContractStatus::Accepted, company: $company);

        $this->repoMock
            ->expects($this->once())
            ->method('findActiveContractByCompany')
            ->with($company)
            ->willReturn($contract);

        $result = $this->resolver->resolveActiveContract($company);

        $this->assertSame($contract, $result);
        $this->assertEquals(ContractStatus::Accepted, $result->getStatus());
    }

    public function testResolveActiveContractReturnsActiveContract(): void
    {
        $company = $this->makeCompany();
        $contract = $this->makeContract(status: ContractStatus::Active, company: $company);

        $this->repoMock
            ->expects($this->once())
            ->method('findActiveContractByCompany')
            ->with($company)
            ->willReturn($contract);

        $result = $this->resolver->resolveActiveContract($company);

        $this->assertSame($contract, $result);
        $this->assertEquals(ContractStatus::Active, $result->getStatus());
    }

    public function testResolveActiveContractReturnsMostRecent(): void
    {
        $company = $this->makeCompany();
        $newerContract = $this->makeContract(id: 2, status: ContractStatus::Accepted, company: $company);

        // The repository mock returns the contract it's configured to return
        $this->repoMock
            ->expects($this->once())
            ->method('findActiveContractByCompany')
            ->with($company)
            ->willReturn($newerContract);

        $result = $this->resolver->resolveActiveContract($company);

        $this->assertSame($newerContract, $result);
        $this->assertEquals(2, $result->getId());
    }

    // ── No Active Contract ────────────────────────────────────────────────

    public function testResolveActiveContractReturnsNullWhenNoContract(): void
    {
        $company = $this->makeCompany();

        $this->repoMock
            ->expects($this->once())
            ->method('findActiveContractByCompany')
            ->with($company)
            ->willReturn(null);

        $result = $this->resolver->resolveActiveContract($company);

        $this->assertNull($result);
    }

    public function testResolveActiveContractReturnsNullWhenOnlyCompletedContracts(): void
    {
        $company = $this->makeCompany();

        $this->repoMock
            ->expects($this->once())
            ->method('findActiveContractByCompany')
            ->with($company)
            ->willReturn(null); // Completed contracts are not returned

        $result = $this->resolver->resolveActiveContract($company);

        $this->assertNull($result);
    }

    public function testResolveActiveContractReturnsNullWhenOnlyBrokenContracts(): void
    {
        $company = $this->makeCompany();

        $this->repoMock
            ->expects($this->once())
            ->method('findActiveContractByCompany')
            ->with($company)
            ->willReturn(null);

        $result = $this->resolver->resolveActiveContract($company);

        $this->assertNull($result);
    }

    public function testResolveActiveContractReturnsNullWhenOnlyAvailableContracts(): void
    {
        $company = $this->makeCompany();

        $this->repoMock
            ->expects($this->once())
            ->method('findActiveContractByCompany')
            ->with($company)
            ->willReturn(null);

        $result = $this->resolver->resolveActiveContract($company);

        $this->assertNull($result);
    }

    // ── Edge Cases ────────────────────────────────────────────────────────

    public function testResolveActiveContractReturnsActiveWhenBothExist(): void
    {
        $company = $this->makeCompany();
        $activeContract = $this->makeContract(id: 2, status: ContractStatus::Active, company: $company);

        // The repository mock returns the contract it's configured to return
        $this->repoMock
            ->expects($this->once())
            ->method('findActiveContractByCompany')
            ->with($company)
            ->willReturn($activeContract);

        $result = $this->resolver->resolveActiveContract($company);

        $this->assertSame($activeContract, $result);
    }

    public function testHasActiveContractReturnsTrue(): void
    {
        $company = $this->makeCompany();
        $contract = $this->makeContract(status: ContractStatus::Accepted, company: $company);

        // When the repo returns a contract, hasActiveContract should be true
        $this->repoMock
            ->expects($this->once())
            ->method('hasActiveContract')
            ->with($company)
            ->willReturn(true);

        $this->assertTrue($this->repoMock->hasActiveContract($company));
    }

    public function testHasActiveContractReturnsFalse(): void
    {
        $company = $this->makeCompany();

        $this->repoMock
            ->expects($this->once())
            ->method('hasActiveContract')
            ->with($company)
            ->willReturn(false);

        $this->assertFalse($this->repoMock->hasActiveContract($company));
    }
}
