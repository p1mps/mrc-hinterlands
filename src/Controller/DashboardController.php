<?php
namespace App\Controller;

use App\Enum\ContractStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController {
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response {
        $company = $this->getUser()->getCompany();
        $activeContracts = $company->getContracts()->filter(
            fn($c) => $c->getStatus() === ContractStatus::Active && !$c->isOpposing()
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
