<?php

namespace App\Command;

use App\Entity\SalvagedMech;
use App\Service\SalvageCalculationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-repair-costs',
    description: 'Recalculate repairCost for all SalvagedMech entities using SalvageCalculationService::calculateRepairCost().'
)]
class FixRepairCostsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SalvageCalculationService $salvageCalc,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->text('Recalculating repair costs for all SalvagedMech entities...');

        $mechan = $this->em->getRepository(SalvagedMech::class);
        $allMechs = $mechan->findAll();

        if (empty($allMechs)) {
            $io->warning('No SalvagedMech entities found.');
            return Command::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;

        $io->text(sprintf('Found %d SalvagedMech entity/entities.', count($allMechs)));

        foreach ($allMechs as $salvagedMech) {
            $newRepairCost = $this->salvageCalc->calculateRepairCost(
                $salvagedMech->getTonnage(),
                $salvagedMech->getDamageState(),
                $salvagedMech->getTechBase()
            );

            $currentRepairCost = $salvagedMech->getRepairCost();

            // Only update if the value differs (null != null is false, skip)
            if ($currentRepairCost === $newRepairCost) {
                $skipped++;
                continue;
            }

            $salvagedMech->setRepairCost($newRepairCost);
            $updated++;
        }

        $this->em->flush();

        $io->success(sprintf(
            'Repair cost recalculation complete. Updated: %d, Skipped (already correct): %d.',
            $updated,
            $skipped
        ));

        return Command::SUCCESS;
    }
}
