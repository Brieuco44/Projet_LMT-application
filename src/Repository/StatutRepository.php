<?php

namespace App\Repository;

use App\Entity\Statut;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Statut>
 */
class StatutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Statut::class);
    }

    /**
     * @return Statut
     */
    public function getStatutConforme(): Statut
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.libelle = :val')
            ->setParameter('val', 'Conforme')
            ->getQuery()
            ->getOneOrNullResult() 
        ;
    }

    /**
     * @return Statut
     */
    public function getStatutNonConforme(): Statut
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.libelle = :val')
            ->setParameter('val', 'Non Conforme')
            ->getQuery()
            ->getOneOrNullResult() 
        ;
    }

    /**
     * @return Statut
     */
    public function getStatutReverifier(): Statut
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.libelle = :val')
            ->setParameter('val', 'à revérifier')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return Statut
     */
    public function getStatutPbParametre(): Statut
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.libelle = :val')
            ->setParameter('val', 'Problème de paramétrage ')
            ->getQuery()
            ->getOneOrNullResult() 
        ;
    }

    /**
     * @return Statut
     */
    public function getStatutChampsInexistant(): Statut
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.libelle = :val')
            ->setParameter('val', 'Champ inexistant')
            ->getQuery()
            ->getOneOrNullResult() 
        ;
    }

        /**
     * @return Statut
     */
    public function getStatutIdentifiantIntrouvable(): Statut
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.libelle = :val')
            ->setParameter('val', 'Identifiant introuvable')
            ->getQuery()
            ->getOneOrNullResult() 
        ;
    }

//    /**
//     * @return Statut[] Returns an array of Statut objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Statut
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
