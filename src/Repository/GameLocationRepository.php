<?php

namespace App\Repository;

use App\Entity\GameLocation;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

class GameLocationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, GameLocation::class);
    }

    public function save(GameLocation $entity)
    {
        $em = $this->getEntityManager();

        $em->persist($entity);
        $em->flush();
    }

    /**
     * @param string $dayOfWeek
     *
     * @return GameLocation[] | null
     */
    public function fetchGamesSlatedForDayOfWeek(string $dayOfWeek)
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
