<?php

namespace App\Tests\Unit\Service;

use App\Entity\MercenaryCompany;
use App\Entity\SupportPointEntry;
use App\Service\SupportPointService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SupportPointService::class)]
class SupportPointServiceTest extends TestCase
{
    private EntityManagerInterface $emMock;
    private SupportPointService $service;

    protected function setUp(): void
    {
        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->service = new SupportPointService($this->emMock);
    }

    // ── getCompanySupportPoints ──────────────────────────────────────────

    public function testGetCompanySupportPointsReturnsStructuredArray(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Davion');

        $result = $this->service->getCompanySupportPoints($company);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('company', $result);
        $this->assertArrayHasKey('entries', $result);
        $this->assertArrayHasKey('balance', $result);
        $this->assertSame($company, $result['company']);
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result['entries']);
        $this->assertIsInt($result['balance']);
    }

    public function testGetCompanySupportPointsReturnsEmptyEntriesWhenCompanyHasNone(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Empty Company');
        $company->setFaction('ComStar');

        $result = $this->service->getCompanySupportPoints($company);

        $this->assertIsArray($result);
        $this->assertCount(0, $result['entries']);
        $this->assertEquals(0, $result['balance']);
    }

    public function testGetCompanySupportPointsCalculatesBalanceFromEntries(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Wealthy Company');
        $company->setFaction('Liao');

        $entry1 = new SupportPointEntry();
        $entry1->setAmount(10);
        $entry1->setCompany($company);
        $company->getSupportPointEntries()->add($entry1);

        $entry2 = new SupportPointEntry();
        $entry2->setAmount(-3);
        $entry2->setCompany($company);
        $company->getSupportPointEntries()->add($entry2);

        $entry3 = new SupportPointEntry();
        $entry3->setAmount(7);
        $entry3->setCompany($company);
        $company->getSupportPointEntries()->add($entry3);

        $result = $this->service->getCompanySupportPoints($company);

        $this->assertEquals(14, $result['balance']);
        $this->assertCount(3, $result['entries']);
    }

    public function testGetCompanySupportPointsWithNegativeBalance(): void
    {
        $company = new MercenaryCompany();
        $company->setName('In Debt Company');
        $company->setFaction('Covenant');

        $entry1 = new SupportPointEntry();
        $entry1->setAmount(-15);
        $entry1->setCompany($company);
        $company->getSupportPointEntries()->add($entry1);

        $entry2 = new SupportPointEntry();
        $entry2->setAmount(-5);
        $entry2->setCompany($company);
        $company->getSupportPointEntries()->add($entry2);

        $result = $this->service->getCompanySupportPoints($company);

        $this->assertEquals(-20, $result['balance']);
    }

    public function testGetCompanySupportPointsWithLargeNumberOfEntries(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Large Company');
        $company->setFaction('Word of Blake');

        for ($i = 0; $i < 50; $i++) {
            $entry = new SupportPointEntry();
            $entry->setAmount($i % 2 === 0 ? 5 : -2);
            $entry->setCompany($company);
            $company->getSupportPointEntries()->add($entry);
        }

        $result = $this->service->getCompanySupportPoints($company);

        // 25 entries of +5 = 125, 25 entries of -2 = -50 → balance = 75
        $this->assertEquals(75, $result['balance']);
        $this->assertCount(50, $result['entries']);
    }

    public function testGetCompanySupportPointsReturnsSameCollectionAsOnCompany(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Free Worlds');

        $entry = new SupportPointEntry();
        $entry->setAmount(10);
        $entry->setCompany($company);
        $company->getSupportPointEntries()->add($entry);

        $result = $this->service->getCompanySupportPoints($company);

        $this->assertSame($company->getSupportPointEntries(), $result['entries']);
    }

    public function testGetCompanySupportPointsWithZeroAmountEntries(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Davion');

        $entry = new SupportPointEntry();
        $entry->setAmount(0);
        $entry->setCompany($company);
        $company->getSupportPointEntries()->add($entry);

        $result = $this->service->getCompanySupportPoints($company);

        $this->assertEquals(0, $result['balance']);
        $this->assertCount(1, $result['entries']);
    }

    // ── addEntry ─────────────────────────────────────────────────────────

    public function testAddEntryCallsPersistAndFlush(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Davion');

        $this->emMock
            ->expects($this->once())
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->addEntry($company, 5, 'Bonus points');

        $this->assertInstanceOf(SupportPointEntry::class, $result);
    }

    public function testAddEntryReturnsCreatedEntry(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Davion');

        $this->emMock
            ->expects($this->once())
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->addEntry($company, 5, 'Bonus points');

        // The returned entry is a new instance created by the service
        $this->assertInstanceOf(SupportPointEntry::class, $result);
        $this->assertEquals(5, $result->getAmount());
        $this->assertEquals('Bonus points', $result->getDescription());
        $this->assertEquals($company, $result->getCompany());
    }

    public function testAddEntryWithNegativeAmount(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Liao');

        $this->emMock
            ->expects($this->once())
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->addEntry($company, -3, 'Penalty');

        $this->assertInstanceOf(SupportPointEntry::class, $result);
        $this->assertEquals(-3, $result->getAmount());
        $this->assertEquals('Penalty', $result->getDescription());
    }

    public function testAddEntryWithZeroAmount(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('ComStar');

        $this->emMock
            ->expects($this->once())
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->addEntry($company, 0, 'Adjustment');

        $this->assertInstanceOf(SupportPointEntry::class, $result);
        $this->assertEquals(0, $result->getAmount());
        $this->assertEquals('Adjustment', $result->getDescription());
    }

    public function testAddEntryWithVeryLargeAmount(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('ComGuard');

        $this->emMock
            ->expects($this->once())
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->addEntry($company, 999999, 'Jackpot');

        $this->assertInstanceOf(SupportPointEntry::class, $result);
        $this->assertEquals(999999, $result->getAmount());
    }

    public function testAddEntryWithVeryLongDescription(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Lyrans');

        $longDescription = str_repeat('A', 255);

        $this->emMock
            ->expects($this->once())
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->addEntry($company, 1, $longDescription);

        $this->assertInstanceOf(SupportPointEntry::class, $result);
        $this->assertEquals($longDescription, $result->getDescription());
    }

    public function testAddEntrySetsCompanyOnTheEntry(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Word of Blake');

        $this->emMock
            ->expects($this->once())
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->addEntry($company, 5, 'Test');

        $this->assertSame($company, $result->getCompany());
    }

    public function testAddEntryThrowsWhenPersistFails(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Davion');

        $this->emMock
            ->expects($this->once())
            ->method('persist')
            ->willThrowException(new \RuntimeException('Database connection lost'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database connection lost');

        $this->service->addEntry($company, 5, 'Test');
    }

    public function testAddEntryThrowsWhenFlushFails(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Liao');

        $this->emMock
            ->expects($this->once())
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Constraint violation'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Constraint violation');

        $this->service->addEntry($company, 5, 'Test');
    }

    public function testAddEntryWithMinimalCompany(): void
    {
        $company = new MercenaryCompany();

        $this->emMock
            ->expects($this->once())
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->addEntry($company, 1, 'Minimal');

        $this->assertInstanceOf(SupportPointEntry::class, $result);
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function provideEntryParameters(): iterable
    {
        return [
            'positive amount' => [10, 'Reward'],
            'negative amount' => [-5, 'Penalty'],
            'zero amount' => [0, 'Adjustment'],
            'large positive' => [9999, 'Mega Bonus'],
            'large negative' => [-9999, 'Mega Penalty'],
            'max int amount' => [PHP_INT_MAX, 'Max Int'],
            'min int amount' => [PHP_INT_MIN, 'Min Int'],
        ];
    }

    #[DataProvider('provideEntryParameters')]
    public function testAddEntryWithVariousAmounts(int $amount, string $description): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Davion');

        $this->emMock
            ->expects($this->once())
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->addEntry($company, $amount, $description);

        $this->assertInstanceOf(SupportPointEntry::class, $result);
        $this->assertEquals($amount, $result->getAmount());
        $this->assertEquals($description, $result->getDescription());
    }

    // ── deleteEntry ──────────────────────────────────────────────────────

    public function testDeleteEntryCallsRemoveAndFlush(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Davion');

        $entry = new SupportPointEntry();
        $entry->setAmount(5);
        $entry->setCompany($company);
        $entry->setDescription('To delete');

        $this->emMock
            ->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($entry));
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->deleteEntry($entry);
    }

    public function testDeleteEntryRemovesTheCorrectEntry(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Davion');

        $entry = new SupportPointEntry();
        $entry->setAmount(5);
        $entry->setCompany($company);
        $entry->setDescription('To delete');

        $capturedEntry = null;
        $this->emMock
            ->method('remove')
            ->willReturnCallback(function ($entity) use (&$capturedEntry) {
                $capturedEntry = $entity;
            });
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->deleteEntry($entry);

        $this->assertSame($entry, $capturedEntry);
    }

    public function testDeleteEntryDoesNotReturnAnything(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Davion');

        $entry = new SupportPointEntry();
        $entry->setAmount(5);
        $entry->setCompany($company);
        $entry->setDescription('To delete');

        $this->emMock
            ->expects($this->once())
            ->method('remove');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->deleteEntry($entry);

        $this->assertNull($result);
    }

    public function testDeleteEntryWithNegativeAmountEntry(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Liao');

        $entry = new SupportPointEntry();
        $entry->setAmount(-3);
        $entry->setCompany($company);
        $entry->setDescription('Penalty entry');

        $this->emMock
            ->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($entry));
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->deleteEntry($entry);
    }

    public function testDeleteEntryWithZeroAmountEntry(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('ComStar');

        $entry = new SupportPointEntry();
        $entry->setAmount(0);
        $entry->setCompany($company);
        $entry->setDescription('Zero entry');

        $this->emMock
            ->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($entry));
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->deleteEntry($entry);
    }

    public function testDeleteEntryWithVeryLargeAmountEntry(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('ComGuard');

        $entry = new SupportPointEntry();
        $entry->setAmount(999999);
        $entry->setCompany($company);
        $entry->setDescription('Huge entry');

        $this->emMock
            ->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($entry));
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->deleteEntry($entry);
    }

    public function testDeleteEntryThrowsWhenRemoveFails(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Davion');

        $entry = new SupportPointEntry();
        $entry->setAmount(5);
        $entry->setCompany($company);
        $entry->setDescription('Failing delete');

        $this->emMock
            ->expects($this->once())
            ->method('remove')
            ->willThrowException(new \RuntimeException('Foreign key constraint'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Foreign key constraint');

        $this->service->deleteEntry($entry);
    }

    public function testDeleteEntryThrowsWhenFlushFails(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Liao');

        $entry = new SupportPointEntry();
        $entry->setAmount(5);
        $entry->setCompany($company);
        $entry->setDescription('Flush failing delete');

        $this->emMock
            ->expects($this->once())
            ->method('remove');
        $this->emMock
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Lock timeout'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Lock timeout');

        $this->service->deleteEntry($entry);
    }

    public function testDeleteEntryWithLongDescription(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Word of Blake');

        $longDescription = str_repeat('B', 255);

        $entry = new SupportPointEntry();
        $entry->setAmount(1);
        $entry->setCompany($company);
        $entry->setDescription($longDescription);

        $this->emMock
            ->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($entry));
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->deleteEntry($entry);
    }

    // ── Integration: addEntry + getCompanySupportPoints ──────────────────

    public function testWorkflowAddEntryThenReadBalanceWithPrepopulatedEntries(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Growing Company');
        $company->setFaction('Davion');

        // Pre-populate the company's collection (as Doctrine cascade would do)
        $entry1 = new SupportPointEntry();
        $entry1->setAmount(10);
        $entry1->setCompany($company);
        $company->getSupportPointEntries()->add($entry1);

        $entry2 = new SupportPointEntry();
        $entry2->setAmount(20);
        $entry2->setCompany($company);
        $company->getSupportPointEntries()->add($entry2);

        // Read initial balance
        $initialResult = $this->service->getCompanySupportPoints($company);
        $this->assertEquals(30, $initialResult['balance']);
        $this->assertCount(2, $initialResult['entries']);

        // Add a new entry through the service (persisted via EM, not added to collection)
        $this->emMock
            ->expects($this->once())
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $newEntry = $this->service->addEntry($company, 15, 'New funding');

        // The returned entry has correct properties
        $this->assertEquals(15, $newEntry->getAmount());
        $this->assertEquals('New funding', $newEntry->getDescription());

        // The company's collection still has the original 2 entries
        // (addEntry persists to EM but doesn't modify the collection directly)
        $updatedResult = $this->service->getCompanySupportPoints($company);
        $this->assertEquals(30, $updatedResult['balance']);
        $this->assertCount(2, $updatedResult['entries']);
    }

    public function testWorkflowAddEntryThenReadBalanceMultipleEntries(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Active Company');
        $company->setFaction('Liao');

        // Pre-populate with 3 entries
        for ($i = 0; $i < 3; $i++) {
            $entry = new SupportPointEntry();
            $entry->setAmount(10);
            $entry->setCompany($company);
            $company->getSupportPointEntries()->add($entry);
        }

        $result = $this->service->getCompanySupportPoints($company);
        $this->assertEquals(30, $result['balance']);

        // Add 2 more entries through service
        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->exactly(2))
            ->method('flush');

        $this->service->addEntry($company, 5, 'Extra 1');
        $this->service->addEntry($company, 8, 'Extra 2');

        // Company's collection unchanged (service persists, doesn't add to collection)
        $result = $this->service->getCompanySupportPoints($company);
        $this->assertEquals(30, $result['balance']);
        $this->assertCount(3, $result['entries']);
    }

    public function testWorkflowMixedEntriesWithPrepopulatedData(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Mixed Company');
        $company->setFaction('ComStar');

        // Pre-populate with mixed positive and negative entries
        $posEntry = new SupportPointEntry();
        $posEntry->setAmount(50);
        $posEntry->setCompany($company);
        $company->getSupportPointEntries()->add($posEntry);

        $negEntry = new SupportPointEntry();
        $negEntry->setAmount(-10);
        $negEntry->setCompany($company);
        $company->getSupportPointEntries()->add($negEntry);

        $result = $this->service->getCompanySupportPoints($company);
        $this->assertEquals(40, $result['balance']);

        // Add entries through service
        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->exactly(2))
            ->method('flush');

        $this->service->addEntry($company, 20, 'Bonus');
        $this->service->addEntry($company, -5, 'Fine');

        $result = $this->service->getCompanySupportPoints($company);
        $this->assertEquals(40, $result['balance']);
    }

    // ── Integration: addEntry + deleteEntry ──────────────────────────────

    public function testWorkflowDeleteEntryFromPrepopulatedCollection(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Davion');

        // Pre-populate with 3 entries
        $entry1 = new SupportPointEntry();
        $entry1->setAmount(10);
        $entry1->setCompany($company);
        $company->getSupportPointEntries()->add($entry1);

        $entry2 = new SupportPointEntry();
        $entry2->setAmount(20);
        $entry2->setCompany($company);
        $company->getSupportPointEntries()->add($entry2);

        $entry3 = new SupportPointEntry();
        $entry3->setAmount(30);
        $entry3->setCompany($company);
        $company->getSupportPointEntries()->add($entry3);

        // Delete the middle entry (EM is mocked, collection untouched)
        $this->emMock
            ->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($entry2));
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->deleteEntry($entry2);

        // Since EM is mocked, all 3 entries remain in the collection (60)
        $result = $this->service->getCompanySupportPoints($company);
        $this->assertEquals(60, $result['balance']);
        $this->assertCount(3, $result['entries']);
    }

    public function testWorkflowDeleteAllEntriesLeavesZeroBalance(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Liao');

        // Pre-populate with 2 entries
        $entry1 = new SupportPointEntry();
        $entry1->setAmount(5);
        $entry1->setCompany($company);
        $company->getSupportPointEntries()->add($entry1);

        $entry2 = new SupportPointEntry();
        $entry2->setAmount(3);
        $entry2->setCompany($company);
        $company->getSupportPointEntries()->add($entry2);

        // Delete both entries (EM is mocked, so collection is untouched)
        $this->emMock
            ->expects($this->exactly(2))
            ->method('remove');
        $this->emMock
            ->expects($this->exactly(2))
            ->method('flush');

        $this->service->deleteEntry($entry1);
        $this->service->deleteEntry($entry2);

        // Since EM is mocked, the collection still has both entries
        $result = $this->service->getCompanySupportPoints($company);
        $this->assertCount(2, $result['entries']);
        $this->assertEquals(8, $result['balance']);
    }

    public function testWorkflowAddThenDeleteSingleEntry(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('ComGuard');

        // Pre-populate with 1 entry
        $existingEntry = new SupportPointEntry();
        $existingEntry->setAmount(10);
        $existingEntry->setCompany($company);
        $company->getSupportPointEntries()->add($existingEntry);

        // Add a new entry through service (persist + flush)
        $this->emMock
            ->expects($this->once())
            ->method('persist');
        $this->emMock
            ->expects($this->exactly(2))
            ->method('flush');

        $newEntry = $this->service->addEntry($company, 5, 'Temporary');

        // Delete it back (remove + flush)
        $this->emMock
            ->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($newEntry));

        $this->service->deleteEntry($newEntry);

        // Since EM is mocked, the collection still has the original entry
        $result = $this->service->getCompanySupportPoints($company);
        $this->assertCount(1, $result['entries']);
        $this->assertEquals(10, $result['balance']);
        $this->assertEquals($existingEntry, $result['entries'][0]);
    }

    // ── SupportPointEntry createdAt verification ─────────────────────────

    public function testAddEntryCreatesEntryWithCurrentTimestamp(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Davion');

        $before = new \DateTimeImmutable();

        $this->emMock
            ->expects($this->once())
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->addEntry($company, 5, 'Timestamp test');

        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getCreatedAt());
        $this->assertTrue($result->getCreatedAt() >= $before);
    }

    public function testAddEntryCreatedAtIsNotMutable(): void
    {
        $company = new MercenaryCompany();
        $company->setName('Test Company');
        $company->setFaction('Davion');

        $this->emMock
            ->expects($this->once())
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->addEntry($company, 5, 'Immutability test');

        $createdAt = $result->getCreatedAt();
        $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);
        // Verify it's the same instance (no copy made)
        $this->assertSame($createdAt, $result->getCreatedAt());
    }
}
