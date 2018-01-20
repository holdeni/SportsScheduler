<?php

namespace App\Repository;

use App\Entity\TeamInformation;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class TeamInformationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TeamInformation::class);
    }

    public function save(TeamInformation $entity)
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Get the count of teams in divisions
     *
     * @return array
     */
    public function getNrOfTeamsInDiv()
    {
        $em = $this->getEntityManager();
        $sql = "SELECT ti.teamDivision, COUNT(ti.teamDivision) as NrTeamsInDiv
                FROM App:TeamInformation ti
                GROUP BY ti.teamDivision
               ";
        $dbData = $em->createQuery($sql)
            ->getArrayResult();

        return($dbData);
    }

}
