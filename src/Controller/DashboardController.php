<?php
namespace App\Controller;

use App\Entity\Contract;
use App\Enum\ContractStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController {
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response {
        $company = $this->getUser()->getCompany();
        $activeContracts = $entityManager->getRepository(Contract::class)->findBy(
            ['status' => ContractStatus::Active]
        );
        return $this->render('dashboard/index.html.twig', [
            'company'         => $company,
            'activeContracts' => $activeContracts,
            'totalBv'         => $company->getTotalBv(),
            'spBalance'       => $company->getSupportPointsBalance(),
            'namedPilots'     => $company->getPilots()->filter(fn($p) => $p->isNamed()),
        ]);
    }
}
