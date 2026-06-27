<?php
namespace App\Controller;

use App\Entity\Contract;
use App\Entity\ContractLogEntry;
use App\Entity\SupportPointEntry;
use App\Entity\TrackRecord;
use App\Enum\ContractLogEntryType;
use App\Enum\TrackStatus;
use App\DataTables\ContractTrackTable;
use App\DataTables\TerrainTable;
use App\Form\ContractLogEntryEditFormType;
use App\Form\PostTrackFormType;
use App\Service\ContractGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contracts/{id}/log')]
class ContractLogController extends AbstractController {
    public function __construct(
        private readonly ContractGeneratorService $generator,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/entry/{entryId}/edit', name: 'app_contracts_log_edit', methods: ['GET', 'POST'])]
    public function editEntry(Contract $contract, int $entryId, Request $request): Response {
        $entry = $this->em->getRepository(ContractLogEntry::class)->find($entryId);
        if (!$entry || $entry->getContract() !== $contract) {
            throw $this->createNotFoundException();
        }
        $form = $this->createForm(ContractLogEntryEditFormType::class, $entry);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($entry->getEntryType() === ContractLogEntryType::TrackSetup && $entry->getTrack() !== null) {
                $track       = $entry->getTrack();
                $missionType = $request->request->get('missionType', $track->getMissionType());
                $terrain     = $request->request->get('terrain', $track->getTerrain());
                $track->setMissionType($missionType);
                $track->setTerrain($terrain);
                $terrainSetting = TerrainTable::getSettingByTerrain($terrain);
                $tofttLabel     = $track->isTakingOneForTeam() ? ' [TOFTT]' : '';
                $entry->setDescription("Track {$track->getTrackNumber()}: {$missionType} on {$terrain} (MegaMek: {$terrainSetting}){$tofttLabel}");
                $data = $entry->getData() ?? [];
                $data['missionType']    = $missionType;
                $data['terrain']        = $terrain;
                $data['terrainSetting'] = $terrainSetting;
                $entry->setData($data);
            }
            $this->em->flush();
            $this->addFlash('success', 'Log entry updated.');
            return $this->redirectToRoute('app_contracts_show', ['id' => $contract->getId()]);
        }
        return $this->render('contract_log/edit.html.twig', [
            'contract'     => $contract,
            'entry'        => $entry,
            'form'         => $form,
            'missionTypes' => ContractTrackTable::getAllMissionTypes(),
            'terrains'     => array_keys(TerrainTable::getAllTerrains()),
        ]);
    }

    #[Route('/entry/{entryId}/delete', name: 'app_contracts_log_delete', methods: ['POST'])]
    public function delete(Contract $contract, int $entryId): Response {
        $entry = $this->em->getRepository(ContractLogEntry::class)->find($entryId);
        if ($entry && $entry->getContract() === $contract) {
            if ($contract->getTracksCompleted() > 0 && $entry->getEntryType() == 'post_track') {
                $contract->setTracksCompleted($contract->getTracksCompleted() - 1);
            }
            $this->addFlash('success', $entry->getEntryType()->value);
            $this->em->remove($entry);
            $this->em->flush();
//            $this->addFlash('success', 'Log entry deleted.');
        }
        return $this->redirectToRoute('app_contracts_show', ['id' => $contract->getId()]);
    }

    #[Route('/add', name: 'app_contracts_log_add', methods: ['GET', 'POST'])]
    public function add(Contract $contract, Request $request): Response {
        $company = $this->getUser()->getCompany();

        $postTrackForm = $this->createForm(PostTrackFormType::class);

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'transport') {
                $this->handleTransport($contract, $company, (int) $request->request->get('jumps', 0));
                $this->addFlash('success', 'Transport recorded.');
            } elseif ($action === 'maintenance') {
                $this->handleMaintenance($contract, $company);
                $this->addFlash('success', 'Maintenance recorded.');
            } elseif ($action === 'base_pay') {
                $this->handleBasePay($contract, $company);
                $this->addFlash('success', 'Base pay recorded.');
            } elseif ($action === 'track_setup') {
                $toftt = (bool) $request->request->get('toftt', false);
                $this->handleTrackSetup($contract, (int) $request->request->get('month'), $toftt);
                $this->addFlash('success', 'Track setup rolled and recorded.');
            } elseif ($action === 'post_track') {
                $postTrackForm->handleRequest($request);
                if ($postTrackForm->isSubmitted() && $postTrackForm->isValid()) {
                    $this->handlePostTrack($contract, $postTrackForm->getData(), $company, (int) $request->request->get('month'));
                    $this->addFlash('success', 'Post-track results recorded.');
                }
            } elseif ($action === 'downtime') {
                $note   = $request->request->get('note', '');
                $month  = (int) $request->request->get('month');
                $amount = (int) $request->request->get('amount', 0);
                $this->handleDowntime($contract, $company, $month, $note, $amount);
                $this->addFlash('success', 'Downtime note added.');
            }

            return $this->redirectToRoute('app_contracts_show', ['id' => $contract->getId()]);
        }

        $currentMonth = $contract->getTracksCompleted() + 1;
        return $this->render('contract_log/add.html.twig', [
            'contract'      => $contract,
            'currentMonth'  => $currentMonth,
            'postTrackForm' => $postTrackForm,
        ]);
    }

    private function handleTransport(Contract $contract, $company, int $jumps): void {
        $full          = 50 + 50 * $jumps;
        $pct           = $contract->parseTransportPercent();
        $employerShare = (int) round($full * $pct / 100);
        $playerPays    = $full - $employerShare;

        $sp = new SupportPointEntry();
        $sp->setCompany($company);
        $sp->setAmount(-$playerPays);
        $sp->setDescription("Transport ({$jumps} jumps)");
        $this->em->persist($sp);

        $pctNote = $pct > 0
            ? " — employer covers {$pct}% (+{$employerShare} SP)"
            : '';
        $log = new ContractLogEntry();
        $log->setContract($contract);
        $log->setMonth($contract->getTracksCompleted() + 1);
        $log->setEntryType(ContractLogEntryType::Transport);
        $log->setDescription("Transport: 50 + (50 × {$jumps}) = {$full} SP total{$pctNote} → player pays -{$playerPays} SP");
        $this->em->persist($log);
        $this->em->flush();
    }

    private function handleMaintenance(Contract $contract, $company): void {
        $amount = -500 * $contract->getScale();
        $sp = new SupportPointEntry();
        $sp->setCompany($company);
        $sp->setAmount($amount);
        $sp->setDescription("Contract maintenance (scale {$contract->getScale()})");
        $this->em->persist($sp);

        $log = new ContractLogEntry();
        $log->setContract($contract);
        $log->setMonth($contract->getTracksCompleted() + 1);
        $log->setEntryType(ContractLogEntryType::Maintenance);
        $log->setDescription("Maintenance deducted: $amount SP");
        $this->em->persist($log);
        $this->em->flush();
    }

    private function handleBasePay(Contract $contract, $company): void {
        $amount = $contract->calculateMonthlyBasePay();
        $sp = new SupportPointEntry();
        $sp->setCompany($company);
        $sp->setAmount($amount);
        $sp->setDescription("Base pay (scale {$contract->getScale()})");
        $this->em->persist($sp);

        $log = new ContractLogEntry();
        $log->setContract($contract);
        $log->setMonth($contract->getTracksCompleted() + 1);
        $log->setEntryType(ContractLogEntryType::BasePay);
        $log->setDescription("Base pay received: +$amount SP");
        $this->em->persist($log);
        $this->em->flush();
    }

    private function handleTrackSetup(Contract $contract, int $month, bool $toftt = false): void {
        $result = $this->generator->rollTrackSetup($contract->getType(), $contract->getCommandRights());

        $track = new TrackRecord();
        $track->setContract($contract);
        $track->setTrackNumber($contract->getTracksCompleted() + 1);
        $track->setMissionType($result['missionType']);
        $track->setTerrain($result['terrain']);
        $track->setCommandComplication($result['complication']);
        $track->setStatus(TrackStatus::Pending);
        $track->setTakingOneForTeam($toftt);
        $this->em->persist($track);

        $tofttLabel = $toftt ? ' [TOFTT]' : '';
        $log = new ContractLogEntry();
        $log->setContract($contract);
        $log->setTrack($track);
        $log->setMonth($month);
        $log->setEntryType(ContractLogEntryType::TrackSetup);
        $log->setDescription("Track {$track->getTrackNumber()}: {$result['missionType']} on {$result['terrain']} (MegaMek: {$result['terrainSetting']}){$tofttLabel}");
        $log->setData($result);
        $this->em->persist($log);
        $this->em->flush();
    }

    private function handlePostTrack(Contract $contract, array $data, $company, int $month): void {
        $tier      = $data['combatPayTier'];
        $combatPay = $contract->calculateMonthlyCombatPay($tier);

        $pendingTrack = null;
        foreach ($contract->getTrackRecords() as $track) {
            if ($track->getStatus() === TrackStatus::Pending) {
                $pendingTrack = $track;
                break;
            }
        }

        $toftt = $pendingTrack?->isTakingOneForTeam() ?? false;
        if ($toftt) {
            $combatPay = (int) floor($combatPay / 2);
        }

        if ($combatPay > 0) {
            $sp = new SupportPointEntry();
            $sp->setCompany($company);
            $sp->setAmount($combatPay);
            $sp->setDescription("Combat pay ({$tier->value})");
            $this->em->persist($sp);
        }

        foreach ($contract->getTrackRecords() as $track) {
            if ($track->getStatus() === TrackStatus::Pending) {
                $track->setStatus(TrackStatus::Completed);
                $track->setCompletedAt(new \DateTimeImmutable());
                $track->setCombatPayTier($tier);
                break;
            }
        }

        $contract->setTracksCompleted($contract->getTracksCompleted() + 1);
        if ($contract->getTracksCompleted() >= $contract->getNumberOfTracks()) {
            $contract->setStatus(\App\Enum\ContractStatus::Completed);
        }

        $salvageNote = $data['salvageClaimed'] ? 'Salvage claimed.' : 'No salvage.';
        $tofttNote   = $toftt ? ' (TOFTT — half pay)' : '';
        $log = new ContractLogEntry();
        $log->setContract($contract);
        $log->setMonth($month);
        $log->setEntryType(ContractLogEntryType::PostTrack);
        $log->setDescription("Combat pay: " . ($combatPay > 0 ? "+$combatPay SP" : "none") . " ({$tier->value}){$tofttNote}. $salvageNote");
        $this->em->persist($log);
        $this->em->flush();
    }

    private function handleDowntime(Contract $contract, $company, int $month, string $note, int $amount = 0): void {
        if ($amount !== 0) {
            $sp = new SupportPointEntry();
            $sp->setCompany($company);
            $sp->setAmount($amount);
            $sp->setDescription("Downtime — " . ($note ?: 'no note'));
            $this->em->persist($sp);
        }

        $amountNote = $amount !== 0 ? " (" . ($amount >= 0 ? "+$amount" : "$amount") . " SP)" : '';
        $log = new ContractLogEntry();
        $log->setContract($contract);
        $log->setMonth($month);
        $log->setEntryType(ContractLogEntryType::Downtime);
        $log->setDescription(($note ?: '(no note)') . $amountNote);
        $this->em->persist($log);
        $this->em->flush();
    }
}
