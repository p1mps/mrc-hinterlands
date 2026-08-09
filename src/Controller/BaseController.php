<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $em) {}

    protected function getUsers(): array
    {
        return $this->em->getRepository(User::class)->findAllUsersWithCompany();
    }

    protected function render(string $template, array $parameters = [], ?Response $response = null): Response
    {
        $user = $this->getUser();
        if ($user && in_array('ROLE_ALLOWED_TO_SWITCH', $user->getRoles(), true)) {
            $parameters['users'] = $this->getUsers();
        }
        return parent::render($template, $parameters, $response);
    }
}
