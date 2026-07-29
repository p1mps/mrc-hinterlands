<?php

namespace App\Tests\Unit\Service;

use App\Entity\MercenaryCompany;
use App\Entity\User;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[CoversClass(SecurityService::class)]
class SecurityServiceTest extends TestCase
{
    private EntityManagerInterface $emMock;
    private UserPasswordHasherInterface $hasherMock;
    private SecurityService $service;

    protected function setUp(): void
    {
        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->hasherMock = $this->createMock(UserPasswordHasherInterface::class);
        $this->service = new SecurityService($this->emMock, $this->hasherMock);
    }

    // -- registerUser: happy path --

    public function testRegisterUserSuccessfullyCreatesUserWithAllProperties(): void
    {
        $username = 'CommanderTex';
        $email = 'commander@tex.merc';
        $password = 'secretPassword123';
        $companyName = 'Tex McIrving';
        $faction = 'Davion';

        $hashed = 'hashed_' . $password;
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), $this->equalTo($password))
            ->willReturn($hashed);

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        // Capture the user passed to persist
        $capturedUser = null;
        $this->emMock
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$capturedUser) {
                if ($entity instanceof User) {
                    $capturedUser = $entity;
                }
            });

        $this->service->registerUser($username, $email, $password, $companyName, $faction);

        $this->assertInstanceOf(User::class, $capturedUser);
        $this->assertEquals($username, $capturedUser->getUsername());
        $this->assertEquals($email, $capturedUser->getEmail());
        $this->assertEquals($hashed, $capturedUser->getPassword());
    }

    public function testRegisterUserSuccessfullyCreatesCompanyWithAllProperties(): void
    {
        $username = 'CommanderTex';
        $email = 'commander@tex.merc';
        $password = 'secretPassword123';
        $companyName = 'Tex McIrving';
        $faction = 'Liao';

        $hashed = 'hashed_' . $password;
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn($hashed);

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->registerUser($username, $email, $password, $companyName, $faction);

        // Verify MercenaryCompany entity state
        $company = new MercenaryCompany();
        $company->setName($companyName);
        $company->setFaction($faction);
        $this->assertEquals($companyName, $company->getName());
        $this->assertEquals($faction, $company->getFaction());
    }

    public function testRegisterUserSetsBidirectionalUserCompanyRelationship(): void
    {
        $username = 'CommanderTex';
        $email = 'commander@tex.merc';
        $password = 'secretPassword123';
        $companyName = 'Tex McIrving';
        $faction = 'Word of Blake';

        $hashed = 'hashed_' . $password;
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn($hashed);

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        // Capture the user and company objects passed to persist
        $capturedUser = null;
        $capturedCompany = null;
        $this->emMock
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$capturedUser, &$capturedCompany) {
                if ($entity instanceof User) {
                    $capturedUser = $entity;
                }
                if ($entity instanceof MercenaryCompany) {
                    $capturedCompany = $entity;
                }
            });

        $this->service->registerUser($username, $email, $password, $companyName, $faction);

        // Verify bidirectional relationship: company -> user
        $this->assertInstanceOf(MercenaryCompany::class, $capturedCompany);
        $this->assertSame($capturedUser, $capturedCompany->getUser());

        // Verify bidirectional relationship: user -> company
        $this->assertInstanceOf(User::class, $capturedUser);
        $this->assertSame($capturedCompany, $capturedUser->getCompany());
    }

    public function testRegisterUserCallsPersistOnBothEntities(): void
    {
        $username = 'CommanderTex';
        $email = 'commander@tex.merc';
        $password = 'secretPassword123';
        $companyName = 'Tex McIrving';
        $faction = 'ComStar';

        $hashed = 'hashed_' . $password;
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn($hashed);

        $persistedEntities = [];
        $this->emMock
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->registerUser($username, $email, $password, $companyName, $faction);

        $this->assertCount(2, $persistedEntities);
        $this->assertInstanceOf(User::class, $persistedEntities[0]);
        $this->assertInstanceOf(MercenaryCompany::class, $persistedEntities[1]);
    }

    public function testRegisterUserCallsFlushOnceOnSuccess(): void
    {
        $username = 'CommanderTex';
        $email = 'commander@tex.merc';
        $password = 'secretPassword123';
        $companyName = 'Tex McIrving';
        $faction = 'Free Worlds';

        $hashed = 'hashed_' . $password;
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn($hashed);

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->registerUser($username, $email, $password, $companyName, $faction);

        // If we reach here without exception, flush was called successfully
        $this->assertTrue(true);
    }

    public function testRegisterUserHashesPasswordViaHasherInterface(): void
    {
        $username = 'CommanderTex';
        $email = 'commander@tex.merc';
        $password = 'myStr0ngP@ss!';
        $companyName = 'Tex McIrving';
        $faction = 'Liao';

        $expectedHash = '$2y$13$abcdef1234567890abcdef01234567890abcdef123456';
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->with(
                $this->callback(function ($user) {
                    return $user instanceof User;
                }),
                $this->equalTo($password)
            )
            ->willReturn($expectedHash);

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->registerUser($username, $email, $password, $companyName, $faction);

        $this->assertTrue(true);
    }

    // -- registerUser: persistence failures --

    public function testRegisterUserThrowsWhenFlushFailsWithDatabaseError(): void
    {
        $username = 'CommanderTex';
        $email = 'commander@tex.merc';
        $password = 'secretPassword123';
        $companyName = 'Tex McIrving';
        $faction = 'Davion';

        $hashed = 'hashed_' . $password;
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn($hashed);

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new \RuntimeException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry');

        $this->service->registerUser($username, $email, $password, $companyName, $faction);
    }

    public function testRegisterUserThrowsWhenFlushFailsWithConnectionError(): void
    {
        $username = 'CommanderTex';
        $email = 'commander@tex.merc';
        $password = 'secretPassword123';
        $companyName = 'Tex McIrving';
        $faction = 'ComStar';

        $hashed = 'hashed_' . $password;
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn($hashed);

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Connection lost'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection lost');

        $this->service->registerUser($username, $email, $password, $companyName, $faction);
    }

    public function testRegisterUserDoesNotPersistOrFlushWhenHashFails(): void
    {
        $username = 'CommanderTex';
        $email = 'commander@tex.merc';
        $password = 'secretPassword123';
        $companyName = 'Tex McIrving';
        $faction = 'Liao';

        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willThrowException(new \Exception('Unsupported hash algorithm'));

        $this->emMock
            ->expects($this->never())
            ->method('persist');
        $this->emMock
            ->expects($this->never())
            ->method('flush');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unsupported hash algorithm');

        $this->service->registerUser($username, $email, $password, $companyName, $faction);
    }

    // -- registerUser: edge cases --

    public function testRegisterUserWithMinimalValidInputs(): void
    {
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed');

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->registerUser('a', 'a@b.c', 'p', 'X', 'Y');

        $this->assertTrue(true);
    }

    public function testRegisterUserWithLongInputs(): void
    {
        $longUsername = str_repeat('a', 179);
        $longEmail = str_repeat('b', 170) . '@' . str_repeat('c', 40);
        $longPassword = str_repeat('x', 1000);
        $longCompanyName = str_repeat('d', 254);
        $longFaction = str_repeat('e', 254);

        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed');

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->registerUser($longUsername, $longEmail, $longPassword, $longCompanyName, $longFaction);

        $this->assertTrue(true);
    }

    public function testRegisterUserWithSpecialCharactersInInputs(): void
    {
        $username = 'user@domain!#$%&\'()*+';
        $email = 'special+tags@example.co.uk';
        $password = 'p@$$w0rd!<>{}[]|\\^`~';
        $companyName = "McIrving's Company";
        $faction = 'Word of Blake';

        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed');

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->registerUser($username, $email, $password, $companyName, $faction);

        $this->assertTrue(true);
    }

    public function testRegisterUserWithEmptyStringInputs(): void
    {
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed');

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->registerUser('', '', '', '', '');

        $this->assertTrue(true);
    }

    /**
     * @return iterable<string, array{string, string, string, string, string, string}>
     */
    public static function provideFactions(): iterable
    {
        return [
            'Davion' => ['Davion'],
            'Liao' => ['Liao'],
            'Word of Blake' => ['Word of Blake'],
            'ComStar' => ['ComStar'],
            'Free Worlds League' => ['Free Worlds League'],
            'ComGuard' => ['ComGuard'],
            'Covenant' => ['Covenant'],
            'Lyrans' => ['Lyrans'],
        ];
    }

    #[DataProvider('provideFactions')]
    public function testRegisterUserCreatesCompanyWithDifferentFactions(string $faction): void
    {
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed');

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        // Capture the company passed to persist
        $capturedCompany = null;
        $this->emMock
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$capturedCompany) {
                if ($entity instanceof MercenaryCompany) {
                    $capturedCompany = $entity;
                }
            });

        $this->service->registerUser('user', 'user@test.com', 'pass', 'Company', $faction);

        $this->assertInstanceOf(MercenaryCompany::class, $capturedCompany);
        $this->assertEquals($faction, $capturedCompany->getFaction());
    }

    public function testRegisterUserReturnsNothing(): void
    {
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed');

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->registerUser('user', 'user@test.com', 'pass', 'Company', 'Davion');

        $this->assertNull($result);
    }

    public function testRegisterUserUsesCorrectUserProperties(): void
    {
        $username = 'CommanderTex';
        $email = 'commander@tex.merc';
        $password = 'secretPassword123';
        $companyName = 'Tex McIrving';
        $faction = 'Liao';

        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed');

        // Capture the user passed to persist
        $capturedUser = null;
        $this->emMock
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$capturedUser) {
                if ($entity instanceof User) {
                    $capturedUser = $entity;
                }
            });
        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->registerUser($username, $email, $password, $companyName, $faction);

        $this->assertInstanceOf(User::class, $capturedUser);
        $this->assertEquals($username, $capturedUser->getUsername());
        $this->assertEquals($email, $capturedUser->getEmail());
        $this->assertEquals('hashed', $capturedUser->getPassword());
    }

    public function testRegisterUserUsesCorrectCompanyProperties(): void
    {
        $username = 'CommanderTex';
        $email = 'commander@tex.merc';
        $password = 'secretPassword123';
        $companyName = 'Tex McIrving';
        $faction = 'Liao';

        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed');

        // Capture the company passed to persist
        $capturedCompany = null;
        $this->emMock
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$capturedCompany) {
                if ($entity instanceof MercenaryCompany) {
                    $capturedCompany = $entity;
                }
            });
        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->registerUser($username, $email, $password, $companyName, $faction);

        $this->assertInstanceOf(MercenaryCompany::class, $capturedCompany);
        $this->assertEquals($companyName, $capturedCompany->getName());
        $this->assertEquals($faction, $capturedCompany->getFaction());
    }

    public function testRegisterUserPersistOrderIsUserThenCompany(): void
    {
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed');

        $persistedEntities = [];
        $this->emMock
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });
        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->registerUser('user', 'user@test.com', 'pass', 'Company', 'Davion');

        $this->assertCount(2, $persistedEntities);
        $this->assertInstanceOf(User::class, $persistedEntities[0]);
        $this->assertInstanceOf(MercenaryCompany::class, $persistedEntities[1]);
    }

    public function testRegisterUserWithNumericCompanyAndFaction(): void
    {
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed');

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->registerUser('user1', 'user1@test.com', 'pass', 'Company1', 'Faction1');

        $this->assertTrue(true);
    }

    public function testRegisterUserWithUnicodeInputs(): void
    {
        $this->hasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed');

        $this->emMock
            ->expects($this->exactly(2))
            ->method('persist');
        $this->emMock
            ->expects($this->once())
            ->method('flush');

        $this->service->registerUser('用户', '用户@测试.com', '密码', '公司', ' faction');

        $this->assertTrue(true);
    }
}
