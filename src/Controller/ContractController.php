<?php
namespace App\Controller;

use App\Entity\Contract;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use App\Enum\CommandRights;
use App\Form\ContractEditFormType;
use App\Service\ContractGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contracts')]
class ContractController extends AbstractController {
    public function __construct(
        private readonly ContractGeneratorService $generator,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'app_contracts')]
    public function index(): Response {
        $company = $this->getUser()->getCompany();
        $myContracts = $this->em->getRepository(Contract::class)->findBy(
            ['company' => $company],
            ['createdAt' => 'DESC']
        );
        $opposingPool = $this->em->getRepository(Contract::class)->findBy([
            'company'    => null,
            'isOpposing' => true,
            'status'     => ContractStatus::Available,
        ]);
        return $this->render('contract/index.html.twig', [
            'myContracts'  => $myContracts,
            'opposingPool' => $opposingPool,
        ]);
    }

    #[Route('/generate', name: 'app_contracts_generate', methods: ['GET'])]
    public function generate(): Response {
        $company = $this->getUser()->getCompany();
        $scale   = $company->getReputation();
        $data    = $this->generator->generate($scale);
        return $this->render('contract/generate.html.twig', ['data' => $data, 'scale' => $scale]);
    }

    #[Route('/accept', name: 'app_contracts_accept', methods: ['POST'])]
    public function accept(Request $request): Response {
        $company = $this->getUser()->getCompany();
        $p       = $request->request->all();

        $primary = new Contract();
        $primary->setCompany($company);
        $primary->setIsOpposing(false);
        $primary->setStatus(ContractStatus::Active);
        $primary->setType(ContractType::from($p['type']));
        $primary->setName(ContractType::from($p['type'])->value);
        $primary->setEmployer($p['employer']);
        $primary->setEmployerAffiliation($p['affiliation']);
        $primary->setScale((int) $p['scale']);
        $primary->setDurationMonths((int) $p['duration']);
        $primary->setBasePayPercent($p['basePayPercent'] !== '' ? (int) $p['basePayPercent'] : null);
        $primary->setCommandRights(CommandRights::from($p['commandRights']));
        $primary->setSupportTerms($p['supportTerms']);
        $primary->setSalvageRights($p['salvageRights']);
        $primary->setTransportTerms($p['transportTerms']);
        $primary->setNumberOfTracks((int) $p['numberOfTracks']);
        $primary->setAcceptedAt(new \DateTimeImmutable());
        $this->em->persist($primary);

        $oppData = $this->generator->generateOpposing($primary->getType(), $primary->getScale());
        $opposing = new Contract();
        $opposing->setCompany(null);
        $opposing->setIsOpposing(true);
        $opposing->setStatus(ContractStatus::Available);
        $opposing->setType($oppData['type']);
        $opposing->setName($oppData['type']->value);
        $opposing->setEmployer($oppData['employer']);
        $opposing->setEmployerAffiliation($oppData['affiliation']);
        $opposing->setScale($primary->getScale());
        $opposing->setDurationMonths($primary->getDurationMonths());
        $opposing->setBasePayPercent($oppData['basePayPercent']);
        $opposing->setCommandRights($oppData['commandRights']);
        $opposing->setSupportTerms($oppData['supportTerms']);
        $opposing->setSalvageRights($oppData['salvageRights']);
        $opposing->setTransportTerms($oppData['transportTerms']);
        $opposing->setNumberOfTracks($primary->getNumberOfTracks());
        $opposing->setLinkedContract($primary);
        $primary->setLinkedContract($opposing);
        $this->em->persist($opposing);
        $this->em->flush();

        $this->addFlash('success', 'Contract accepted. Opposing contract posted to the pool.');
        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/discard', name: 'app_contracts_discard', methods: ['POST'])]
    public function discard(): Response {
        $this->addFlash('info', 'Contract discarded. You must wait one month before generating again.');
        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/claim/{id}', name: 'app_contracts_claim', methods: ['POST'])]
    public function claim(Contract $contract): Response {
        if (!$contract->isOpposing() || $contract->getCompany() !== null) {
            throw $this->createAccessDeniedException();
        }
        $company = $this->getUser()->getCompany();
        $contract->setCompany($company);
        $contract->setStatus(ContractStatus::Active);
        $contract->setAcceptedAt(new \DateTimeImmutable());
        if ($contract->getLinkedContract() !== null) {
            $contract->getLinkedContract()->setOpposingCompany($company);
        }
        $this->em->flush();
        $this->addFlash('success', 'Opposing contract claimed.');
        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/{id}/delete', name: 'app_contracts_delete', methods: ['POST'])]
    public function delete(Contract $contract): Response {
        if ($contract->getCompany() !== $this->getUser()->getCompany()) {
            throw $this->createAccessDeniedException();
        }
        $linked = $contract->getLinkedContract();
        if ($linked !== null) {
            $linked->setLinkedContract(null);
            $contract->setLinkedContract(null);
            $this->em->flush();
            if ($linked->getCompany() === null) {
                $this->em->remove($linked);
                $this->em->flush();
            }
        }
        $this->em->remove($contract);
        $this->em->flush();
        $this->addFlash('success', 'Contract deleted.');
        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/{id}/edit', name: 'app_contracts_edit', methods: ['GET', 'POST'])]
    public function edit(Contract $contract, Request $request): Response {
        $form = $this->createForm(ContractEditFormType::class, $contract);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Contract updated.');
            return $this->redirectToRoute('app_contracts_show', ['id' => $contract->getId()]);
        }
        return $this->render('contract/edit.html.twig', [
            'contract' => $contract,
            'form'     => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contracts_show')]
    public function show(Contract $contract): Response {
        return $this->render('contract/show.html.twig', ['contract' => $contract]);
    }
}
