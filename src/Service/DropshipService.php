<?php

namespace App\Service;

use App\Entity\Dropship;
use App\Entity\MercenaryCompany;
use App\Entity\SalvagedMech;
use App\Entity\Unit;
use Doctrine\ORM\EntityManagerInterface;

class DropshipService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function getDropship(int $id): ?Dropship
    {
        return $this->em->getRepository(Dropship::class)->find($id);
    }

    public function getDropshipByCompany(MercenaryCompany $company): ?Dropship
    {
        return $this->em->getRepository(Dropship::class)->findOneBy(['company' => $company]);
    }

    public function createDropship(MercenaryCompany $company, int $maxCapacity, ?string $name = null, int $mekbayCapacity = 0): Dropship
    {
        $existing = $this->getDropshipByCompany($company);
        if ($existing !== null) {
            throw new \LogicException('This company already has a dropship. Each company may only have one dropship.');
        }

        if ($maxCapacity < 40) {
            throw new \InvalidArgumentException('Dropship maxCapacity must be at least 40 tons (minimum 2 mechs at 20 tons).');
        }

        $dropship = new Dropship();
        $dropship->setCompany($company);
        $dropship->setMaxCapacity($maxCapacity);
        $dropship->setName($name);
        $dropship->setMekbayCapacity($mekbayCapacity);

        $this->em->persist($dropship);
        $this->em->flush();

        return $dropship;
    }

    public function updateDropship(Dropship $dropship, int $newMaxCapacity, int $newMekbayCapacity = 0): void
    {
        if ($newMaxCapacity < 40) {
            throw new \InvalidArgumentException('Dropship maxCapacity must be at least 40 tons (minimum 2 mechs at 20 tons).');
        }

        $dropship->setMaxCapacity($newMaxCapacity);
        $dropship->setMekbayCapacity($newMekbayCapacity);
        $this->em->flush();
    }

    public function deleteDropship(Dropship $dropship): void
    {
        foreach ($this->em->getRepository(SalvagedMech::class)->findBy(['dropship' => $dropship]) as $mechan) {
            $mechan->setDropship(null);
        }
        foreach ($this->em->getRepository(Unit::class)->findBy(['dropship' => $dropship]) as $unit) {
            $unit->setDropship(null);
        }
        $this->em->remove($dropship);
        $this->em->flush();
    }

    public function getTonnageOnDropship(Dropship $dropship): int
    {
        $unitTonnage = $this->em->getRepository(Unit::class)
            ->countTonnageOnDropship($dropship->getId());
        $salvagedTonnage = (int) $this->em->createQueryBuilder()
            ->select('COALESCE(SUM(s.tonnage), 0)')
            ->from(SalvagedMech::class, 's')
            ->where('s.dropship = :id')
            ->setParameter('id', $dropship->getId())
            ->getQuery()
            ->getSingleScalarResult();
        return $unitTonnage + $salvagedTonnage;
    }

    public function getUsedMekbays(Dropship $dropship): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(Unit::class, 'u')
            ->where('u.dropship = :id')
            ->setParameter('id', $dropship->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function assignMechToDropship(SalvagedMech $mechan, Dropship $dropship): void
    {
        $currentTonnage = $this->getTonnageOnDropship($dropship);
        $mechanTonnage = $mechan->getTonnage();
        if ($currentTonnage + $mechanTonnage > $dropship->getMaxCapacity()) {
            throw new \LogicException(
                "Dropship capacity exceeded. Current tonnage: {$currentTonnage}, mech tonnage: {$mechanTonnage}, max: {$dropship->getMaxCapacity()}."
            );
        }
        $mechan->setDropship($dropship);
        $this->em->flush();
    }

    public function assignUnitToDropship(Unit $unit, Dropship $dropship): void
    {
        $currentTonnage = $this->getTonnageOnDropship($dropship);
        if ($currentTonnage + $unit->getTonnage() > $dropship->getMaxCapacity()) {
            throw new \LogicException(
                "Dropship capacity exceeded. Current tonnage: {$currentTonnage}, unit tonnage: {$unit->getTonnage()}, max: {$dropship->getMaxCapacity()}."
            );
        }

        $usedMekbays = $this->getUsedMekbays($dropship);
        if ($usedMekbays >= $dropship->getMekbayCapacity()) {
            throw new \LogicException(
                "No mekbays available. Current mekbays: {$usedMekbays}, max: {$dropship->getMekbayCapacity()}."
            );
        }

        $unit->setDropship($dropship);
        $this->em->flush();
    }

    /** @return \Doctrine\Common\Collections\Collection<SalvagedMech> */
    public function getUnassignedMechs(MercenaryCompany $company): \Doctrine\Common\Collections\Collection
    {
        $mechs = $this->em->createQueryBuilder()
            ->select('s')
            ->from(SalvagedMech::class, 's')
            ->where('s.dropship IS NULL')
            ->andWhere('s.spTaken IS NULL')
            ->andWhere('s.isTrulyDestroyed = false')
            ->andWhere('s.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getResult();
        return new \Doctrine\Common\Collections\ArrayCollection($mechs);
    }
}
