<?php

namespace App\Repository;

use App\Entity\Dropship;
use App\Entity\SalvagedMech;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dropship>
 */
class DropshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dropship::class);
    }

    public function countMechsOnDropship(int $dropshipId): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(s.id)')
            ->join(SalvagedMech::class, 's', 'WITH', 's.dropship = d')
            ->where('d.id = :id')
            ->setParameter('id', $dropshipId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
