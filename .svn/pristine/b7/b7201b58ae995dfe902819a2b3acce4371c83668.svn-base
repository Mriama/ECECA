<?php
namespace App\Utils;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\EleParticipation;
use App\Entity\ElePrioritaire;
use App\Entity\EleConsolidation;
use App\Entity\EleResultat;
use App\Entity\RefTypePrioritaire;
use App\Entity\RefProfil;
use App\Entity\RefTypeElection;
use App\Entity\RefTypeEtablissement;

/**
 * Classe de consolidation des résultats
 * 
 * @author a176206
 *        
 */
class ConsolidationService
{

    private $em; // EntityManager
    public function __construct($doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    /**
     *
     *
     * Consolidation des résultats d'un département
     * Par ajout des résultats d'un établissement
     * Appelé après validation des résultats
     *
     * @param unknown $eleEtab  
     * @param boolean $doFlush       
     */
    public function consolidationEleEtab($eleEtab, $doFlush = true)
    {
        // Identification du type d'établissement
        $typeEtablissement = $eleEtab->getEtablissement()->getTypeEtablissement();
        
        // Identification de la zone
        $idZone = $eleEtab->getEtablissement()
            ->getCommune()
            ->getDepartement()
            ->getNumero();
        
        // Identification de la campagne
        $campagne = $eleEtab->getCampagne();
        
        // Identification du type Prioritaire
        $typePrioritaire = $eleEtab->getEtablissement()->getTypePrioritaire();
        if (null == $typePrioritaire) {
            $typePrioritaire = $this->em->getRepository('EPLEElectionBundle:RefTypePrioritaire')->findOneBy(array(
                'code' => RefTypePrioritaire::REF_TYPE_PRIORITAIRE_AUTRE
            ));
        }
        
        // Recherche dans la table ele_consolidation avec comme paramètres le type d’établissement et l’id de campagne
        $eleConsolidation = $this->em->getRepository('EPLEElectionBundle:EleConsolidation')->findOneBy(array(
            'campagne' => $campagne->getId(),
            'typeEtablissement' => $typeEtablissement->getId(),
            'idZone' => $idZone
        ));
        
        // Si aucune consolidation n’est retournée, il s’agit d’une création de consolidation (et donc pas de cumul de participation au niveau de la table ele_participation)
        if (null == $eleConsolidation) {
            
            $zone = EpleUtils::getZone($this->em, $idZone);
            
            // Création d’une ligne dans la table ele_participation et écriture des données de participation de l’établissement
            $participation = new EleParticipation();
            $participation->setNbInscrits($eleEtab->getParticipation()->getNbInscrits());
            $participation->setNbVotants($eleEtab->getParticipation()->getNbVotants());
            $participation->setNbNulsBlancs($eleEtab->getParticipation()->getNbNulsBlancs());
            $participation->setNbSiegesPourvoir($eleEtab->getParticipation()->getNbSiegesPourvoir());
            $participation->setNbSiegesPourvus($eleEtab->getParticipation()->getNbSiegesPourvus());
            
            // Création d’une ligne dans la table ele_consolidation et écriture des données de participation de l’établissement, avec l’id_participation rattaché, et la zone correspondant au département/académie.
            $eleConsolidation = new EleConsolidation();
            $eleConsolidation->setCampagne($campagne);
            $eleConsolidation->setParticipation($participation);
            $eleConsolidation->setTypeEtablissement($typeEtablissement);
            $eleConsolidation->setIdZone($idZone);
            
            $nbEtabTotal = $this->em->getRepository('EPLEElectionBundle:RefEtablissement')->getNbEtabParTypeEtablissementZoneTypeElection(null, $zone, $campagne->getTypeElection());
            //correction du nb_total_etab qui prend pas en compte 
            //$nbEtabTotal = $this->em->getRepository('EPLEElectionBundle:RefEtablissement')->getNbEtabParTypeEtablissementZoneTypeElection($typeEtablissement, $zone, $campagne->getTypeElection());
            // Un seul etablissement exprimé pour le moment
            $nbEtabExprimes = 1;
            
            $eleConsolidation->setNbEtabExprimes($nbEtabExprimes);
            $eleConsolidation->setNbEtabTotal($nbEtabTotal);
            
            $this->em->persist($eleConsolidation);
            
            // L'entité n'existe pas en base -> obligation de la créer à chaque fois.
            $this->em->flush();
            
            // Création d’une ligne dans la table ele_prioritaire et écriture des données de participation de l’établissement, avec l’id_participation et le type_prioritaire rattaché
            $elePrioritaire = new ElePrioritaire();
            
            $elePrioritaire->setTypePrioritaire($typePrioritaire);
            $elePrioritaire->setParticipation($participation);
            $elePrioritaire->setNbInscrits($eleEtab->getParticipation()->getNbInscrits());
            $elePrioritaire->setNbVotants($eleEtab->getParticipation()->getNbVotants());
            $elePrioritaire->setNbNulsBlancs($eleEtab->getParticipation()->getNbNulsBlancs());
            $this->em->persist($elePrioritaire);

        } else {
            
            // Mise à jour de la table ele_participation avec l’id_participation et ajout de la participation de l’établissement
            $participation = $this->em->getRepository('EPLEElectionBundle:EleParticipation')->find($eleConsolidation->getParticipation()->getId());
            $nbInscrits = intval($participation->getNbInscrits()) + intval($eleEtab->getParticipation()->getNbInscrits());
            $nbVotants = intval($participation->getNbVotants()) + intval($eleEtab->getParticipation()->getNbVotants());
            $nbNulsBlancs = intval($participation->getNbNulsBlancs()) + intval($eleEtab->getParticipation()->getNbNulsBlancs());
            $nbSiegesPourvoir = intval($participation->getNbSiegesPourvoir()) + intval($eleEtab->getParticipation()->getNbSiegesPourvoir());
            $nbSiegesPourvus = intval($participation->getNbSiegesPourvus()) + intval($eleEtab->getParticipation()->getNbSiegesPourvus());
            
            $participation->setNbInscrits($nbInscrits);
            $participation->setNbVotants($nbVotants);
            $participation->setNbNulsBlancs($nbNulsBlancs);
            $participation->setNbSiegesPourvoir($nbSiegesPourvoir);
            $participation->setNbSiegesPourvus($nbSiegesPourvus);

            
            // Mise à jour de la table ele_consolidation par incrémentation du nombre d'établissements exprimés
            $nbEtabExprimes = intval($eleConsolidation->getNbEtabExprimes()) + 1;
            $eleConsolidation->setNbEtabExprimes($nbEtabExprimes);
            
            $this->em->persist($eleConsolidation);
            
            // Mise à jour de la table ele_prioritaire : écriture des données de participation de l’établissement, avec l’id_participation et le type_prioritaire rattaché
            $elePrioritaire = $this->em->getRepository('EPLEElectionBundle:ElePrioritaire')->findOneBy(array(
                'typePrioritaire' => $typePrioritaire->getId(),
                'participation' => $eleConsolidation->getParticipation()
                    ->getId()
            ));
            
            if (null == $elePrioritaire) {
                // La ligne n'a pas été trouvée en base => l'établissement à rajouter est d'un nouveau type d'education prioritaire
                $elePrioritaire = new ElePrioritaire();
                $elePrioritaire->setTypePrioritaire($typePrioritaire);
                $elePrioritaire->setParticipation($participation);
                $nbInscrits = $eleEtab->getParticipation()->getNbInscrits();
                $nbVotants = $eleEtab->getParticipation()->getNbVotants();
                $nbNulsBlancs = $eleEtab->getParticipation()->getNbNulsBlancs();
            } else {
                $nbInscrits = intval($elePrioritaire->getNbInscrits()) + intval($eleEtab->getParticipation()->getNbInscrits());
                $nbVotants = intval($elePrioritaire->getNbVotants()) + intval($eleEtab->getParticipation()->getNbVotants());
                $nbNulsBlancs = intval($elePrioritaire->getNbNulsBlancs()) + intval($eleEtab->getParticipation()->getNbNulsBlancs());
            }
            
            $elePrioritaire->setNbInscrits($nbInscrits);
            $elePrioritaire->setNbVotants($nbVotants);
            $elePrioritaire->setNbNulsBlancs($nbNulsBlancs);
            $this->em->persist($elePrioritaire);
        }
        
        $this->em->flush();
        
        // Insertion des résultats de consolidation dans la table ele_resultat
        
        // Recherche de la consolidation précédente
        $liste_resultats = $this->em->getRepository('EPLEElectionBundle:EleResultat')->findBy(array('consolidation' => $eleConsolidation->getId()));
        if (empty($liste_resultats)) {
            
            // Insertion des résultats de l'établissement
            foreach ($eleEtab->getResultats() as $eleResultat) {
                
                $eleResultatConsolidation = new EleResultat();
                $eleResultatConsolidation->setConsolidation($eleConsolidation);
                $eleResultatConsolidation->setOrganisation($eleResultat->getOrganisation());
                $eleResultatConsolidation->setNbVoix($eleResultat->getNbVoix());
                $eleResultatConsolidation->setNbSieges($eleResultat->getNbSieges());
                $eleResultatConsolidation->setNbSiegesSort($eleResultat->getNbSiegesSort());
                
                $this->em->persist($eleResultatConsolidation);
            }

        } else {
            // Mise à jour des résultats de la consolidation
            foreach ($liste_resultats as $eleResultatConsolidation) {
                
                $nbVoix = intval($eleResultatConsolidation->getNbVoix());
                $nbSieges = intval($eleResultatConsolidation->getNbSieges());
                $nbSiegesSort = intval($eleResultatConsolidation->getNbSiegesSort());
                
                foreach ($eleEtab->getResultats() as $eleResultat) {
                    
                    // Recherche de correspondances
                    if ($eleResultatConsolidation->getOrganisation()->getId() == $eleResultat->getOrganisation()->getId()) {
                        // Mise à jour des attributs
                        
                        $eleResultatConsolidation->setNbVoix($nbVoix + intval($eleResultat->getNbVoix()));
                        $eleResultatConsolidation->setNbSieges($nbSieges + intval($eleResultat->getNbSieges()));
                        $eleResultatConsolidation->setNbSiegesSort($nbSiegesSort + intval($eleResultat->getNbSiegesSort()));
                        break;
                    }
                    $this->em->persist($eleResultatConsolidation);
                }
            }
        }
        if($doFlush){
        	$this->em->flush();
        }
    }
    
    /**
     * 
     * Consolidation en masse des résultats des établissements
     * 
     * 
     * @param unknown $listEleEtabs
     */
    public function massConsolidationEleEtab($listEleEtabs){
    	
    	$batchsize = 20;
    	$i = 0;    	
    	foreach($listEleEtabs as $eleEtab){
    		if(($i % $batchsize) == 0){
    			// On enclenche le flush
    			$this->consolidationEleEtab($eleEtab, true);
    		}else{
    			// Pas de flush
    			$this->consolidationEleEtab($eleEtab, false);
    		}
    	}
    	
    }

    /**
     *
     *
     * Déconsolidation des résultats d'un département
     * Appelé après la dévalidation des résultats
     *
     * @param unknown $eleEtab            
     * @param unknown $campagne            
     * @param unknown $typeElection            
     */
    public function deconsolidationEleEtab($eleEtab, $campagne)
    {
        $boolean= true;
        // Identification du type d'établissement
        $typeEtablissement = $eleEtab->getEtablissement()->getTypeEtablissement();
        
        $idZone = $eleEtab->getEtablissement()
            ->getCommune()
            ->getDepartement()
            ->getNumero();
        
        // Identification du type Prioritaire
        $typePrioritaire = $eleEtab->getEtablissement()->getTypePrioritaire();
        if (null == $typePrioritaire) {
            $typePrioritaire = $this->em->getRepository('EPLEElectionBundle:RefTypePrioritaire')->findOneBy(array(
                'code' => RefTypePrioritaire::REF_TYPE_PRIORITAIRE_AUTRE
            ));
        }
        
        // Recherche dans la table ele_consolidation avec comme paramètres le type d’établissement et l’id de campagne
        $eleConsolidation = $this->em->getRepository('EPLEElectionBundle:EleConsolidation')->findOneBy(array(
            'campagne' => $campagne->getId(),
            'typeEtablissement' => $typeEtablissement->getId(),
            'idZone' => $idZone
        ));
        // mantis 0146481 Dévalider les établissements
        if($eleConsolidation != null) {
            if($boolean){
                // Mise à jour de la table ele_participation avec l’id_participation et ajout de la participation de l’établissement
                $participation = $this->em->getRepository('EPLEElectionBundle:EleParticipation')->find($eleConsolidation->getParticipation()->getId());
                if($participation != null) {
                    $nbInscrits = intval($participation->getNbInscrits()) - intval($eleEtab->getParticipation()->getNbInscrits());
                    $nbVotants = intval($participation->getNbVotants()) - intval($eleEtab->getParticipation()->getNbVotants());
                    $nbNulsBlancs = intval($participation->getNbNulsBlancs()) - intval($eleEtab->getParticipation()->getNbNulsBlancs());
                    $nbSiegesPourvoir = intval($participation->getNbSiegesPourvoir()) - intval($eleEtab->getParticipation()->getNbSiegesPourvoir());
                    $nbSiegesPourvus = intval($participation->getNbSiegesPourvus()) - intval($eleEtab->getParticipation()->getNbSiegesPourvus());
                    
                    $participation->setNbInscrits($nbInscrits);
                    $participation->setNbVotants($nbVotants);
                    $participation->setNbNulsBlancs($nbNulsBlancs);
                    $participation->setNbSiegesPourvoir($nbSiegesPourvoir);
                    $participation->setNbSiegesPourvus($nbSiegesPourvus);
                    $this->em->persist($participation);
                }
                // $this->em->flush();
            
                // Mise à jour de la table ele_consolidation par incrémentation du nombre d'établissements exprimés
                $nbEtabExprimes = intval($eleConsolidation->getNbEtabExprimes()) - 1;
                $eleConsolidation->setNbEtabExprimes($nbEtabExprimes);
            
                $this->em->persist($eleConsolidation);
                // $this->em->flush();
            
                // Mise à jour de la table ele_prioritaire : écriture des données de participation de l’établissement, avec l’id_participation et le type_prioritaire rattaché
                $elePrioritaire = $this->em->getRepository('EPLEElectionBundle:ElePrioritaire')->findOneBy(array(
                    'typePrioritaire' => $typePrioritaire->getId(),
                    'participation' => $eleConsolidation->getParticipation()
                    ->getId()
                ));
            
                if (null == $elePrioritaire) {
                    // La ligne n'a pas été trouvée en base => l'établissement à rajouter est d'un nouveau type d'education prioritaire
                    $elePrioritaire = new ElePrioritaire();
                    $elePrioritaire->setTypePrioritaire($typePrioritaire);
                    $elePrioritaire->setParticipation($participation);
                    $nbInscrits = $eleEtab->getParticipation()->getNbInscrits();
                    $nbVotants = $eleEtab->getParticipation()->getNbVotants();
                    $nbNulsBlancs = $eleEtab->getParticipation()->getNbNulsBlancs();
                } else {
                    $nbInscrits = intval($elePrioritaire->getNbInscrits()) - intval($eleEtab->getParticipation()->getNbInscrits());
                    $nbVotants = intval($elePrioritaire->getNbVotants()) - intval($eleEtab->getParticipation()->getNbVotants());
                    $nbNulsBlancs = intval($elePrioritaire->getNbNulsBlancs()) - intval($eleEtab->getParticipation()->getNbNulsBlancs());
                }
            
                $elePrioritaire->setNbInscrits($nbInscrits);
                $elePrioritaire->setNbVotants($nbVotants);
                $elePrioritaire->setNbNulsBlancs($nbNulsBlancs);
                $this->em->persist($elePrioritaire);
                // $this->em->flush();
            }
            $this->em->flush();
            
            // Insertion des résultats de consolidation dans la table ele_resultat
            
            // Recherche de la consolidation précédente
            $liste_resultats = $this->em->getRepository('EPLEElectionBundle:EleResultat')->findBy(array(
                'consolidation' => $eleConsolidation->getId()
            ));
            if (empty($liste_resultats)) {
            
                // Insertion des résultats de l'établissement
                foreach ($eleEtab->getResultats() as $eleResultat) {
            
                    $eleResultatConsolidation = new EleResultat();
                    $eleResultatConsolidation->setConsolidation($eleConsolidation);
                    $eleResultatConsolidation->setOrganisation($eleResultat->getOrganisation());
                    $eleResultatConsolidation->setNbVoix($eleResultat->getNbVoix());
                    $eleResultatConsolidation->setNbSieges($eleResultat->getNbSieges());
                    $eleResultatConsolidation->setNbSiegesSort($eleResultat->getNbSiegesSort());
            
                    $this->em->persist($eleResultatConsolidation);
                }
                // $this->em->flush();
            } else {
                // Mise à jour des résultats de la consolidation
                foreach ($liste_resultats as $eleResultatConsolidation) {
            
                    $nbVoix = intval($eleResultatConsolidation->getNbVoix());
                    $nbSieges = intval($eleResultatConsolidation->getNbSieges());
                    $nbSiegesSort = intval($eleResultatConsolidation->getNbSiegesSort());
            
                    foreach ($eleEtab->getResultats() as $eleResultat) {
            
                        // Recherche de correspondances
                        if ($eleResultatConsolidation->getOrganisation()->getId() == $eleResultat->getOrganisation()->getId()) {
                            // Mise à jour des attributs
            
                            $eleResultatConsolidation->setNbVoix($nbVoix - intval($eleResultat->getNbVoix()));
                            $eleResultatConsolidation->setNbSieges($nbSieges - intval($eleResultat->getNbSieges()));
                            $eleResultatConsolidation->setNbSiegesSort($nbSiegesSort - intval($eleResultat->getNbSiegesSort()));
                            break;
                        }
                        $this->em->persist($eleResultatConsolidation);
                    }
                }
            }
        }
    }
}