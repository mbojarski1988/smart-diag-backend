<?php

declare(strict_types=1);

namespace App\User\Command;

use App\User\Application\Dto\UserWriteRequest;
use App\User\Application\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;

#[AsCommand(name: 'app:create-admin', description: 'Bootstrap the first administrator account')]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly UserManager $userManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email address')
            ->addOption('firstName', null, InputOption::VALUE_REQUIRED, 'First name')
            ->addOption('lastName', null, InputOption::VALUE_REQUIRED, 'Last name')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password (min 8 chars)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $emailRaw     = $input->getOption('email');
        $firstNameRaw = $input->getOption('firstName');
        $lastNameRaw  = $input->getOption('lastName');
        $passwordRaw  = $input->getOption('password');

        $email     = is_string($emailRaw) ? $emailRaw : '';
        $firstName = is_string($firstNameRaw) ? $firstNameRaw : '';
        $lastName  = is_string($lastNameRaw) ? $lastNameRaw : '';
        $password  = is_string($passwordRaw) ? $passwordRaw : '';

        if ($email === '' || $firstName === '' || $lastName === '' || $password === '') {
            $output->writeln('<error>All options required: --email --firstName --lastName --password</error>');

            return Command::FAILURE;
        }

        $fakeRequest = new Request(content: (string) json_encode([
            'email'     => $email,
            'firstName' => $firstName,
            'lastName'  => $lastName,
            'role'      => 'ROLE_ADMIN',
            'password'  => $password,
        ]));

        $dto = UserWriteRequest::fromRequest($fakeRequest);

        if (is_string($dto)) {
            $output->writeln("<error>Validation error: {$dto}</error>");

            return Command::FAILURE;
        }

        $result = $this->userManager->create($dto);

        if (is_string($result)) {
            $output->writeln("<error>Error: {$result}</error>");

            return Command::FAILURE;
        }

        $this->entityManager->persist($result);
        $this->entityManager->flush();

        $output->writeln("<info>Admin user created: {$email}</info>");

        return Command::SUCCESS;
    }
}
