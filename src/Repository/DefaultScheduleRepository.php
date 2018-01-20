<?php

namespace App\Repository;

use App\Entity\DefaultSchedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DefaultScheduleRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DefaultSchedule::class);
    }

    public function save(DefaultSchedule $entity)
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Get the length, in weeks, of a schedule, for a given division size
     *
     * @param int $divisionFormat
     *
     * @return int
     */
    public function getScheduleLengthInWeeks(int $divisionFormat)
    {
        $em = $this->getEntityManager();
        $sql = "SELECT COUNT(DISTINCT ds.weekNr) AS WeeksInSchedule
                FROM App:DefaultSchedule ds
                WHERE ds.divisionFormat = :divisionFormat
               ";
        $dbData = $em->createQuery($sql)
            ->setParameters(array(
                'divisionFormat' => $divisionFormat,
            ))
            ->getResult(Query::HYDRATE_SINGLE_SCALAR);

        return($dbData);
    }
}
