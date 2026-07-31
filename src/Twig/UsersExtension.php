<?php

namespace App\Twig;

use App\Repository\UserRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UsersExtension extends AbstractExtension
{
    public function __construct(private readonly UserRepository $userRepository) {}

    public function getGlobals(): array
    {
        return [
            'users' => $this->userRepository->findAllUsersWithCompany(),
        ];
    }
}
