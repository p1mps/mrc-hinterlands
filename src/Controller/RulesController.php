<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RulesController extends BaseController {
    #[Route('/rules', name: 'app_rules')]
    public function index(): Response {
        $rulesPath = $this->getParameter('kernel.project_dir') . '/templates/rules/rules.md';
        $content = file_get_contents($rulesPath);

        return $this->render('rules/index.html.twig', [
            'rulesContent' => $content,
        ]);
    }
}
