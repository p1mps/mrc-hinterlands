<?php

namespace App\Service;

use App\Entity\MercenaryCompany;
use App\Entity\SupportPointEntry;
use Doctrine\ORM\EntityManagerInterface;

class SupportPointService
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    /** @return array{company: MercenaryCompany, entries: \Doctrine\Common\Collections\Collection, balance: int} */
    public function getCompanySupportPoints(MercenaryCompany $company): array
    {
        return [
            'company' => $company,
            'entries' => $company->getSupportPointEntries(),
            'balance' => $company->getSupportPointsBalance(),
        ];
    }

    public function addEntry(MercenaryCompany $company, int $amount, string $description): SupportPointEntry
    {
        $entry = new SupportPointEntry();
        $entry->setCompany($company);
        $entry->setAmount($amount);
        $entry->setDescription($description);

        $this->em->persist($entry);
        $this->em->flush();

        return $entry;
    }

    public function deleteEntry(SupportPointEntry $entry): void
    {
        $this->em->remove($entry);
        $this->em->flush();
    }
}
