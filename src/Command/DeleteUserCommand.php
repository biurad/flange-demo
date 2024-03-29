<?php declare(strict_types=1);

/*
 * This file is part of Flange Blog Demo Project
 *
 * @copyright 2022 Divine Niiquaye Ibok (https://divinenii.com/)
 * @license   https://opensource.org/licenses/MIT License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\User;
use App\Security\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * A console command that deletes users from the database.
 *
 * To use this command, open a terminal window, enter into your project
 * directory and execute the following:
 *
 *     $ php rade security:delete-user
 *
 * Check out the code of the src/Command/AddUserCommand.php file for
 * the full explanation about Symfony commands.
 *
 * @author Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 */
class DeleteUserCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Validator $validator,
        private ?LoggerInterface $logger = null
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('security:delete-user')
            ->setDescription('Deletes users from the database')
            ->addArgument('username', InputArgument::REQUIRED, 'The username of an existing user')
            ->setHelp(
                <<<'HELP'
                The <info>%command.name%</info> command deletes users from the database:

                  <info>php %command.full_name%</info> <comment>username</comment>

                If you omit the argument, the command will ask you to
                provide the missing value:

                  <info>php %command.full_name%</info>
                HELP
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle is an optional feature that Symfony provides so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument('username')) {
            return;
        }

        $this->io->title('Delete User Command Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console app:delete-user username',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
            '',
        ]);

        $username = $this->io->ask('Username', null, [$this->validator, 'validateUsername']);
        $input->setArgument('username', $username);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $this->validator->validateUsername($input->getArgument('username'));

        /** @var User|null $user */
        $user = $this->entityManager->getRepository(User::class)->loadUserByIdentifier($username);

        if (null === $user) {
            throw new RuntimeException(\sprintf('User with username "%s" not found.', $username));
        }

        // After an entity has been removed, its in-memory state is the same
        // as before the removal, except for generated identifiers.
        // See https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/working-with-objects.html#removing-entities
        $userId = $user->getId();

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $userUsername = $user->getUsername();
        $userEmail = $user->getEmail();

        $this->io->success(\sprintf('User "%s" (ID: %d, email: %s) was successfully deleted.', $userUsername, $userId, $userEmail));

        // Logging is helpful and important to keep a trace of what happened in the software runtime flow.
        // See https://symfony.com/doc/current/logging.html
        $this->logger?->info('User "{username}" (ID: {id}, email: {email}) was successfully deleted.', ['username' => $userUsername, 'id' => $userId, 'email' => $userEmail]);

        return Command::SUCCESS;
    }
}
