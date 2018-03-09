<?php

namespace App\Command;

use App\Entity\GameLocation;
use App\Repository\GameLocationRepository;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class AppDefaultLocationsLoadCommand
 * @package App\Command
 */
class AppDefaultLocationsLoadCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'app:default-locations:load';

    /** @var SymfonyStyle */
    protected $io;

    /** @var string|null  */
    protected $csvFilename = null;

    /** @var resource|null */
    protected $csvFileHandle = null;

    /** @var GameLocationRepository */
    protected $gameLocationRepo;

    /** @var string[] */
    protected $validDayOfWeekValues = array(
        "Mon",
        "Monday",
        "Tue",
        "Tuesday",
        "Wed",
        "Wednesday",
        "Thu",
        "Thursday",
        "Fri",
        "Friday",
        "Sat",
        "Saturday",
        "Sun",
        "Sunday",
    );

    /** @var bool */
    protected $truncateData = false;


    /**
     * AppDefaultLocationsLoadCommand constructor.
     *
     * @param GameLocationRepository $gameLocationRepository
     */
    public function __construct(GameLocationRepository $gameLocationRepository)
    {
        parent::__construct();

        $this->gameLocationRepo = $gameLocationRepository;
    }


    /**
     * Define command line options/switches
     */
    protected function configure()
    {
        $this
            ->setDescription('Load a CSV file containing the default game locations')
            ->addArgument(
                'csvFile',
                InputArgument::REQUIRED,
                'CSV file containing game location details'
            )
            ->addOption(
                'truncate',
                't',
                InputOption::VALUE_NONE,
                'Delete existing data from database'
            );
    }

    /**
     * Main action - do what the command is meant to do
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $arguments = array_merge($input->getArguments(), $input->getOptions());

        $this->validateArguments($arguments);

        if ($this->truncateData) {
            $this->gameLocationRepo->truncate();
        }

        $this->openCsvFile();
        $this->processCSVLoad();
        $this->closeCsvFile();

        $this->io->success('... game location information has been successfully loaded');
    }

    /**
     * Validate the switches/options provided on the command line
     *
     * @param array $arguments
     */
    protected function validateArguments(array $arguments)
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
                        $this->io->warning("Existing data for Game Location will be deleted");
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
     * Read in the contents of the specified CSV file
     */
    protected function processCSVLoad()
    {
        $row = 0;
        $this->io->text("... reading in game location information");

        while ($line = fgetcsv($this->csvFileHandle)) {
            /**
             * @todo Should allow for a header row
             * @todo Should header row to reorder fields
             * @todo Use constants for the index values into $line
             */

            $row++;
            if ($this->isCsvInputValid($line, $row)) {
                $action = $line[0];
                $location = $line[1];
                $dayOfWeek = $line[2];
                $startAvailable = new \DateTimeImmutable($line[3]);
                $endAvailable = new \DateTimeImmutable($line[4]);

                $gameLocation = new GameLocation(
                    $location,
                    $dayOfWeek,
                    $startAvailable,
                    $endAvailable,
                    $action
                );
                $this->gameLocationRepo->save($gameLocation);
                unset($gameLocation);

                if ($row % 5 === 0) {
                    $this->io->text("    - loaded " . $row . " records");
                }
            } else {
                die();
            }
        }

        $this->io->text("... completed reading in " . $row . " game location records");
    }

    /**
     * Validate a line of data from the CSV meets the expected structure
     *
     * @param array $line
     * @param int   $row
     *
     * @return bool
     */
    protected function isCsvInputValid(array $line, int $row)
    {
        if (count($line) != 5) {
            $this->io->error("Row " . $row . " contains too many or too few fields: " . print_r($line, true));
            return false;
        }

        $action = strtoupper($line[0]);
        $location = $line[1];
        $dayOfWeek = $line[2];
        $startAvailable = $line[3];
        $endAvailable = $line[4];

        if ($action != "ADD" &&
            $action != "DELETE"
        ) {
            $this->io->error("Row " . $row . " contains invalid action: " . $action);
            return false;
        }

        if (empty($location)) {
            $this->io->error("Row " . $row . " missing a location value");
        }

        if (($action == "DELETE" && !$this->isValidDateFormat($dayOfWeek)) ||
            ($action == "ADD" && !in_array($dayOfWeek, $this->validDayOfWeekValues))
        ) {
            $this->io->error("Row " . $row . " contains invalid day of week value: " . $dayOfWeek);
            return false;
        }

        if (!$this->isValidDateFormat($startAvailable)) {
            $this->io->error("Row " . $row . " contains invalid date for start available: " . $startAvailable);
            return false;
        }

        if (!$this->isValidDateFormat($endAvailable)) {
            $this->io->error("Row " . $row . " contains invalid date for end available: " . $endAvailable);
            return false;
        }

        return true;
    }

    /**
     * Ensure date is valid
     *
     * This function will validate using strtotime() so any date format that that function allows is accepted
     *
     * @param string $dateToCheck
     *
     * @return bool
     */
    protected function isValidDateFormat(string $dateToCheck)
    {
        $isValid = true;

        if (strtotime($dateToCheck) === false) {
            $isValid = false;
        }

        return $isValid;
    }
}
