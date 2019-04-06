<?php

namespace App\Command;

use App\Service\ScheduleService;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class AppScheduleGenerateCommand
 * @package App\Command
 */
class AppScheduleGenerateCommand extends Command
{
    protected static $defaultName = 'app:schedule:generate';

    /** @var SymfonyStyle */
    protected $io;

    /** @var null|DateTime */
    protected $scheduleStartDate = null;

    /** @var int */
    protected $gameLengthInMins = 0;

    /** @var ScheduleService */
    protected $scheduleService;

    /** @var array */
    protected $flowControl = array(
        'generateGamesToSchedule' => false,
    );

    /**
     * AppScheduleGenerateCommand constructor.
     *
     * @param ScheduleService $scheduleService
     */
    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;

        parent::__construct();
    }

    /**
     * Define acceptable options and switches
     */
    protected function configure()
    {
        $this
            ->setDescription('Generate a default schedule, given some seeding information')
            ->addOption(
                'startDate',
                null,
                InputOption::VALUE_REQUIRED,
                'First day of schedule (YYYY-MM-DD)'
            )
            ->addOption(
                'gameLength',
                null,
                InputOption::VALUE_REQUIRED,
                'Time, in minutes, each game is alloted'
            )
            ->addOption(
                'genGamesToSched',
                null,
                InputOption::VALUE_NONE,
                'Prepare list of games to schedule and an empty schedule with timeslots'
            )
        ;
    }

    /**
     * Do the work required
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) : void
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->validateArguments($input->getOptions());
        $rc = $this->scheduleService->generateSchedule(
            $this->scheduleStartDate,
            $this->gameLengthInMins,
            $this->flowControl,
            $this->io
        );

        if ($rc) {
            $this->io->success("... all games slotted into schedule");
        } else {
            $this->io->warning("... some games remain to be slotted into schedule");
        }
    }

    /**
     * Validate arguments against acceptable command line options and switches
     *
     * @param array $arguments
     */
    private function validateArguments(array $arguments)
    {
        foreach ($arguments as $argumentKey => $argumentValue) {
            switch ($argumentKey) {
                case 'startDate':
                    if (strtotime($argumentValue) === false
                    ) {
                        $this->io->error("Value " . $argumentValue . " is not a valid start date");
                        die();
                    }

                    $this->io->text("... using as start date: " . $argumentValue);
                    $this->scheduleStartDate = $argumentValue;

                    break;

                case 'gameLength':
                    $minsInDay = 24 * 60;
                    $gameLength = (int) $argumentValue;
                    if (0 >= $gameLength ||
                        $gameLength > $minsInDay
                    ) {
                        $this->io->error(
                            "Value " . $argumentValue . " must be an integer value between 1 and  " . $minsInDay . ": " . $gameLength
                        );
                        die();
                    }
                    $this->io->text("... using as game length: " . $argumentValue);
                    $this->gameLengthInMins = $argumentValue;

                    break;

                case 'genGamesToSched':
                    if (!empty($argumentValue)) {
                        $this->flowControl['generateGamesToSchedule'] = true;
                        $this->io->text("... will generate games to schedule for divisions in league");
                    }

                    break;
            }
        }

        if ($this->scheduleStartDate === null) {
            $this->io->error("Command is missing required values to operating. Exiting");
            die();
        }
    }
}
