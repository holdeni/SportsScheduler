<?php

namespace App\Repository;

use App\Entity\DefaultSchedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class DefaultScheduleRepository
 * @package App\Repository
 */
class DefaultScheduleRepository extends ServiceEntityRepository
{
    /**
     * DefaultScheduleRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DefaultSchedule::class);
    }

    /**
     * Save record to the database
     *
     * @param DefaultSchedule $entity
     */
    public function save(DefaultSchedule $entity)
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Delete all records matching specified division format
     *
     * @param int $divFormat
     */
    public function delete(int $divFormat)
    {
        $sql = "DELETE
                FROM App:DefaultSchedule ds
                WHERE ds.divisionFormat = :divFormat
               ";
        $this->getEntityManager()
            ->createQuery($sql)
            ->execute(array(
                'divFormat' => $divFormat,
            ));
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

    /**
     * @param int $divisionFormat
     *
     * @return DefaultSchedule[]
     */
    public function getDefaultSchedule(int $divisionFormat)
    {
        $em = $this->getEntityManager();
        $sql = "SELECT ds
                FROM App:DefaultSchedule ds
                WHERE ds.divisionFormat = :divisionFormat
                ";
        $dbData = $em->createQuery($sql)
            ->setParameters(array(
                'divisionFormat' => $divisionFormat,
            ))
            ->getResult(Query::HYDRATE_OBJECT);

        return $dbData;
    }
}
