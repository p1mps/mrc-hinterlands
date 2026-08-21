<?php

namespace App\Command;

use App\Entity\Contract;
use App\Entity\ContractLogEntry;
use App\Entity\Dropship;
use App\Entity\MercenaryCompany;
use App\Entity\Pilot;
use App\Entity\SalvagedMech;
use App\Entity\SupportPointEntry;
use App\Entity\TrackRecord;
use App\Entity\Unit;
use App\Entity\User;
use App\Enum\CombatPayTier;
use App\Enum\CommandRights;
use App\Enum\ContractLogEntryType;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use App\Enum\DamageState;
use App\Enum\TechBase;
use App\Enum\TrackStatus;
use App\Enum\UnitType;
use App\Service\TrackIntensityGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:seed-database', description: 'Seed the database with sample data for all entities')]
class SeedDatabaseCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TrackIntensityGenerator $intensityGenerator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->text('Seeding database with sample data...');

        $this->clearExistingData();

        $mainCompany = $this->createMainCompany();
        $opposingCompany = $this->createOpposingCompany();
        $dropship = $this->createDropship($mainCompany);

        [$pilots, $units] = $this->createPilotsAndUnits($mainCompany, $dropship);

        $contracts = $this->createContracts($mainCompany, $opposingCompany, $pilots);

        $this->createTrackRecords($contracts);
        $this->createContractLogEntries($contracts, $mainCompany);
        $this->createSalvagedMechs($mainCompany, $dropship, $contracts);

        $this->em->flush();

        $io->success('Database seeded successfully!');
        $io->table(
            ['Entity', 'Count'],
            [
                ['User', 1],
                ['Mercenary Company', 2],
                ['Dropship', 1],
                ['Pilots', count($pilots)],
                ['Units', count($units)],
                ['Contracts', count($contracts)],
                ['Track Records', $this->countTrackRecords($contracts)],
                ['Contract Log Entries', $this->countLogEntries($contracts)],
                ['Salvaged Mechs', $this->countSalvagedMechs()],
                ['Support Point Entries', $this->countSupportPointEntries($mainCompany)],
            ]
        );

        return Command::SUCCESS;
    }

    private function clearExistingData(): void
    {
        $conn = $this->em->getConnection();

        $tables = [
            'salvaged_mech',
            'contract_log_entry',
            'support_point_entry',
            'track_record',
            'contract',
            'unit',
            'pilot',
            'dropship',
            'mercenary_company',
            '`user`',
        ];

        foreach ($tables as $table) {
            try {
                $conn->executeStatement("DELETE FROM {$table}");
            } catch (\Throwable) {
                // Ignore if table doesn't exist
            }
        }
    }

    private function createMainCompany(): MercenaryCompany
    {
        $user = new User();
        $user->setUsername('commander_vance')
            ->setEmail('commander.vance@hinterlands.mrc')
            ->setPassword(password_hash('secretpassword', PASSWORD_BCRYPT));

        $company = new MercenaryCompany();
        $company->setUser($user)
            ->setName('Hinterlands Mercenary Company')
            ->setFaction('Canopian Commonwealth')
           ->setReputation(5);

        $this->em->persist($user);
        $this->em->persist($company);

        return $company;
    }

    private function createOpposingCompany(): MercenaryCompany
    {
        $user = new User();
        $user->setUsername('rival_commander')
            ->setEmail('rival.commander@hinterlands.mrc')
            ->setPassword(password_hash('secretpassword', PASSWORD_BCRYPT));

        $company = new MercenaryCompany();
        $company->setUser($user)
            ->setName('Iron Jaguar Mercenaries')
            ->setFaction('Cloud Cobra')
            ->setReputation(3);

        $this->em->persist($user);
        $this->em->persist($company);

        return $company;
    }

    private function createDropship(MercenaryCompany $company): Dropship
    {
        $dropship = new Dropship();
        $dropship->setCompany($company)
            ->setName('HMS Valiant')
            ->setMaxCapacity(8)
            ->setMekbayCapacity(5);

        $this->em->persist($dropship);

        return $dropship;
    }

    /**
     * @return array{0: Pilot[], 1: Unit[]}
     */
    private function createPilotsAndUnits(MercenaryCompany $company, ?Dropship $dropship): array
    {
        $pilots = [];
        $units = [];

        // Named pilot 1 - Mech pilot (gunner focus)
        $pilot1 = new Pilot();
        $pilot1->setCompany($company)
            ->setName('Alexei "Ironhand" Volkov')
            ->setIsNamed(true)
            ->setGunnery(2)
            ->setPiloting(4)
            ->setGunneryXp(850)
            ->setPilotingXp(320);
        $this->em->persist($pilot1);
        $pilots[] = $pilot1;

        $unit1 = new Unit();
        $unit1->setCompany($company)
            ->setPilot($pilot1)
            ->setName('Jagermechan JGM-101B')
            ->setChassis('Jagermechan')
            ->setTonnage(100)
            ->setBv(1000)
            ->setUnitType(UnitType::Mech)
            ->setDamageState(DamageState::None);
        $this->em->persist($unit1);
        $units[] = $unit1;

        // Named pilot 2 - Mech pilot (piloting focus)
        $pilot2 = new Pilot();
        $pilot2->setCompany($company)
            ->setName('Mira "Silkwing" Chen')
            ->setIsNamed(true)
            ->setGunnery(3)
            ->setPiloting(1)
            ->setGunneryXp(420)
            ->setPilotingXp(910);
        $this->em->persist($pilot2);
        $pilots[] = $pilot2;

        $unit2 = new Unit();
        $unit2->setCompany($company)
            ->setPilot($pilot2)
            ->setName('Grasshopper GHR-5n')
            ->setChassis('Grasshopper')
            ->setTonnage(70)
            ->setBv(750)
            ->setUnitType(UnitType::Mech)
            ->setDamageState(DamageState::None);
        $this->em->persist($unit2);
        $units[] = $unit2;

        // Named pilot 3 - Vehicle pilot
        $pilot3 = new Pilot();
        $pilot3->setCompany($company)
            ->setName('Rashid "Scorcher" Al-Mansoor')
            ->setIsNamed(true)
            ->setGunnery(3)
            ->setPiloting(2)
            ->setGunneryXp(200)
            ->setPilotingXp(680);
        $this->em->persist($pilot3);
        $pilots[] = $pilot3;

        $unit3 = new Unit();
        $unit3->setCompany($company)
            ->setPilot($pilot3)
            ->setDropship($dropship)
            ->setName('Cataphract CTF-2X')
            ->setChassis('Cataphract')
            ->setTonnage(55)
            ->setBv(520)
            ->setUnitType(UnitType::Vehicle)
            ->setDamageState(DamageState::None);
        $this->em->persist($unit3);
        $units[] = $unit3;

        // Named pilot 4 - Battle Armor pilot (max named pilots reached)
        $pilot4 = new Pilot();
        $pilot4->setCompany($company)
            ->setName('Katarina "Ghost" Novak')
            ->setIsNamed(true)
            ->setGunnery(1)
            ->setPiloting(3)
            ->setGunneryXp(1100)
            ->setPilotingXp(150);
        $this->em->persist($pilot4);
        $pilots[] = $pilot4;

        $unit4 = new Unit();
        $unit4->setCompany($company)
            ->setPilot($pilot4)
            ->setName('Ottotto BA Launch System')
            ->setChassis('Ottotto')
            ->setTonnage(40)
            ->setBv(200)
            ->setUnitType(UnitType::BattleArmor)
            ->setDamageState(DamageState::None);
        $this->em->persist($unit4);
        $units[] = $unit4;

        // Unnamed pilots
        foreach (['Jensen', 'Okonkwo', 'Bjornsson'] as $i => $name) {
            $pilot = new Pilot();
            $pilot->setCompany($company)
                ->setName("Pilot {$name}")
                ->setIsNamed(false)
                ->setGunnery(4)
                ->setPiloting(5)
                ->setGunneryXp(0)
                ->setPilotingXp(0);
            $this->em->persist($pilot);
            $pilots[] = $pilot;

            $unitTypes = [UnitType::Mech, UnitType::Vehicle, UnitType::Mech];
            $chassisNames = ['Warhammer WHM-A', 'Stalker STK-5M', 'Rhino RHN-0'];
            $bvs = [900, 680, 780];

            $unit = new Unit();
            $unit->setCompany($company)
                ->setPilot($pilot)
                ->setDropship($dropship)
                ->setName($chassisNames[$i])
                ->setChassis($chassisNames[$i])
                ->setTonnage([80, 55, 75][$i])
                ->setBv($bvs[$i])
                ->setUnitType($unitTypes[$i])
                ->setDamageState(DamageState::None);
            $this->em->persist($unit);
            $units[] = $unit;
        }

        // A damaged unit
        $damagedPilot = new Pilot();
        $damagedPilot->setCompany($company)
            ->setName('Rook')
            ->setIsNamed(false)
            ->setGunnery(5)
            ->setPiloting(4)
            ->setGunneryXp(50)
            ->setPilotingXp(30);
        $this->em->persist($damagedPilot);
        $pilots[] = $damagedPilot;

        $damagedUnit = new Unit();
        $damagedUnit->setCompany($company)
            ->setPilot($damagedPilot)
            ->setName('Thunderbird TDR-7D')
            ->setChassis('Thunderbird')
            ->setTonnage(90)
            ->setBv(850)
            ->setUnitType(UnitType::Mech)
            ->setDamageState(DamageState::Crippled);
        $this->em->persist($damagedUnit);
        $units[] = $damagedUnit;

        return [$pilots, $units];
    }

    /**
     * @return Contract[]
     */
    private function createContracts(MercenaryCompany $mainCompany, MercenaryCompany $opposingCompany, array $pilots): array
    {
        $contracts = [];

        // Contract 1: Active expedition with multiple tracks
        $contract1 = new Contract();
        $contract1->setCompany($mainCompany)
            ->setType(ContractType::Expedition)
            ->setEmployer('Canopian Commonwealth Command')
            ->setEmployerAffiliation('Canopian Commonwealth')
            ->setDescription('Secure and hold key infrastructure on planet Vermeer-4. Establish forward operating base.')
            ->setScale(2)
            ->setDurationMonths(12)
            ->setBasePayPercent(75)
            ->setCommandRights(CommandRights::Integrated)
            ->setSupportTerms('Battle/50%')
            ->setSalvageRights('3/50%')
            ->setTransportTerms('—')
            ->setNumberOfTracks(4)
            ->setTracksCompleted(2)
            ->setStatus(ContractStatus::Active)
            ->setName('Operation Vermeer Shield')
            ->setPlanet('Vermeer-4')
            ->setIntensity($this->intensityGenerator->generate($contract1->getDurationMonths(), $contract1->getNumberOfTracks()));
        $this->em->persist($contract1);
        $contracts[] = $contract1;

        // Contract 2: Completed raid
        $contract2 = new Contract();
        $contract2->setCompany($mainCompany)
            ->setType(ContractType::Raid)
            ->setEmployer('Free Traders Guild')
            ->setEmployerAffiliation('Free Traders Guild')
            ->setDescription('Raid enemy supply depot and destroy stored munitions.')
            ->setScale(1)
            ->setDurationMonths(3)
            ->setBasePayPercent(100)
            ->setCommandRights(CommandRights::House)
            ->setSupportTerms('None')
            ->setSalvageRights('Exchange')
            ->setTransportTerms('—')
            ->setNumberOfTracks(2)
            ->setTracksCompleted(2)
            ->setStatus(ContractStatus::Completed)
            ->setName('Operation Blackout')
            ->setPlanet('Kowloon')
            ->setIntensity($this->intensityGenerator->generate($contract2->getDurationMonths(), $contract2->getNumberOfTracks()));
        $this->em->persist($contract2);
        $contracts[] = $contract2;

        // Contract 3: Available garrison
        $contract3 = new Contract();
        $contract3->setCompany($mainCompany)
            ->setType(ContractType::Garrison)
            ->setEmployer('Lyran League')
            ->setEmployerAffiliation('Lyran Alliance')
            ->setDescription('Garrison and defend the city of New Glasgow against possible raids.')
            ->setScale(3)
            ->setDurationMonths(24)
            ->setBasePayPercent(50)
            ->setCommandRights(CommandRights::Liaison)
            ->setSupportTerms('Straight/100%')
            ->setSalvageRights('None')
            ->setTransportTerms('—')
            ->setNumberOfTracks(6)
            ->setTracksCompleted(0)
            ->setStatus(ContractStatus::Available)
            ->setName('Operation Iron Wall')
            ->setPlanet('New Glasgow')
            ->setIntensity($this->intensityGenerator->generate($contract3->getDurationMonths(), $contract3->getNumberOfTracks()));
        $this->em->persist($contract3);
        $contracts[] = $contract3;

        // Contract 4: Opposing contract (against Iron Jaguars)
        $contract4 = new Contract();
        $contract4->setCompany($mainCompany)
            ->setOpposingCompany($opposingCompany)
            ->setIsOpposing(true)
            ->setType(ContractType::Expedition)
            ->setEmployer('Canopian Commonwealth Command')
            ->setEmployerAffiliation('Canopian Commonwealth')
            ->setDescription('Contested zone extraction. Opposing force: Iron Jaguar Mercenaries.')
            ->setScale(2)
            ->setDurationMonths(6)
            ->setBasePayPercent(80)
            ->setCommandRights(CommandRights::Independent)
            ->setSupportTerms('Battle/25%')
            ->setSalvageRights('Exchange/50%')
            ->setTransportTerms('25%')
            ->setNumberOfTracks(3)
            ->setTracksCompleted(1)
            ->setStatus(ContractStatus::Active)
            ->setName('Operation Crossfire')
            ->setPlanet('Tharkad')
            ->setIntensity($this->intensityGenerator->generate($contract4->getDurationMonths(), $contract4->getNumberOfTracks()));
        $this->em->persist($contract4);
        $contracts[] = $contract4;

        // Contract 5: Invasion with opposing company
        $contract5 = new Contract();
        $contract5->setCompany($opposingCompany)
            ->setOpposingCompany($mainCompany)
            ->setIsOpposing(true)
            ->setType(ContractType::Invasion)
            ->setEmployer('Cloud Cobra Khanate')
            ->setEmployerAffiliation('Cloud Cobra')
            ->setDescription('Invasion force opposing Hinterlands MC defense.')
            ->setScale(3)
            ->setDurationMonths(18)
            ->setBasePayPercent(60)
            ->setCommandRights(CommandRights::House)
            ->setSupportTerms('Battle/50%')
            ->setSalvageRights('3/50%')
            ->setTransportTerms('50%')
            ->setNumberOfTracks(6)
            ->setTracksCompleted(2)
            ->setStatus(ContractStatus::Active)
            ->setName('Operation Steel Tempest')
            ->setPlanet('Luthien')
            ->setIntensity($this->intensityGenerator->generate($contract5->getDurationMonths(), $contract5->getNumberOfTracks()));
        $contract5->setLinkedContract($contract4);
        $this->em->persist($contract5);
        $contracts[] = $contract5;

        // Contract 6: Retainer - accepted
        $contract6 = new Contract();
        $contract6->setCompany($mainCompany)
            ->setType(ContractType::Retainer)
            ->setEmployer('Marian Hegemony')
            ->setEmployerAffiliation('Marian Hegemony')
            ->setDescription('Retainer contract for routine patrol and intelligence gathering.')
            ->setScale(1)
            ->setDurationMonths(6)
            ->setBasePayPercent(75)
            ->setCommandRights(CommandRights::Integrated)
            ->setSupportTerms('Straight/75%')
            ->setSalvageRights('3')
            ->setTransportTerms('—')
            ->setNumberOfTracks(2)
            ->setTracksCompleted(0)
            ->setStatus(ContractStatus::Accepted)
            ->setName('Operation Silent Watch')
            ->setPlanet('Marian')
            ->setIntensity($this->intensityGenerator->generate($contract6->getDurationMonths(), $contract6->getNumberOfTracks()));
        $this->em->persist($contract6);
        $contracts[] = $contract6;

        // Contract 7: Liaison
        $contract7 = new Contract();
        $contract7->setCompany($mainCompany)
            ->setType(ContractType::Liaison)
            ->setEmployer('Lyran League')
            ->setEmployerAffiliation('Lyran Alliance')
            ->setDescription('Diplomatic escort and liaison duties with AFFS forces.')
            ->setScale(1)
            ->setDurationMonths(4)
            ->setBasePayPercent(null)
            ->setCommandRights(CommandRights::Liaison)
            ->setSupportTerms('None')
            ->setSalvageRights('None')
            ->setTransportTerms('—')
            ->setNumberOfTracks(2)
            ->setTracksCompleted(0)
            ->setStatus(ContractStatus::Available)
            ->setName('Operation Silver Tongue')
            ->setPlanet('Davos')
            ->setIntensity($this->intensityGenerator->generate($contract7->getDurationMonths(), $contract7->getNumberOfTracks()));
        $this->em->persist($contract7);
        $contracts[] = $contract7;

        // Contract 8: Broken contract
        $contract8 = new Contract();
        $contract8->setCompany($mainCompany)
            ->setType(ContractType::Expedition)
            ->setEmployer('Former Client Corp')
            ->setEmployerAffiliation('Former Client Corp')
            ->setDescription('Failed contract - terms were not met due to unforeseen circumstances.')
            ->setScale(1)
            ->setDurationMonths(3)
            ->setBasePayPercent(50)
            ->setCommandRights(CommandRights::Independent)
            ->setSupportTerms('None')
            ->setSalvageRights('None')
            ->setTransportTerms('—')
            ->setNumberOfTracks(2)
            ->setTracksCompleted(0)
            ->setStatus(ContractStatus::Broken)
            ->setName('Operation Broken Promise')
            ->setPlanet('Bjost')
            ->setIntensity($this->intensityGenerator->generate($contract8->getDurationMonths(), $contract8->getNumberOfTracks()));
        $this->em->persist($contract8);
        $contracts[] = $contract8;

        return $contracts;
    }

    private function createTrackRecords(array $contracts): void
    {
        foreach ($contracts as $contract) {
            for ($trackNum = 1; $trackNum <= $contract->getNumberOfTracks(); $trackNum++) {
                $track = new TrackRecord();
                $track->setContract($contract)
                    ->setTrackNumber($trackNum)
                    ->setMissionType(match($contract->getType()) {
                        ContractType::Expedition => 'Urban Assault',
                        ContractType::Raid => 'Supply Depot Raid',
                        ContractType::Garrison => 'Fortification Defense',
                        ContractType::Invasion => 'Beachhead Assault',
                        ContractType::Retainer => 'Patrol Route',
                        ContractType::Liaison => 'Diplomatic Escort',
                    })
                    ->setTerrain(match($trackNum % 3) {
                        0 => 'Mountain',
                        1 => 'Urban',
                        2 => 'Open',
                    })
                    ->setCommandComplication($trackNum % 4 === 0 ? 'Ambush by enemy reinforcements' : null)
                    ->setCombatPayTier(match($trackNum % 4) {
                        0 => CombatPayTier::None,
                        1 => CombatPayTier::Half,
                        2 => CombatPayTier::Full,
                        3 => CombatPayTier::HalfAgain,
                    })
                    ->setStatus($trackNum <= $contract->getTracksCompleted() ? TrackStatus::Completed : TrackStatus::Pending)
                    ->setTakingOneForTeam($trackNum % 5 === 0);

                if ($track->getStatus() === TrackStatus::Completed) {
                    $track->setCompletedAt(new \DateTimeImmutable('-' . ($contract->getNumberOfTracks() - $trackNum) . ' months'));
                }

                $this->em->persist($track);
            }
        }
    }

    private function createContractLogEntries(array $contracts, MercenaryCompany $company): void
    {
        foreach ($contracts as $contract) {
            if ($contract->getStatus() === ContractStatus::Available || $contract->getStatus() === ContractStatus::Broken) {
                continue;
            }

            $tracks = $contract->getTrackRecords();

            // Track setup log entries
            foreach ($tracks as $track) {
                $logEntry = new ContractLogEntry();
                $logEntry->setContract($contract)
                    ->setTrack($track)
                    ->setMonth($track->getTrackNumber())
                    ->setEntryType(ContractLogEntryType::TrackSetup)
                    ->setDescription("Track {$track->getTrackNumber()} setup: {$track->getMissionType()} on {$track->getTerrain()} terrain");

                if ($track->getCommandComplication()) {
                    $logEntry->setData(['complication' => $track->getCommandComplication()]);
                    $logEntry->setRollResult(rand(3, 12));
                }

                $this->em->persist($logEntry);

                // Create a supporting SP entry for track setup
                $spEntry = new SupportPointEntry();
                $spEntry->setCompany($company)
                    ->setAmount(-50)
                    ->setDescription("Track {$track->getTrackNumber()} setup cost");
                $logEntry->setSupportPointEntry($spEntry);
            }

            // Base pay log entries for completed tracks
            foreach ($tracks as $track) {
                if ($track->getStatus() !== TrackStatus::Completed) {
                    continue;
                }

                $logEntry = new ContractLogEntry();
                $logEntry->setContract($contract)
                    ->setTrack($track)
                    ->setMonth($track->getTrackNumber())
                    ->setEntryType(ContractLogEntryType::BasePay)
                    ->setDescription("Month {$track->getTrackNumber()} base pay: " . $contract->calculateMonthlyBasePay() . " SP");

                $this->em->persist($logEntry);

                // Credit SP for completed track
                $spEntry = new SupportPointEntry();
                $spEntry->setCompany($company)
                    ->setAmount($contract->calculateMonthlyBasePay())
                    ->setDescription("Month {$track->getTrackNumber()} base pay credit");
                $logEntry->setSupportPointEntry($spEntry);

                // Combat pay log entry
                if ($track->getCombatPayTier()) {
                    $combatPayLog = new ContractLogEntry();
                    $combatPayLog->setContract($contract)
                        ->setTrack($track)
                        ->setMonth($track->getTrackNumber())
                        ->setEntryType(ContractLogEntryType::PostTrack)
                        ->setDescription("Month {$track->getTrackNumber()} combat pay ({$track->getCombatPayTier()->value}): " . $contract->calculateMonthlyCombatPay($track->getCombatPayTier()) . " SP");

                    $this->em->persist($combatPayLog);

                    $combatSpEntry = new SupportPointEntry();
                    $combatSpEntry->setCompany($company)
                        ->setAmount($contract->calculateMonthlyCombatPay($track->getCombatPayTier()))
                        ->setDescription("Month {$track->getTrackNumber()} combat pay credit");
                    $combatPayLog->setSupportPointEntry($combatSpEntry);
                }
            }

            // Downtime log entries
            $logEntry = new ContractLogEntry();
            $logEntry->setContract($contract)
                ->setMonth(0)
                ->setEntryType(ContractLogEntryType::Downtime)
                ->setDescription("Contract initialization downtime");
            $this->em->persist($logEntry);

            // Negotiation log entry
            $logEntry = new ContractLogEntry();
            $logEntry->setContract($contract)
                ->setMonth(0)
                ->setEntryType(ContractLogEntryType::Negotiation)
                ->setDescription("Contract negotiation with {$contract->getEmployer()}");
            $this->em->persist($logEntry);
        }
    }

    private function createSalvagedMechs(MercenaryCompany $company, ?Dropship $dropship, array $contracts): void
    {
        $salvagedMechs = [
            [
                'model' => 'Catapult CAT-PU1',
                'tonnage' => 80,
                'bvCost' => 300,
                'damageState' => DamageState::Destroyed,
                'techBase' => TechBase::IS,
                'salvageRightsPercent' => 50,
                'spTaken' => 75,
                'scrapyard' => false,
            ],
            [
                'model' => 'Thunderbird TDR-7D',
                'tonnage' => 90,
                'bvCost' => 850,
                'damageState' => DamageState::Crippled,
                'salvageRightsPercent' => 50,
                'spTaken' => 212,
                'scrapyard' => false,
            ],
            [
                'model' => 'Grasshopper GHR-5n',
                'tonnage' => 70,
                'bvCost' => null,
                'damageState' => DamageState::Destroyed,
                'techBase' => TechBase::Mixed,
                'salvageRightsPercent' => null,
                'spTaken' => 0,
                'scrapyard' => true,
            ],
            [
                'model' => 'Stalker STK-5M',
                'tonnage' => 85,
                'bvCost' => 1655,
                'damageState' => DamageState::ArmorOnly,
                'techBase' => TechBase::IS,
                'salvageRightsPercent' => 50,
                'spTaken' => null,
                'scrapyard' => false,
            ],
            [
                'model' => 'Warhammer WHM-A',
                'tonnage' => 80,
                'damageState' => DamageState::Structural,
                'techBase' => TechBase::Clan,
                'salvageRightsPercent' => 50,
                'spTaken' => null,
                'scrapyard' => false,
            ],
        ];

        foreach ($salvagedMechs as $data) {
            $mechan = new SalvagedMech();
            $mechan->setCompany($company)
                ->setModel($data['model'])
                ->setTonnage($data['tonnage'])
                ->setBvCost($data['bvCost'])
                ->setDamageState($data['damageState'])
                ->setTechBase($data['techBase'])
                ->setSalvageRightsPercent($data['salvageRightsPercent'])
                ->setSpTaken($data['spTaken'])
                ->setScrapyard($data['scrapyard'])
                ->setIsTrulyDestroyed($data['damageState'] === DamageState::Destroyed);

            if ($dropship) {
                $mechan->setDropship($dropship);
            }

            // Link some salvaged mech to a contract
            $contractIndex = array_search($data['model'], ['Catapult CAT-PU1', 'Thunderbird TDR-7D']);
            if ($contractIndex !== false && isset($contracts[$contractIndex])) {
                $mechan->setContract($contracts[$contractIndex]);
            }

            $this->em->persist($mechan);
        }
    }

    private function countTrackRecords(array $contracts): int
    {
        $count = 0;
        foreach ($contracts as $contract) {
            $count += $contract->getNumberOfTracks();
        }
        return $count;
    }

    private function countLogEntries(array $contracts): int
    {
        $count = 0;
        foreach ($contracts as $contract) {
            if ($contract->getStatus() === ContractStatus::Available || $contract->getStatus() === ContractStatus::Broken) {
                continue;
            }
            $tracks = $contract->getTrackRecords();
            // Track setup entries + base pay entries + combat pay entries + downtime + negotiation
            $count += count($tracks) * 3 + 2;
        }
        return $count;
    }

    private function countSalvagedMechs(): int
    {
        return 5;
    }

    private function countSupportPointEntries(MercenaryCompany $company): int
    {
        // This is an approximation since we can't query after flush without persisting
        // Real count: setup entries (3 per active contract) + base pay (2 per completed track) + combat pay
        return 30;
    }
}
