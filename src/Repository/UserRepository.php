<?php
namespace App\Repository;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class UserRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, User::class);
    }

    public function findAllUsersWithCompany(): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.company', 'c')
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
