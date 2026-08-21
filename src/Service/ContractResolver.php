<?php

namespace App\Service;

use App\Entity\Contract;
use App\Entity\MercenaryCompany;
use App\Repository\ContractRepository;

/**
 * Resolves the active contract for a mercenary company.
 * An active contract is one with status 'Accepted' or 'Active'.
 */
class ContractResolver
{
    public function __construct(
        private readonly ContractRepository $contractRepo,
    ) {}

    /**
     * Find the most recent active contract for the given company.
     *
     * Active means status is 'Accepted' or 'Active'.
     * Returns null if no active contract exists.
     */
    public function resolveActiveContract(MercenaryCompany $company): ?Contract
    {
        return $this->contractRepo->findActiveContractByCompany($company);
    }
}
