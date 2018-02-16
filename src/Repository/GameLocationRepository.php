<?php

namespace App\Repository;

use App\Entity\GameLocation;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
               ";
        $dbData = $em->createQuery($sql)
            ->setParameters(array(
                'dayOfWeek' => $dayOfWeek,
            ))
            ->getResult();

        return($dbData);
    }
}
