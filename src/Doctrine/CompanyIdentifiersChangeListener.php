<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;
use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Message\VerifyCompany;

/**
 * Asks for a verification when a company is created or when its VAT or registration number
 * changes. The message is sent once the company is saved (it needs its id).
 */
final class CompanyIdentifiersChangeListener
{
    /** @var list<CompanyInterface> */
    private array $pending = [];

    public function __construct(private readonly MessageBusInterface $bus)
    {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof CompanyInterface) {
                $this->pending[] = $entity;
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof CompanyInterface && $this->identifiersChanged($unitOfWork->getEntityChangeSet($entity))) {
                $this->pending[] = $entity;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $companies = $this->pending;
        $this->pending = [];

        foreach ($companies as $company) {
            if (null !== $company->getId()) {
                $this->bus->dispatch(new VerifyCompany($company->getId()));
            }
        }
    }

    /** @param array<string, mixed> $changeSet */
    private function identifiersChanged(array $changeSet): bool
    {
        return isset($changeSet['vatNumber']) || isset($changeSet['registrationNumber']);
    }
}
