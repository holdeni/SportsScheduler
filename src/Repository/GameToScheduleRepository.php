<?php

namespace App\Repository;

use App\Entity\GameToSchedule;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

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
     *
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
}
