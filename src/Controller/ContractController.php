<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\MercenaryCompany;
use App\Entity\SupportPointEntry;
use App\Entity\TrackRecord;
use App\Enum\CommandRights;
use App\Enum\CombatPayTier;
use App\Enum\ContractLogEntryType;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use App\Generator\MegaMekGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContractController extends AbstractController
{
    #[Route('/contract', name: 'app_contract')]
    public function index(EntityManagerInterface $em): Response
    {
        $contracts = $em->getRepository(Contract::class)->findAll();
        return $this->render('contract/index.html.twig', [
            'contracts' => $contracts,
        ]);
    }

    #[Route('/contract/new', name: 'app_contract_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $contract = (new Contract())
                ->setType($data['type'])
                ->setEmployer($data['employer'])
                ->setEmployerAffiliation($data['employerAffiliation'])
                ->setScale($data['scale'])
                ->setDurationMonths($data['durationMonths'])
                ->setBasePayPercent($data['basePayPercent'] ?? null)
                ->setCommandRights($data['commandRights'])
                ->setSupportTerms($data['supportTerms'])
                ->setSalvageRights($data['salvageRights'])
                ->setTransportTerms($data['transportTerms'])
                ->setNumberOfTracks($data['numberOfTracks']);

            if ($data['company'] ?? null) {
                $contract->setCompany($data['company']);
            }

            if ($data['opposingCompany'] ?? null) {
                $contract->setOpposingCompany($data['opposingCompany']);
            }

            if ($data['linkedContract'] ?? null) {
                $contract->setLinkedContract($data['linkedContract']);
            }

            $contract->setIsOpposing($data['isOpposing'] ?? false);

            $em->persist($contract);
            $em->flush();

            $this->addFlash('success', 'Contract created.');
            return $this->redirectToRoute('app_contract');
        }

        return $this->render('contract/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/contract/{id}/accept', name: 'app_contract_accept')]
    public function accept(Contract $contract, EntityManagerInterface $em): Response
    {
        if ($contract->getStatus() !== ContractStatus::Available) {
            $this->addFlash('error', 'Contract is not available.');
            return $this->redirectToRoute('app_contract');
        }

        $contract->setStatus(ContractStatus::Accepted);
        $contract->setAcceptedAt(new \DateTimeImmutable());

        $em->flush();
        $this->addFlash('success', 'Contract accepted.');

        return $this->redirectToRoute('app_contract');
    }

    #[Route('/contract/{id}/track-setup', name: 'app_contract_track_setup')]
    public function trackSetup(Contract $contract, Request $request, EntityManagerInterface $em): Response
    {
        $generator = new MegaMekGenerator();
        $result = $generator->rollTrackSetup($contract->getType(), $contract->getCommandRights());

        $track = (new TrackRecord())
            ->setContract($contract)
            ->setTrackNumber($contract->getTracksCompleted() + 1)
            ->setMissionType($result['missionType'])
            ->setTerrain($result['terrain'])
            ->setCommandComplication($result['complication'])
            ->setStatus(TrackRecord::Status::Pending)
            ->setTakingOneForTeam(false);

        $em->persist($track);

        $log = (new ContractLogEntry())
            ->setContract($contract)
            ->setTrack($track)
            ->setMonth($request->request->getInt('month') ?? 1)
            ->setEntryType(ContractLogEntryType::TrackSetup)
            ->setDescription("Track {$track->getTrackNumber()}: {$result['missionType']} on {$result['terrain']} (MegaMek: {$result['terrainSetting']})")
            ->setData($result);

        $em->persist($log);
        $em->flush();
        $this->addFlash('success', 'Track setup rolled and recorded.');

        return $this->redirectToRoute('app_contract');
    }

    #[Route('/contract/{id}/post-track', name: 'app_contract_post_track')]
    public function postTrack(Contract $contract, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->redirectToRoute('app_contract');
        }

        $data = $form->getData();
        $tier = $data['combatPayTier'];
        $combatPay = $contract->calculateMonthlyCombatPay($tier);
        $month = $request->request->getInt('month') ?? 1;

        // Find the first pending track
        $pendingTrack = $contract->getTrackRecords()->filter(
            fn(TrackRecord $t) => $t->getStatus() === TrackRecord::Status::Pending
        )->first() ?: null;

        $toftt = $pendingTrack?->isTakingOneForTeam() ?? false;
        if ($toftt) {
            $combatPay = (int) floor($combatPay / 2);
        }

        $sp = null;
        if ($combatPay > 0) {
            if ($toftt) {
                $combatPay = floor($combatPay/4);
            }
            $sp = (new SupportPointEntry())
                ->setCompany($contract->getCompany())
                ->setAmount($combatPay)
                ->setDescription("Combat pay ({$tier->value})");
            $em->persist($sp);
        }

        if ($pendingTrack) {
            $pendingTrack->setStatus(TrackRecord::Status::Completed);
            $pendingTrack->setCompletedAt(new \DateTimeImmutable());
            $pendingTrack->setCombatPayTier($tier);
        }

        $contract->setTracksCompleted($contract->getTracksCompleted() + 1);
        if ($contract->getTracksCompleted() >= $contract->getNumberOfTracks()) {
            $contract->setStatus(ContractStatus::Completed);
        }

        $salvageNote = $data['salvageClaimed'] ? 'Salvage claimed.' : 'No salvage.';
        $tofttNote = $toftt ? ' (TOFTT — half pay)' : '';

        $log = (new ContractLogEntry())
            ->setContract($contract)
            ->setMonth($month)
            ->setEntryType(ContractLogEntryType::PostTrack)
            ->setDescription("Combat pay: " . ($combatPay > 0 ? "+$combatPay SP" : "none") . " ({$tier->value}){$tofttNote}. $salvageNote");

        if ($sp) {
            $log->setSupportPointEntry($sp);
        }

        $em->persist($log);
        $em->flush();
        $this->addFlash('success', 'Post-track results recorded.');

        return $this->redirectToRoute('app_contract');
    }

    #[Route('/contract/{id}/downtime', name: 'app_contract_downtime')]
    public function downtime(Contract $contract, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->redirectToRoute('app_contract');
        }

        $data = $form->getData();
        $month = $request->request->getInt('month') ?? 1;
        $amount = $data['amount'];
        $note = $data['note'] ?? '';

        $sp = null;
        if ($amount !== 0) {
            $sp = (new SupportPointEntry())
                ->setCompany($contract->getCompany())
                ->setAmount($amount)
                ->setDescription("Downtime — " . ($note ?: 'no note'));
            $em->persist($sp);
        }

        $amountNote = $amount !== 0 ? " (" . ($amount >= 0 ? "+$amount" : "$amount") . " SP)" : '';

        $log = (new ContractLogEntry())
            ->setContract($contract)
            ->setMonth($month)
            ->setEntryType(ContractLogEntryType::Downtime)
            ->setDescription(($note ?: '(no note)') . $amountNote);

        if ($sp) {
            $log->setSupportPointEntry($sp);
        }

        $em->persist($log);
        $em->flush();
        $this->addFlash('success', 'Downtime note added.');

        return $this->redirectToRoute('app_contract');
    }

    #[Route('/contract/{id}/salvage', name: 'app_contract_salvage')]
    public function salvage(Contract $contract, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->redirectToRoute('app_contract');
        }

        $data = $form->getData();
        $month = $request->request->getInt('month') ?? 1;
        $amount = $data['amount'];
        $note = $data['note'] ?? '';

        $sp = null;
        if ($amount !== 0) {
            $sp = (new SupportPointEntry())
                ->setCompany($contract->getCompany())
                ->setAmount($amount)
                ->setDescription("Salvage — " . ($note ?: 'no note'));
            $em->persist($sp);
        }

        $amountNote = $amount !== 0 ? " (" . ($amount >= 0 ? "+$amount" : "$amount") . " SP)" : '';

        $log = (new ContractLogEntry())
            ->setContract($contract)
            ->setMonth($month)
            ->setEntryType(ContractLogEntryType::Salvage)
            ->setDescription(($note ?: '(no note)') . $amountNote);

        if ($sp) {
            $log->setSupportPointEntry($sp);
        }

        $em->persist($log);
        $em->flush();
        $this->addFlash('success', 'Salvage recorded.');

        return $this->redirectToRoute('app_contract');
    }

    private function createForm(): \Symfony\Component\Form\FormInterface
    {
        // Form creation would go here
        return null;
    }
}
