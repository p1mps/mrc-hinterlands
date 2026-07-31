<?php

namespace App\Service;

use App\Entity\Dropship;
use App\Entity\MercenaryCompany;
use App\Entity\SalvagedMech;
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

    public function createDropship(MercenaryCompany $company, int $maxCapacity, ?string $name = null): Dropship
    {
        $existing = $this->getDropshipByCompany($company);
        if ($existing !== null) {
            throw new \LogicException('This company already has a dropship. Each company may only have one dropship.');
        }

        if ($maxCapacity <= 0) {
            throw new \InvalidArgumentException('Dropship maxCapacity must be a positive integer.');
        }

        $dropship = new Dropship();
        $dropship->setCompany($company);
        $dropship->setMaxCapacity($maxCapacity);
        $dropship->setName($name);

        $this->em->persist($dropship);
        $this->em->flush();

        return $dropship;
    }

    public function updateDropship(Dropship $dropship, int $newMaxCapacity): void
    {
        if ($newMaxCapacity <= 0) {
            throw new \InvalidArgumentException('Dropship maxCapacity must be a positive integer.');
        }

        $dropship->setMaxCapacity($newMaxCapacity);
        $this->em->flush();
    }

    public function deleteDropship(Dropship $dropship): void
    {
        $repo = $this->em->getRepository(SalvagedMech::class);
        foreach ($repo->findBy(['dropship' => $dropship]) as $mechan) {
            $mechan->setDropship(null);
        }
        $this->em->remove($dropship);
        $this->em->flush();
    }

    public function assignMechToDropship(SalvagedMech $mechan, Dropship $dropship): void
    {
        $mechanCount = $this->em->getRepository(Dropship::class)
            ->countMechsOnDropship($dropship->getId());

        if ($mechanCount >= $dropship->getMaxCapacity()) {
            throw new \LogicException(
                "Dropship is at full capacity ({$mechanCount}/{$dropship->getMaxCapacity()} mechs). Cannot assign additional mechs."
            );
        }

        $mechan->setDropship($dropship);
        $this->em->flush();
    }

    public function countMechsOnDropship(Dropship $dropship): int
    {
        $count = $this->em->getRepository(Dropship::class)->countMechsOnDropship($dropship->getId());
        return $count ?? 0;
    }
}
