<?php
namespace App\Repository;
use App\Entity\Contract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class ContractRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Contract::class);
    }

    public function findAllOrderedByConnections(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.linkedContract', 'lc')
            ->addSelect('lc')
            ->orderBy('c.createdAt', 'DESC')
            ->addOrderBy('c.isOpposing', 'ASC')
            ->addOrderBy('CASE WHEN lc.id IS NULL THEN c.id ELSE lc.id END', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
