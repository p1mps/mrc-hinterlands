<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-contract-description',
    description: 'Generate a BattleTech Hinterlands contract description in markdown from an existing contract.',
    aliases: ['app:contract-desc']
)]
class GenerateContractDescriptionCommand extends Command
{
    private const LM_STUDIO_URL = 'http://localhost:1234/v1/chat/completions';
    private const MODEL_NAME = 'qwen/qwen3.6-35b-a3b';

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('contract_id', InputArgument::REQUIRED, 'The contract ID to generate a description for');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $contractId = (int) $input->getArgument('contract_id');

        $qb = $this->em->createQueryBuilder();
        $qb->select('c.id, c.name, c.employer, c.employerAffiliation, c.type, c.scale,
                      c.durationMonths, c.basePayPercent, c.commandRights, c.supportTerms,
                      c.salvageRights, c.transportTerms, c.numberOfTracks, c.tracksCompleted,
                      c.status, c.planet, c.intensity, c.isOpposing')
           ->from(\App\Entity\Contract::class, 'c')
           ->where('c.id = :id')
           ->setParameter('id', $contractId);

        $result = $qb->getQuery()->getSingleResult(null);

        if (!$result) {
            $io->error("No contract found with ID {$contractId}.");
            return Command::FAILURE;
        }

        $contract = [
            'id'               => $result['id'],
            'name'             => $result['name'] ?: null,
            'employer'         => $result['employer'],
            'employerAffiliation' => $result['employerAffiliation'] ?: null,
            'type'             => $result['type']?->value ?: null,
            'scale'            => $result['scale'],
            'durationMonths'   => $result['durationMonths'],
            'basePayPercent'   => $result['basePayPercent'] ?: null,
            'commandRights'    => $result['commandRights']?->value ?: null,
            'supportTerms'     => $result['supportTerms'] ?: null,
            'salvageRights'    => $result['salvageRights'] ?: null,
            'transportTerms'   => $result['transportTerms'] ?: null,
            'numberOfTracks'   => $result['numberOfTracks'],
            'tracksCompleted'  => $result['tracksCompleted'],
            'status'           => $result['status']?->value ?: null,
            'planet'           => $result['planet'] ?: null,
            'intensity'        => $result['intensity'] ?: null,
            'isOpposing'       => $result['isOpposing'] ? 'true' : 'false',
        ];

        $prompt = $this->buildPrompt($contract);

        $io->text('Sending request to LM Studio...');

        $response = $this->callLmStudio($prompt);

        if (!$response) {
            return Command::FAILURE;
        }

        $io->text('');
        $io->text($response);
        $io->text('');

        return Command::SUCCESS;
    }

    private function buildPrompt(array $contract): string
    {
        $fieldLabels = [
            'id'               => 'ID',
            'name'             => 'Name',
            'employer'         => 'Employer',
            'employerAffiliation' => 'Employer Affiliation',
            'type'             => 'Contract Type',
            'scale'            => 'Scale',
            'durationMonths'   => 'Duration (months)',
            'basePayPercent'   => 'Base Pay %',
            'commandRights'    => 'Command Rights',
            'supportTerms'     => 'Support Terms',
            'salvageRights'    => 'Salvage Rights',
            'transportTerms'   => 'Transport Terms',
            'numberOfTracks'   => 'Number of Tracks',
            'tracksCompleted'  => 'Tracks Completed',
            'status'           => 'Status',
            'planet'           => 'Planet',
            'intensity'        => 'Intensity',
            'isOpposing'       => 'Is Opposing',
        ];

        $prompt = "You are a BattleTech hinterlands contract writer. "
            . "Using the contract details below, generate a compelling markdown description "
            . "that serves as the mission briefing given to a mercenary commander.\n\n"
            . "The description should:\n"
            . "- Establish the narrative context of the contract\n"
            . "- Explain who is offering it and why\n"
            . "- Reference relevant BattleTech geopolitical factions and dynamics\n"
            . "- Match BattleTech's gritty, morally complex tone\n"
            . "- Include enough intrigue to make the mercenary commander cautious\n"
            . "- Keep it plausible as a mission briefing\n\n"
            . "Format the output in markdown (headings, paragraphs, lists, bold/italic).\n\n"
            . "---\n"
            . "Contract Details:\n";

        foreach ($fieldLabels as $key => $label) {
            if (isset($contract[$key])) {
                $value = $contract[$key];
                if (empty($value) || $value === '' || $value === 'NULL' || $value === 'null' || $value === '0') {
                    $value = '—';
                }
                $prompt .= "- {$label}: {$value}\n";
            }
        }

        return $prompt;
    }

    private function callLmStudio(string $prompt): ?string
    {
        $payload = json_encode([
            'model'  => self::MODEL_NAME,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 4096,
        ]);

        $ch = curl_init(self::LM_STUDIO_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return null;
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['choices'][0]['message']['content'])) {
            return null;
        }

        return $data['choices'][0]['message']['content'];
    }
}
