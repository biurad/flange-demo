<?php declare(strict_types=1);

/*
 * This file is part of RadePHP Demo Project
 *
 * @copyright 2022 Divine Niiquaye Ibok (https://divinenii.com/)
 * @license   https://opensource.org/licenses/MIT License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Sharding\PoolingShardConnection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Doctrine\Common\DataFixtures\{FixtureInterface, Loader};
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Load data fixtures for doctrine entity schemes.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class DataFixturesCommand extends Command
{
    private Loader $fixturesLoader;

    /**
     * @param array<int,FixtureInterface> $fixtureFactories
     */
    public function __construct(private EntityManagerInterface $doctrine, array $fixtureFactories = [])
    {
        parent::__construct();
        $this->fixturesLoader = new Loader();

        foreach ($fixtureFactories as $fixtureFactory) {
            $this->fixturesLoader->addFixture($fixtureFactory);
        }
    }

    protected function configure(): void
    {
        $this
            ->setName('orm:fixtures:load')
            ->setDescription('Load data fixtures to your database')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of deleting all data from the database first.')
            ->addOption('purge-exclusions', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'List of database tables to ignore while purging')
            ->addOption('shard', null, InputOption::VALUE_REQUIRED, 'The shard connection to use for this command.')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command loads data fixtures from your application:

  <info>php %command.full_name%</info>

Fixtures are services that are tagged with <comment>doctrine.fixture.orm</comment>.

If you want to append the fixtures instead of flushing the database first you can use the <comment>--append</comment> option:

  <info>php %command.full_name%</info> <comment>--append</comment>

By default Doctrine Data Fixtures uses DELETE statements to drop the existing rows from the database.
If you want to use a TRUNCATE statement instead you can use the <comment>--purge-with-truncate</comment> flag:

  <info>php %command.full_name%</info> <comment>--purge-with-truncate</comment>

EOT
            );
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ui = new SymfonyStyle($input, $output);
        $em = $this->doctrine;

        if (!$input->getOption('append')) {
            if (!$ui->confirm(\sprintf('Careful, database "%s" will be purged. Do you want to continue?', $em->getConnection()->getDatabase()), !$input->isInteractive())) {
                return Command::SUCCESS;
            }
        }

        if ($input->getOption('shard')) {
            if (!$em->getConnection() instanceof PoolingShardConnection) {
                throw new \LogicException(\sprintf('Connection of EntityManager "%s" must implement shards configuration.', $em::class));
            }

            $em->getConnection()->connect($input->getOption('shard'));
        }

        if (!$fixtures = $this->fixturesLoader->getFixtures()) {
            $ui->error('Could not find any fixture services to load.');

            return Command::FAILURE;
        }

        $purger = new ORMPurger($em, $input->getOption('purge-exclusions'));
        $purger->setPurgeMode($input->getOption('purge-with-truncate') ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);

        $executor = new ORMExecutor($em, $purger);
        $executor->setLogger(static function ($message) use ($ui): void {
            $ui->text(\sprintf('  <comment>></comment> <info>%s</info>', $message));
        });
        $executor->execute($fixtures, $input->getOption('append'));

        return Command::SUCCESS;
    }
}
