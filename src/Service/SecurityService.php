<?php

namespace App\Service;

use App\Entity\MercenaryCompany;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public function registerUser(string $username, string $email, string $password, string $companyName, string $faction): void
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $company = new MercenaryCompany();
        $company->setName($companyName);
        $company->setFaction($faction);
        $company->setUser($user);
        $user->setCompany($company);

        $this->em->persist($user);
        $this->em->persist($company);
        $this->em->flush();
    }
}
