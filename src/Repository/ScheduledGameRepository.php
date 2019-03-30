<?php

namespace App\Repository;

use App\Entity\ScheduledGame;

use App\Service\DateUtilityService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ScheduledGameRepository
 * @package App\Repository
 */
class ScheduledGameRepository extends ServiceEntityRepository
{
    /** @var DateUtilityService  */
    private $dateUtilityService;

    /**
     * ScheduledGameRepository constructor.
     *
     * @param RegistryInterface  $registry
     * @param DateUtilityService $dateUtilityService
     */
    public function __construct(
        RegistryInterface $registry,
        DateUtilityService $dateUtilityService
    )
    {
        parent::__construct($registry, ScheduledGame::class);

        $this->dateUtilityService = $dateUtilityService;
    }

    /**
     * Save record to the database
     *
     * @param ScheduledGame $entity
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(ScheduledGame $entity)
    {
        $em = $this->getEntityManager();

        $em->persist($entity);
        $em->flush();
    }

    /**
     * Truncate the table
     */
    public function truncate()
    {
        $sql = "SET FOREIGN_KEY_CHECKS=0;
                TRUNCATE Scheduled_Game;
                SET FOREIGN_KEY_CHECKS=1;
                ";
        $dbConn = $this->getEntityManager()->getConnection();
        $dbCmd = $dbConn->prepare($sql);
        $dbCmd->execute();
    }

    /**
     * Find records between given set of dates
     *
     * @param \DateTime $startDate
     * @param \DateTime @endDate
     *
     * @return ScheduledGame[]
     */
    public function findAvailSlots(\DateTime $startDate, \DateTime $endDate)
    {
        $sql = "SELECT sg
                FROM App:ScheduledGame sg
                WHERE sg.gameDate BETWEEN :startDate AND :endDate
                  AND sg.homeTeamId IS NULL
                ORDER BY sg.gameDate, sg.gameTime
               ";

        $dbData = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameters(array(
                'startDate' => $startDate->format("Y-m-d"),
                'endDate' => $endDate->format("Y-m-d"),
            ))
            ->getResult(Query::HYDRATE_OBJECT);

        return $dbData;
    }

    /**
     * Find past games, in a date range, for a specific team id
     *
     * @param int $teamId
     * @param string $startDate
     * @param string $endDate
     *
     * @return ScheduledGame[]
     */
    public function findPastGamesForTeamId(
        int $teamId,
        string $startDate,
        string $endDate
    ) {
        $sql = "SELECT sg
                FROM App:ScheduledGame sg
                WHERE sg.gameDate BETWEEN :startDate AND :endDate
                  AND (sg.homeTeamId = :teamId OR sg.visitTeamId = :teamId)
               ";
        $dbData = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameters(array(
                'startDate' => $startDate,
                'endDate' => $endDate,
                'teamId' => $teamId
            ))
            ->getResult(Query::HYDRATE_OBJECT);

        return $dbData;
    }

    /**
     * Get list of all games in schedule, possibly limited to specific team
     *
     * @param int $teamId  Defaults to 0 meaning show every teams' games
     *
     * @return ScheduledGame[]
     */
    public function listAllScheduledGames($teamId = 0)
    {
        $where = '';
        if ($teamId > 0) {
            $where = " WHERE sg.homeTeamId = :teamId OR sg.visitTeamId = :teamId ";
        }

        $sql = "SELECT sg
                FROM App:ScheduledGame sg";
        if ($teamId > 0) {
            $sql .= $where;
        }
        $sql .= " ORDER BY sg.gameDate, sg.gameTime, sg.gameLocation";

        $query = $this->getEntityManager()
            ->createQuery($sql);
        if ($teamId > 0) {
            $query->setParameters(array(
                'teamId' => $teamId,
            ));
        }
        $dbData = $query->getResult(Query::HYDRATE_OBJECT);

        return $dbData;
    }

    /**
     * Get the count of games team is either Home or Away
     *
     * @param int    $teamId
     * @param string $selector  One of HOME or AWAY
     *
     * @return int
     */
    public function getCountHomeAndAwayForTeam(int $teamId, string $selector)
    {
        if ($selector == "HOME") {
            $fieldSelector = "sg.homeTeamId";
        } elseif ($selector == "AWAY") {
            $fieldSelector = "sg.visitTeamId";
        } else {
            throw new \InvalidArgumentException(
                'Value of {selector} parameter must be HOME or AWAY: ' . print_r($selector, true),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        $sql = "SELECT COUNT(sg)
                FROM App:ScheduledGame sg
                WHERE " . $fieldSelector  . " = :teamId
                ";

        $dbData = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameters(array(
                'teamId' => $teamId
            ))
            ->getResult(Query::HYDRATE_SINGLE_SCALAR);

        return (int) $dbData;
    }

    /**
     * Delete all games on the given game date
     *
     * @param string $gameDate
     */
    public function deleteSlotsForDate(string $gameDate)
    {
        $sql = "DELETE
                FROM App:ScheduledGame sg
                WHERE sg.gameDate = :gameDate
               ";

        $this->getEntityManager()
            ->createQuery($sql)
            ->execute(array(
                'gameDate' => $gameDate
            ));
    }

    /**
     * Get a breakdown of how many games a team has played for each day of the week
     * @param int $teamId
     *
     * @return mixed
     */
    public function getDayOfWeekBreakdownForTeam(int $teamId)
    {
        $dbData = $this->listAllScheduledGames($teamId);

        $dowBreakdown = $this->dateUtilityService->createDowArray(0, 'short');
        foreach ($dbData as $game) {
            $dowBreakdown[$game->getGameDate()->format("D")]++;
        }

        return $dowBreakdown;
    }

    /**
     * Fetch list of scheduled games involving teams included in provided list
     *
     * @param array $teamIdFilter
     *
     * @return ScheduledGame[]|array
     */
    public function listScheduledGamesForFilteredTeams(array $teamIdFilter)
    {
        if (empty($teamIdFilter)) {
            return array();
        }

        $teamIdList = implode(',', $teamIdFilter);
        $where = " WHERE sg.homeTeamId IN (" . $teamIdList . ") OR sg.visitTeamId IN (" . $teamIdList . ") ";
        $where .= " OR sg.division IS NULL ";

        $sql = "SELECT sg
                FROM App:ScheduledGame sg";
        $sql .=  $where;
        $sql .= " ORDER BY sg.gameDate, sg.gameTime, sg.gameLocation";

        $dbData = $this->getEntityManager()
            ->createQuery($sql)
            ->getResult(Query::HYDRATE_OBJECT);

        return $dbData;
    }
}
