<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Message\VerifyCompany;
use Tmonier\SyliusCompanyVerificationPlugin\Verification\VerificationRepositoryInterface;

#[AsCommand(name: 'tmonier:company-verification:verify-unverified', description: 'Verifies again the companies never verified or whose last verification could not reach a service (to schedule).')]
final class VerifyUnverifiedCommand extends Command
{
    public function __construct(
        private readonly VerificationRepositoryInterface $repository,
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ids = $this->repository->findCompanyIdsToVerify();

        foreach ($ids as $id) {
            $this->bus->dispatch(new VerifyCompany($id));
        }

        $output->writeln(sprintf('%d verification(s) requested.', \count($ids)));

        return Command::SUCCESS;
    }
}
