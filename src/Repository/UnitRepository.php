<?php
namespace App\Repository;
use App\Entity\Unit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class UnitRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Unit::class);
    }

    public function countTonnageOnDropship(int $dropshipId): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COALESCE(SUM(u.tonnage), 0)')
            ->where('u.dropship = :id')
            ->setParameter('id', $dropshipId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
