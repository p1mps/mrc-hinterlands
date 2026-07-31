<?php

namespace App\Service;

use App\DataTables\XpThresholdsTable;
use App\Entity\MercenaryCompany;
use App\Entity\Pilot;
use Doctrine\ORM\EntityManagerInterface;

class PilotService
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    /** @return \Doctrine\Common\Collections\Collection<Pilot> */
    public function getPilots(MercenaryCompany $company): \Doctrine\Common\Collections\Collection
    {
        return $company->getPilots();
    }

    /** @return array<int, string> Map of pilot ID to XP improvement alert message */
    public function getXpThresholdAlerts(\Doctrine\Common\Collections\Collection $pilots): array
    {
        $alerts = [];
        foreach ($pilots as $pilot) {
            if ($pilot->isNamed()) {
                $alert = XpThresholdsTable::checkImprovement(
                    $pilot->getGunnery(),
                    $pilot->getPiloting(),
                    $pilot->getGunneryXp(),
                    $pilot->getPilotingXp()
                );
                if ($alert) {
                    $alerts[$pilot->getId()] = $alert;
                }
            }
        }
        return $alerts;
    }

    public function createPilot(MercenaryCompany $company, Pilot $pilot): ?string
    {
        if ($pilot->isNamed() && $company->getNamedPilotsCount() >= 4) {
            return 'Maximum 4 named pilots allowed.';
        }

        $pilot->setCompany($company);
        $this->em->persist($pilot);
        $this->em->flush();

        return null;
    }

    public function updatePilot(Pilot $pilot): void
    {
        $this->em->flush();
    }

    public function deletePilot(Pilot $pilot): void
    {
        $this->em->remove($pilot);
        $this->em->flush();
    }
}
