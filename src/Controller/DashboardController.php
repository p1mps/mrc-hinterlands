<?php

namespace App\Controller;

use App\Service\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(DashboardService $dashboardService): Response
    {
        $company = $this->getUser()->getCompany();
        $companies = $dashboardService->getAllCompanies();

        return $this->render('dashboard/index.html.twig', [
            'company'                 => $company,
            'companies'               => $companies,
            'activeContracts'         => $dashboardService->getActiveContracts(),
            'companiesAndSupportPoints' => $dashboardService->getCompaniesWithSupportPoints($companies),
            'totalBv'                 => $company->getTotalBv(),
            'spBalance'               => $company->getSupportPointsBalance(),
            'namedPilots'             => $company->getPilots()->filter(fn($p) => $p->isNamed()),
        ]);
    }
}
