<?php
namespace App\Controller;

use App\Entity\Contract;
use App\Entity\MercenaryCompany;
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
        $companies = $entityManager->getRepository(MercenaryCompany::class)->findAll();

        $companiesAndSupportPoints = [];

        foreach ($companies as $c) {
            $companiesAndSupportPoints[] = [
                'company' => $c,
                'supportPoints' => $c->getSupportPointsBalance()
            ];
        }

        usort($companiesAndSupportPoints, fn($a, $b) => $b['supportPoints'] <=> $a['supportPoints']);

        return $this->render('dashboard/index.html.twig', [
            'company'         => $company,
            'companies'         => $companies,
            'activeContracts' => $activeContracts,
            'companiesAndSupportPoints' => $companiesAndSupportPoints,
            'totalBv'         => $company->getTotalBv(),
            'spBalance'       => $company->getSupportPointsBalance(),
            'namedPilots'     => $company->getPilots()->filter(fn($p) => $p->isNamed()),
        ]);
    }
}
