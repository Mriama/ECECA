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
	 * @param : $campagne : obligatoire : EleCampagne
	 * Permet de valider les résultats des participations aux élections pour une campagne donnée
	 * @deprecated : no validation flag in participation anymore
	*/
	public function valideEleParticipationCampagne(EleCampagne $campagne) 
	{
		$id_campagne = $campagne->getId();
		
		// Récupération des id des participations de chaque consolidation liée à la campagne
		$query_conso = $this->_em->createQueryBuilder();
		$query_conso->add('select', 'p.id')
		            ->add('from', 'App\Entity\EleConsolidation c')
		            ->join('c.participation', 'p')
		            ->add('where', 'c.campagne = :campagne_id')
		            ->setParameter('campagne_id',$id_campagne);
		
		$l_participationConsoCampagne = $query_conso->getQuery()->getResult();
	
		// Récupération des id des participations de chaque résultat lié à la campagne
		$query_result = $this->_em->createQueryBuilder();
		$query_result->add('select', 'p.id')
		             ->add('from', 'App\Entity\EleEtablissement e')
		             ->join('e.participation', 'p')
		             ->add('where', 'e.campagne = :campagne_id')
		             ->setParameter('campagne_id',$id_campagne);
		
		$l_participationResultCampagne = $query_result->getQuery()->getResult();
	
		// Constitution du tableau des id des participations liées à la campagne
		$l_idParticipation = array();
		foreach($l_participationConsoCampagne as $id) 
		{
			$l_idParticipation[] = \intval($id['id']);
		}
		
		foreach($l_participationResultCampagne as $id)
		{
			$l_idParticipation[] = \intval($id['id']);
		}
	
		if (count($l_idParticipation) != 0)
		{
			$str_participationIds = implode(",", $l_idParticipation);
		
			$qb = $this->createQueryBuilder('eleParticipation');
			$qb->update()
			   ->set('eleParticipation.validation', 1)
			   ->where('eleParticipation.id in ('.$str_participationIds.')');
			
			$qb->getQuery()->execute();
		}
	}
	
	
    /**
     * 
     * Mise à jour du nombre de tirage au sort
     * 
     * @param unknown $nbSiegesSort
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