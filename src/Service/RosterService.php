<?php

namespace App\Service;

use App\Entity\MercenaryCompany;
use App\Entity\Pilot;
use App\Entity\Unit;
use Doctrine\ORM\EntityManagerInterface;

class RosterService
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    /** @return \Doctrine\Common\Collections\Collection<Unit> */
    public function getUnits(MercenaryCompany $company): \Doctrine\Common\Collections\Collection
    {
        return $company->getUnits();
    }

    /** @return \Doctrine\Common\Collections\Collection<Pilot> */
    public function getPilots(MercenaryCompany $company): \Doctrine\Common\Collections\Collection
    {
        return $company->getPilots();
    }

    public function createUnit(MercenaryCompany $company, Unit $unit): void
    {
        $unit->setCompany($company);
        $this->em->persist($unit);
        $this->em->flush();
    }

    public function updateUnit(Unit $unit): void
    {
        $this->em->flush();
    }

    /**
     * @return string|null Error message if assignment failed, null on success
     */
    public function assignPilotToUnit(Unit $unit, ?int $pilotId, MercenaryCompany $company): ?string
    {
        if ($pilotId === null || $pilotId === 0) {
            $unit->setPilot(null);
            $this->em->flush();
            return null;
        }

        $pilot = $this->em->getRepository(Pilot::class)->find($pilotId);

        if (!$pilot || $pilot->getCompany() !== $company) {
            return 'Pilot not found or does not belong to this company.';
        }

        $unit->setPilot($pilot);
        $this->em->flush();

        return null;
    }

    public function deleteUnit(Unit $unit): void
    {
        $this->em->remove($unit);
        $this->em->flush();
    }
}
