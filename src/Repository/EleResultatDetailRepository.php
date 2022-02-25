<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

use App\Entity\EleEtablissement;
use App\Entity\EleCampagne;
use App\Entity\EleConsolidation;
use App\Entity\EleResultat;
use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Entity\RefTypeElection;
use App\Entity\RefTypeEtablissement;
use App\Utils\EpleUtils;
use App\Entity\RefUser;
use App\Entity\RefProfil;
use App\Entity\RefCommune;

/**
 * EleResultatDetailRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EleResultatDetailRepository extends EntityRepository
{
    
    /**
     * @param : $eleEtab : EleEtablissement : facultatif
     * @return liste de EleResultat
     * "findByEleEtablissementOrderByOrdre" permet de récupérer des EleResultatDetail
     * pour $eleEtab donné
     */
    public function findByEleEtablissement(EleEtablissement $eleEtab=null) {
        $qb = $this->createQueryBuilder('res')->innerjoin('res.organisation', 'o');
        if (!empty($eleEtab)) {
            $qb->where('res.electionEtab =:eleEtab')->setParameter('eleEtab', $eleEtab);
        }
        //$qb->orderBy('o.ordre', 'ASC')->addOrderBy('o.libelle', 'ASC');
        return $qb->getQuery()->getResult();
    }
    
    /**
     * @param : $uais
     * @return liste de EleResultat
     * "findByEleEtablissementsOrderByOrdre" permet de récupérer des EleResultatDetail
     * pour une liste des etablissments données
     */
    public function findByEleEtablissementsForExport($campagne,$lstElectEtab) {
       $qb = $this->createQueryBuilder('res')->innerjoin('res.organisation', 'o')
	    	  ->join('res.electionEtab', 'eleEtab');
			  //->join('eleEtab.etablissement', 'etab');
	    
	    $idEtablissement = array();
	    foreach ($lstElectEtab as $electEtab) {
	        if (!empty($electEtab)) {
	            array_push($idEtablissement, $electEtab->getId());
	        }
	    }
	    
       $qb->where('eleEtab.campagne = :campagne')->setParameter('campagne', $campagne);
       $qb->andWhere('eleEtab IN (:ids)')->setParameter('ids', $idEtablissement);
       //$qb->orderBy('o.ordre', 'ASC')->addOrderBy('o.libelle', 'ASC');
       return $qb->getQuery()->getResult();
    }
    
    public function findDatasEnCoursFromEleResultatDetailByCampagneZoneTypeEtab(EleCampagne $campagne, $zone=EpleUtils::TOUTES_ZONES, $typeEtab="tous", $etatSaisie, $refUser=null, $isEreaErpdExclus = false, $idSousTypeElection = null) {
    	return $this->queryFindDatasResultatsDetailsEnCoursByCampagneZoneTypeEtab($campagne, $zone, $typeEtab, $etatSaisie, $refUser, $isEreaErpdExclus, $idSousTypeElection)->getResult();
    }
    
    public function queryFindDatasResultatsDetailsEnCoursByCampagneZoneTypeEtab(EleCampagne $campagne, $zone, $typeEtab, $etatSaisie, $refUser = null, $isEreaErpdExclus = false, $idSousTypeElection = null)
    {
		$parameters = array ();
		// Evol 015E afficher le nombre de sieges reellement attribués
		$stringQuery = "SELECT o.id as idOrg, o.libelle as libOrg, o.ordre as ordreOrg,
						sum(r.nbVoix) as nbVoix, sum(r.nbSieges) as nbSieges, sum(r.nbSiegesSort) as nbSiegesSort, sum(CASE WHEN r.nbSieges < r.nbCandidats  THEN r.nbSieges ELSE r.nbCandidats END) as nbCandidats
						FROM EPLEElectionBundle:EleResultatDetail r
						JOIN r.organisation o
						JOIN r.electionEtab eleEtab
						JOIN eleEtab.etablissement etab ";
		
		/* Test d'abord si une commune est communiquée */
		
		if (! empty ( $zone )) {
			if ($zone instanceof RefCommune) {
				$stringQuery .= ' JOIN etab.commune comm WITH comm = :commune ';
				$parameters ['commune'] = $zone;
			} else {
				$stringQuery .= ' JOIN etab.commune comm ';
			}
			
			if ($zone instanceof RefAcademie) {
				// DSDEN multidepartements Resultat : [ECT] fonctionnellement, tous les departements appartiennent a la meme academie
				if (null != $refUser && $refUser->getProfil ()->getCode () == RefProfil::CODE_PROFIL_DSDEN) {
					$stringQuery .= ' JOIN comm.departement dept WITH dept.numero in (' . EpleUtils::getNumerosDepts ( $refUser->getPerimetre ()->getDepartements () ) . ') ';
				} else {
					$stringQuery .= ' JOIN comm.departement dept WITH dept.academie = :academie ';
					$parameters ['academie'] = $zone;
				}
			}
			if ($zone instanceof RefDepartement) {
				$stringQuery .= ' JOIN comm.departement dept WITH dept = :departement ';
				$parameters ['departement'] = $zone;
			}
		}
		
		$stringQuery .= " JOIN eleEtab.campagne camp WITH camp.id = :campagneId ";
		$parameters ['campagneId'] = $campagne->getId ();
		
		if ($typeEtab instanceof RefTypeEtablissement) {
			// Correction filtre de recherche des resultats
			if (RefTypeEtablissement::ID_TYP_2ND_DEGRE == $typeEtab->getId ()) {
				$stringQuery .= " JOIN etab.typeEtablissement te WITH te.degre = 2 ";
			} else {
				$stringQuery .= " JOIN etab.typeEtablissement te WITH te = :typeEtab_sent ";
				$parameters ['typeEtab_sent'] = $typeEtab;
			}
		} else if ($typeEtab === RefTypeEtablissement::CODE_URL_2ND_DEGRE) {
			$stringQuery .= " JOIN etab.typeEtablissement te WITH te.degre = 2 ";
		} else {
			$stringQuery .= " JOIN etab.typeEtablissement te ";
		}
		
		$stringQuery .= " WHERE 1=1 ";
		
		// Evol 013E RG_STATG_15
		if ($isEreaErpdExclus) {
			$stringQuery .= " AND te.id <> '" . RefTypeEtablissement::ID_TYP_EREA_ERPD . "'"; // YME 0145664
		}
		
		$stringQuery .= " AND eleEtab.validation IN (:etatSaisie) ";
		
		// Stats generales : CE/DE/IEN limiter aux etabs du perimetre
		if (null != $refUser && ($refUser->getProfil ()->getCode () == RefProfil::CODE_PROFIL_IEN || $refUser->getProfil ()->getCode () == RefProfil::CODE_PROFIL_CE || $refUser->getProfil ()->getCode () == RefProfil::CODE_PROFIL_DE)) {
			$stringQuery .= " AND etab.uai IN (" . EpleUtils::getUais ( $refUser->getPerimetre ()->getEtablissements () ) . ") ";
		}
		
		$parameters ['etatSaisie'] = $etatSaisie;
		
		if (null != $idSousTypeElection) {
			$stringQuery .= " AND eleEtab.sousTypeElection = " . $idSousTypeElection;
		}
		
		$stringQuery .= " GROUP BY o.id ORDER BY o.ordre ASC, o.libelle ASC";
		$query = $this->_em->createQuery ( $stringQuery )->setParameters ( $parameters );


		return $query;
	}
	
	public function findDatasGlobauxFromEleResultatDetailByCampagneZoneTypeEtab(EleCampagne $campagne, $zone=EpleUtils::TOUTES_ZONES, $typeEtab="tous", $refUser = null) {
        return $this->queryFindDatasResultatsDetailGlobauxByCampagneZoneTypeEtab($campagne, $zone, $typeEtab, $refUser)->getResult();
    }
	
	public function queryFindDatasResultatsDetailGlobauxByCampagneZoneTypeEtab(EleCampagne $campagne, $zone=EPLEUtils::TOUTES_ZONES, $typeEtab="tous", $refUser = null) {
		// Evol 015E afficher le nombre de sieges reellement attribués
		$stringQuery = "SELECT o.id as idOrg, o.libelle as libOrg, o.ordre as ordreOrg,
						sum(r.nbVoix) as nbVoix, sum(r.nbSieges) as nbSieges, sum(r.nbSiegesSort) as nbSiegesSort, sum(CASE WHEN r.nbSieges < r.nbCandidats  THEN r.nbSieges ELSE r.nbCandidats END) as nbCandidats
						FROM EPLEElectionBundle:EleResultatDetail r
						JOIN r.organisation o
						JOIN r.electionEtab eleEtab
						JOIN eleEtab.etablissement etab ";

		/* if (($zone instanceof RefAcademie) or ($zone instanceof RefDepartement)) {
			$stringQuery .= " WITH (cons.idZone = :zoneId) ";

			if ($zone instanceof RefAcademie) {
				$fct_getIdsDept = function ($dept) {
					return $dept->getIdZone();
				};

				$zonesDept = array_map($fct_getIdsDept, $zone->getDepartements()->toArray());
				$str_zoneIds = implode(" ', '", $zonesDept);

				$stringQuery .= " OR (cons.idZone in ( '" . $str_zoneIds . "' )) ";
			}
		}*/

        if (! empty ( $zone )) {
            if ($zone instanceof RefCommune) {
                $stringQuery .= ' JOIN etab.commune comm WITH comm = :commune ';
                $parameters ['commune'] = $zone;
            } else {
                $stringQuery .= ' JOIN etab.commune comm ';
            }

            if ($zone instanceof RefAcademie) {
                // DSDEN multidepartements Resultat : [ECT] fonctionnellement, tous les departements appartiennent a la meme academie
                if (null != $refUser && $refUser->getProfil ()->getCode () == RefProfil::CODE_PROFIL_DSDEN) {
                    $stringQuery .= ' JOIN comm.departement dept WITH dept.numero in (' . EpleUtils::getNumerosDepts ( $refUser->getPerimetre ()->getDepartements () ) . ') ';
                } else {
                    $stringQuery .= ' JOIN comm.departement dept WITH dept.academie = :academie ';
                    $parameters ['academie'] = $zone;
                }
            }
            if ($zone instanceof RefDepartement) {
                $stringQuery .= ' JOIN comm.departement dept WITH dept = :departement ';
                $parameters ['departement'] = $zone;
            }
        }


        $stringQuery .= " JOIN eleEtab.campagne camp WITH camp.id = :campagneId ";
        $parameters ['campagneId'] = $campagne->getId ();

		/* if ($typeEtab instanceof RefTypeEtablissement) {
			// Correction filtre de recherche des resultats
			if(RefTypeEtablissement::ID_TYP_2ND_DEGRE == $typeEtab->getId()) {
				$stringQuery .=" JOIN cons.typeEtablissement te WITH te.degre = 2 ";
			} else {
				$stringQuery .=" JOIN cons.typeEtablissement te WITH te = :typeEtab_sent ";
			}
		} else if ($typeEtab === RefTypeEtablissement::CODE_URL_2ND_DEGRE) {
			$stringQuery .=" JOIN cons.typeEtablissement te WITH te.degre = 2 ";
		} else {
			if ($perimetre != null && $perimetre->getDegres() != null) {
				$stringQuery .=" JOIN cons.typeEtablissement te WITH te.degre in (:degres) ";
			}
		} */

        if ($typeEtab instanceof RefTypeEtablissement) {
            // Correction filtre de recherche des resultats
            if (RefTypeEtablissement::ID_TYP_2ND_DEGRE == $typeEtab->getId ()) {
                $stringQuery .= " JOIN etab.typeEtablissement te WITH te.degre = 2 ";
            } else {
                $stringQuery .= " JOIN etab.typeEtablissement te WITH te = :typeEtab_sent ";
                $parameters ['typeEtab_sent'] = $typeEtab;
            }
        } else if ($typeEtab === RefTypeEtablissement::CODE_URL_2ND_DEGRE) {
            $stringQuery .= " JOIN etab.typeEtablissement te WITH te.degre = 2 ";
        }

        // where
        $stringQuery .= " WHERE 1=1 ";

        $stringQuery .= " AND eleEtab.validation ='V' ";
		$stringQuery .= " GROUP BY o.id
						  ORDER BY o.ordre ASC, o.libelle ASC";

		/* if (($zone instanceof RefAcademie) or ($zone instanceof RefDepartement)) { $query->setParameter('zoneId', $zone->getIdZone()); }
		// Correction filtre de recherche des résultats
		if ($typeEtab instanceof RefTypeEtablissement && RefTypeEtablissement::ID_TYP_2ND_DEGRE != $typeEtab->getId()) {
			$query->setParameter('typeEtab_sent', $typeEtab);
		} else {
			if ($perimetre != null && $perimetre->getDegres() != null) {
				$query->setParameter('degres', $perimetre->getDegres());
			}
		}*/
        $query = $this->_em->createQuery ( $stringQuery )->setParameters ( $parameters );
		return $query;
	}
}
