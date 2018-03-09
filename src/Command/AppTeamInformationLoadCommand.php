<?php

namespace App\Command;

use App\Entity\TeamInformation;
use App\Repository\TeamInformationRepository;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class AppTeamInformationLoadCommand
 * @package App\Command
 */
class AppTeamInformationLoadCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'app:team-information:load';

    /** @var SymfonyStyle */
    protected $io;

    /** @var string|null  */
    protected $csvFilename = null;

    /** @var resource|null */
    protected $csvFileHandle = null;

    /** @var TeamInformationRepository */
    protected $teamInformationRepo;

    /** @var bool */
    protected $truncateData = false;


    /**
     * AppTeamInformationLoadCommand constructor.
     *
     * @param TeamInformationRepository $teamInformationRepository
     */
    public function __construct(TeamInformationRepository $teamInformationRepository)
    {
        parent::__construct();

        $this->teamInformationRepo = $teamInformationRepository;
    }

    /**
     * Setup command line options and arguments
     */
    protected function configure()
    {
        $this
            ->setDescription('Load a CSV file containing information on the teams')
            ->addArgument(
                'csvFile',
                InputArgument::REQUIRED,
                'CSV file containing team information'
            )
            ->addOption(
                'truncate',
                't',
                InputOption::VALUE_NONE,
                'Delete existing data from database'
            );
    }

    /**
     * Mainline - Do what the command is supposed to perform
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $arguments = array_merge($input->getArguments(), $input->getOptions());

        $this->processCommandLine($arguments);

        if ($this->truncateData) {
            $this->teamInformationRepo->truncate();
        }

        $this->openCsvFile();
        $this->processCSVLoad();
        $this->closeCsvFile();

        $this->io->success('... team information has been successfully loaded');
    }

    /**
     * Process the switches/options provided on the command line
     *
     * @param array $arguments
     */
    protected function processCommandLine(array $arguments)
    {
        foreach ($arguments as $argumentKey => $argumentValue) {
            switch ($argumentKey) {
                case 'csvFile':
                    if (!file_exists($argumentValue) ||
                        !is_readable($argumentValue) ||
                        !is_file($argumentValue)
                    ) {
                        $this->io->error("Value -" . $argumentValue . "- is not an existing, readable file");
                        die();
                    }

                    $this->io->text("... using input file: " . $argumentValue);
                    $this->csvFilename = $argumentValue;

                    break;

                case 'truncate':
                    if (!empty($argumentValue)) {
                        $this->io->warning("Existing team information will be deleted");
                        $this->truncateData = true;
                    }
                    break;
            }
        }

        if ($this->csvFilename === null) {
            $this->io->error("Command is missing required values to operating. Exiting");
            die();
        }
    }

    /**
     * Open CSV file
     */
    protected function openCsvFile()
    {
        $this->csvFileHandle = fopen($this->csvFilename, "r");
        if ($this->csvFileHandle === false) {
            die("Error on opening csv file");
        }

    }

    /**
     * Close CSV file
     */
    protected function closeCsvFile()
    {
        if ($this->csvFileHandle != null) {
            fclose($this->csvFileHandle);
        }
    }

    /**
     * Loads information from CSV file into database
     */
    protected function processCSVLoad()
    {
        $row = 0;
        $this->io->text("... reading in team information");

        while ($line = fgetcsv($this->csvFileHandle)) {
            /**
             * @todo Should allow for a header row
             * @todo Should header row to reorder fields
             * @todo Use constants for the index values into $line
             * @todo Validate each row for minimum compliance
             */
            $row++;
            $teamNumInDiv = $line[0];
            $teamName = $line[1];
            $teamDivision = $line[2];

            $preferences = array();
            for ($i=3; $i < count($line); $i++) {
                $preferences[] = $line[$i];
            }

            $gameLocation = new TeamInformation(
                $teamNumInDiv,
                $teamName,
                $teamDivision
            );
            if (!empty($preferences)) {
                $gameLocation->setPreferences($preferences);
            }

            $this->teamInformationRepo->save($gameLocation);
            unset($gameLocation);

            if ($row % 5 === 0) {
                $this->io->text("    - loaded " . $row . " records");
            }
        }

        $this->io->text("... completed reading in " . $row . " team records");
    }
}
