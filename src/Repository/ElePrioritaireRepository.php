<?php

namespace App\Repository;
use App\Entity\EleCampagne;
use App\Entity\ElePrioritaire;
use App\Entity\EleParticipation;
use App\Entity\RefAcademie;
use Doctrine\ORM\EntityRepository;
use App\Entity\RefTypeEtablissement;

/**
 * ElePrioritaireRepository
 */
class ElePrioritaireRepository extends EntityRepository {

    public function findListElePrioritaireParParticipation($participation){
        $qb = $this->createQueryBuilder('elePrio');
        $qb->join('elePrio.participation', 'elePart');
        $qb->leftjoin('elePrio.typePrioritaire', 'typePrio');
        $qb->select('typePrio.code as categorie, elePart.id as libelle, elePrio.id as Id, sum(elePrio.nbInscrits) as nbIns, sum(elePrio.nbVotants) as nbVotants, sum(elePrio.nbExprimes) as nbExpr');
        $qb->where('elePart.id = :participation');
        $qb->setParameter('participation',$participation);
        $qb->groupBy('typePrio.code');
        $qb->orderBy('typePrio.code', 'DESC');

        return $qb->getQuery()->getResult();
    }
}