<?php

namespace App\Repository;

use App\Entity\DefaultSchedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    /*
    public function findBySomething($value)
    {
        return $this->createQueryBuilder('d')
            ->where('d.something = :value')->setParameter('value', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
}
