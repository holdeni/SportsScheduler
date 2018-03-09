<?php

namespace App\Repository;

use App\Entity\GameLocation;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class GameLocationRepository
 * @package App\Repository
 */
class GameLocationRepository extends ServiceEntityRepository
{
    /**
     * GameLocationRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, GameLocation::class);
    }

    /**
     * Save record to database
     *
     * @param GameLocation $entity
     */
    public function save(GameLocation $entity)
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
                TRUNCATE Game_Location;
                SET FOREIGN_KEY_CHECKS=1;
                ";
        $dbConn = $this->getEntityManager()->getConnection();
        $dbCmd = $dbConn->prepare($sql);
        $dbCmd->execute();
    }

    /**
     * Get game locations allocated to specified day of week
     *
     * @param string $dayOfWeek
     *
     * @return GameLocation[] | null
     */
    public function fetchLocationsSlatedForDayOfWeek(string $dayOfWeek)
    {
        $em = $this->getEntityManager();
        $sql = "SELECT gl
                FROM App:GameLocation gl
                WHERE gl.dayOfWeek = :dayOfWeek
                  AND gl.action = :addAction
               ";
        $dbData = $em->createQuery($sql)
            ->setParameters(array(
                'dayOfWeek' => $dayOfWeek,
                'addAction' => 'ADD'
            ))
            ->getResult();

        return($dbData);
    }

    /**
     * @return string[]
     */
    public function fetchSkippedDates()
    {
        $sql = "SELECT gl.dayOfWeek
                FROM App:GameLocation gl
                WHERE gl.action = :deleteAction
               ";
        $dbData = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameters(array(
                'deleteAction' => 'DELETE'
            ))
            ->getResult(Query::HYDRATE_ARRAY);

        return $dbData;
    }
}
