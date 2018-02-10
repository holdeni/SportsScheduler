<?php

namespace App\Repository;

use App\Entity\ScheduledGame;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class ScheduledGameRepository
 * @package App\Repository
 */
class ScheduledGameRepository extends ServiceEntityRepository
{
    /**
     * ScheduledGameRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ScheduledGame::class);
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
     * @return ScheduledGame[]
     */
    public function listAllScheduledGame()
    {
        $sql = "SELECT sg
                FROM App:ScheduledGame sg
                ORDER BY sg.gameDate, sg.gameLocation, sg.gameTime
               ";
        $dbData = $this->getEntityManager()
            ->createQuery($sql)
            ->getResult(Query::HYDRATE_OBJECT);

        return $dbData;
    }
}