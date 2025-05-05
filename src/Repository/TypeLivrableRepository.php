<?php

namespace App\Repository;

use App\Entity\TypeLivrable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeLivrable>
 */
class TypeLivrableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeLivrable::class);
    }


    /**
     * Retourne <Zone, Champ, DonneeERP> de l'identifiant pour un TypeLivrable donné
     * @param int $idTypeLivrable
     * @return array|null 
     */
    public function findIdentifiant(int $idTypeLivrable): ?array
    {
        return $this->createQueryBuilder('t')
        ->select('z.libelle as Zone, c.nom as Champ, c.donneeERP as DonneeERP')
            ->join('t.zones', 'z')
            ->join('z.champs', 'c')
            ->join('c.typeChamps', 'tc')
            ->andWhere('t.id = :idTL')
            ->andWhere('tc.id = :idTC')
            ->setParameter('idTL', $idTypeLivrable)
            ->setParameter('idTC', 6) // TypeChamps::IDENTIFIANT
            ->orderBy('t.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult() 
        ;
    }

    /**
     * Retourne si un TypeLivrable donné a un identifiant
     * @param int $idTypeLivrable
     * @return bool 
     */
    public function hasIdentifiant(int $idTypeLivrable): bool
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(c.id) as nb')
            ->join('t.zones', 'z')
            ->join('z.champs', 'c')
            ->join('c.typeChamps', 'tc')
            ->andWhere('t.id = :idTL')
            ->andWhere('tc.id = :idTC')
            ->setParameter('idTL', $idTypeLivrable)
            ->setParameter('idTC', 6) // TypeChamps::IDENTIFIANT
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }

    //    public function findOneBySomeField($value): ?TypeLivrable
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
