<?php

namespace App\Repository;

use App\Entity\EleParticipation;
use App\Entity\EleCampagne;

use Doctrine\ORM\EntityRepository;

/**
 * EleParticipationRepository
 */
class EleParticipationRepository extends EntityRepository {

    /**
     *
     * Mise à jour du nombre de tirage au sort
     *
     * @param $nbSiegesSort
     */
    public function updateNbSiegesSort($idElePart, $nbSiegesSort){
        $qb = $this->createQueryBuilder('eleParticipation');
        $qb->update()
            ->set('eleParticipation.nbSiegesSort', '?1')
            ->where('eleParticipation.id = :id')
            ->setParameter(1, $nbSiegesSort)
            ->setParameter('id', $idElePart);

        $qb->getQuery()->execute();
    }
}