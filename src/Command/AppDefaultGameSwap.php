<?php

namespace App\Command;

use App\Entity\ScheduledGame;
use App\Repository\ScheduledGameRepository;
use App\Service\ScheduledGameService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class AppDefaultGameSwap
 * @package App\Command
 */
class AppDefaultGameSwap extends Command
{
    protected static $defaultName = 'app:game:swap';

    /** @var SymfonyStyle */
    protected $io;

    /** @var ScheduledGameRepository */
    protected $scheduledGameRepo;

    /** @var ScheduledGameRepository */
    protected $scheduledGameService;

    /** @var null|ScheduledGame */
    protected $slot1 = null;

    /** @var null|ScheduledGame */
    protected $slot2 = null;

    /**
     * AppDefaultGameSwap constructor.
     *
     * @param string|null             $name
     * @param ScheduledGameRepository $scheduledGameRepo
     * @param ScheduledGameService    $scheduledGameService
     */
    public function __construct(
        ScheduledGameRepository $scheduledGameRepo,
        ScheduledGameService $scheduledGameService,
        string $name = null
    ) {
        parent::__construct($name);
        $this->scheduledGameRepo = $scheduledGameRepo;
        $this->scheduledGameService = $scheduledGameService;
    }

    /**
     * Prepare the command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    }

    /**
     * List command-specific options
     */
    protected function configure()
    {
        $this
            ->setDescription('Swap date, time and diamond details for 2 game slots')
            ->addOption(
                'slot1',
                null,
                InputOption::VALUE_REQUIRED,
                'Id of first slot'
            )
            ->addOption(
                'slot2',
                null,
                InputOption::VALUE_REQUIRED,
                'Id of second slot'
            );
    }

    /**
     * Do the job
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $arguments['slot1'] = $input->getOption('slot1');
        $arguments['slot2'] = $input->getOption('slot2');

        $this->validateArguments($arguments);

        $response = $this->io->ask("Enter Y to perform the swap of these 2 game slots.", "N");
        if (mb_strtoupper($response) == "Y") {
            $this->performSwap();
            $this->io->success('... scheduling information has been swapped as requested');
        } else {
            $this->io->caution("Aborting swap action at user's behest");
        }
    }

    /**
     * Validate command line arguments
     *
     * @param array $arguments
     */
    protected function validateArguments(array $arguments)
    {
        foreach ($arguments as $argumentKey => $argumentValue) {
            switch ($argumentKey) {
                case 'slot1':
                    if (!is_numeric($argumentValue) ||
                        $argumentValue <= 0
                    ) {
                        $this->io->error("Id of slots must be an integer value greater than 0");
                        die();
                    } else {
                        $this->slot1 = $this->scheduledGameRepo->getScheduledGameDetails($argumentValue);
                        $this->displayGameDetails($this->slot1);
                    }
                    break;

                case 'slot2':
                    if (!is_numeric($argumentValue) ||
                        $argumentValue <= 0
                    ) {
                        $this->io->error("Id of slots must be an integer value greater than 0");
                        die();
                    } else {
                        $this->slot2 = $this->scheduledGameRepo->getScheduledGameDetails($argumentValue);
                        $this->displayGameDetails($this->slot2);
                    }
                    break;
            }
        }

        if ($this->slot1 === null ||
            $this->slot2 === null
        ) {
            $this->io->error("Command is missing required values to operate. Exiting.");
            die();
        }

        if ($this->slot1 === $this->slot2) {
            $this->io->error("You must provide 2 different scheduling slots. Exiting.");
            die();
        }
    }

    /**
     * @param ScheduledGame $game
     */
    protected function displayGameDetails(ScheduledGame $game)
    {
        $gameDetails = $this->scheduledGameService->formatAsArray(
            $this->scheduledGameService->mapScheduledGame(
                $game
            )
        );

        $this->io->note(print_r($gameDetails, true));
    }

    protected function performSwap()
    {
        $tempGameDate = $this->slot2->getGameDate();
        $tempGameTime = $this->slot2->getGameTime();
        $tempLocation = $this->slot2->getGameLocation();

        $this->slot2->setGameDate($this->slot1->getGameDate());
        $this->slot2->setGameTime($this->slot1->getGameTime());
        $this->slot2->setGameLocation($this->slot1->getGameLocation());

        $this->slot1->setGameDate($tempGameDate);
        $this->slot1->setGameTime($tempGameTime);
        $this->slot1->setGameLocation($tempLocation);

        $this->scheduledGameRepo->save($this->slot1);
        $this->scheduledGameRepo->save($this->slot2);
    }
}
