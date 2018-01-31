<?php

namespace App\Repository;

use App\Entity\GameToSchedule;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class GameToScheduleRepository
 * @package App\Repository
 */
class GameToScheduleRepository extends ServiceEntityRepository
{
    /**
     * GameToScheduleRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, GameToSchedule::class);
    }

    /**
     * Save a record to the database
     *
     * @param GameToSchedule $entity
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save (GameToSchedule $entity)
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
                TRUNCATE Game_To_Schedule;
                SET FOREIGN_KEY_CHECKS=1;
                ";
        $dbConn = $this->getEntityManager()->getConnection();
        $dbCmd = $dbConn->prepare($sql);
        $dbCmd->execute();
    }

    /**
     * Get the games for a specific division in the requested week
     * @param string $division
     * @param int    $weekNr
     *
     * @return GameToSchedule[]
     */
    public function findGamesForDivInWeek(string $division, int $weekNr)
    {
        $sql = "SELECT gts
                FROM App:GameToSchedule gts
                WHERE gts.division = :division AND 
                    gts.weekNr = :weekNr
               ";
        $dbData = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameters(array(
                'division' => $division,
                'weekNr' => $weekNr,
            ))
            ->getResult(Query::HYDRATE_OBJECT);

        return $dbData;
    }

    /**
     * @param int $gameToScheduleId
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function remove(int $gameToScheduleId)
    {
        $em = $this->getEntityManager();
        $record = $em->find(GameToSchedule::class, $gameToScheduleId);
        $em->remove($record);
        $em->flush();
    }
}
