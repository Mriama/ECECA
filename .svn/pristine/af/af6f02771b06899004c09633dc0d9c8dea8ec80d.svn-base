<?php
namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\EleCampagne;

class EleCampagneRepository extends EntityRepository 
{	
	/**
	 * Retourne la dernière campagne stockée en base pour le type d'élection 
	 * passé en paramètre. Son état est soit 'en cours', soit 'archivé'
	 */
	public function getLastCampagne($typeElectionId=null)
	{	
		
		$Query = $this->createQueryBuilder('c')
				          ->where('c.typeElection = :typeElec')
				          ->orderBy('c.anneeDebut','DESC')
				          ->setParameter('typeElec',$typeElectionId)
				          ->setMaxResults(1)
				          ->getQuery();
			
		try 
		{
    		$campagne = $Query->getSingleResult();
		} 
		catch (\Doctrine\Orm\NoResultException $e) 
		{
    		$campagne = null;
		}

		return $campagne;
	}
	
	/**
	 * Retourne l'année de début de la nouvelle campagne
	 */
	public function getAnneeDebutNewCampagne($typeElectionId, $dateCourante)
	{
		if ($typeElectionId != null)
		{
			if ($dateCourante === null)
			{
				$dateCourante = new \DateTime();
			}
			
			$campagne = $this->getLastCampagne($typeElectionId);
		
			if ($campagne != null)
			{     
				if ($campagne->getArchivee())
				{
					// On récupère l'année de fin de la dernière campagne archivée pour ce type d'élection
					$anneeFinDerniereCampagne = $campagne->getAnneeFin();
					
					// On récupère la date courante
					$anneeCourante = \intval($dateCourante->format('Y'));
					
					// On teste si on peut initialiser la nouvelle campagne
					// La date courante doit être supérieure ou égale au 1er Janvier de l'année 
					// de début de la nouvelle campagne
					// Ceci interdit d'initialiser, par exemple, la campagne 2014/2015 avant le 01/01/2014
					if ($anneeCourante < $anneeFinDerniereCampagne)
					{
						$anneeDebutCampagne = -1;
					}
					else
					{
						// On prend le Max des 2 années afin d'éviter le problème d'année sans campagne (??)
						$anneeDebutCampagne = \max($anneeFinDerniereCampagne, $anneeCourante);
					}
				}
				else
				{
					// La dernière campagne n'a pas été archivée - Initialisation impossible
					$anneeDebutCampagne = 0;
				}
			}
			else
			{
				$anneeDebutCampagne = \intval($dateCourante->format('Y'));
			}
		}
		else
		{
			$anneeDebutCampagne = null;
		}

		return $anneeDebutCampagne;
	}
	
	/**
	 * @param : $typeElection : RefTypeElection : obligatoire
	 * @param : $anneeDeb : date en année : facultatif
	 * @return liste de Campagne
	 * "getCampagneParTypeElectionAnneeDebut" permet de récupérer la liste des campagne pour un type d'élection et 
	 * pour une $anneeDeb
	 */
	public function getCampagneParTypeElectionAnneeDebut($typeElection, $anneeDeb = null) {
		
		$query = $this->createQueryBuilder('c');
		$query = $query->where('c.typeElection = :typeElection');
		
		if (!empty($anneeDeb)) {
			$query = $query->andWhere('c.anneeDebut = :anneeDeb')->setParameter('anneeDeb', $anneeDeb);
		}		
		
		$query = $query->setParameter('typeElection', $typeElection);
		$query = $query->orderBy('c.anneeDebut','DESC');

		return $query->getQuery()->getResult();
	}
	
	/**
	 * @param : $typeElection : RefTypeElection : obligatoire
	 * @return liste de Campagne
	 * "getLastCampagneNonArchive" permet de récupérer la dernière campagne non archivée pour un type d'élection donné
	 */
	public function getLastCampagneNonArchive($typeElection) {
		
		$query = $this->createQueryBuilder('c')
					  ->where('c.typeElection = :typeElection')
		              ->AndWhere('c.archivee = :archive')
					  ->setParameter('typeElection', $typeElection)
					  ->setParameter('archive', 0)
					  ->orderBy('c.anneeDebut','DESC')
					  ->setMaxResults(1)
					  ->getQuery();
	
		try 
		{
    		$campagne = $query->getSingleResult();
		} 
		catch (\Doctrine\Orm\NoResultException $e) 
		{
    		$campagne = null;
		}
		
		return $campagne;
	}
	
	/**
	 * @param : $typeElection : RefTypeElection : obligatoire
	 * @return liste de Campagne
	 * "getLastCampagneArchivee" permet de récupérer la dernière campagne archivée pour un type d'élection donné
	 */
	public function getLastCampagneArchivee($typeElection) {
	
		$query = $this->createQueryBuilder('c')
		->where('c.typeElection = :typeElection')
		->andWhere('c.archivee = true')
		->setParameter('typeElection', $typeElection)
		->orderBy('c.anneeDebut','DESC')
		->setMaxResults(1)
		->getQuery();
	
		try
		{
			$campagne = $query->getSingleResult();
		}
		catch (\Doctrine\Orm\NoResultException $e)
		{
			$campagne = null;
		}
	
		return $campagne;
	}
	
	/**
	 * recuperer les campagnes de plus de X ans d'un type d'election donné
	 * 
	 */
	public function getCampagnesASupprimerParTypeElectionAnneeDebut($typeElection, $anneeDeb) {
	
		$query = $this->createQueryBuilder('c');
		$query = $query->where('c.typeElection = :typeElection');
		$query = $query->andWhere('c.anneeDebut <= :anneeDeb')->setParameter('anneeDeb', $anneeDeb);
		$query = $query->setParameter('typeElection', $typeElection);
		$query = $query->orderBy('c.anneeDebut','DESC');
	
		return $query->getQuery()->getResult();
	}
}