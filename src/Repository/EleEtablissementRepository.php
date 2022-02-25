<?php
namespace App\Repository;

use App\Entity\EleEtablissement;
use App\Entity\EleConsolidation;
use App\Entity\EleCampagne;
use App\Entity\EleParticipation;
use App\Entity\EleResultat;
use App\Entity\EleResultatDetail;
use App\Entity\RefAcademie;
use App\Entity\RefCommune;
use App\Entity\RefDepartement;
use App\Entity\RefModaliteVote;
use App\Entity\RefTypeEtablissement;
use App\Entity\RefEtablissement;
use App\Entity\RefUser;
use App\Entity\RefProfil;

use App\Entity\ElePrioritaire;
use Doctrine\ORM\EntityRepository;

use App\Controller\StatistiqueController;

use App\Utils\EpleUtils;
use App\Entity\RefSousTypeElection;
use App\Entity\RefTypeElection;
use App\Entity\RefAcademieFusion;
use DateTime;
use Symfony\Component\Validator\Constraints\Date;

/**
 * EleEtablissementRepository
 */
class EleEtablissementRepository extends EntityRepository
{

    // TODO TEST YME A SUPPRIMER
    public function findByCampagneAndZone(EleCampagne $campagne, RefDepartement $zone){
        $qb = $this->createQueryBuilder('eleEtab');
        $qb->join('eleEtab.etablissement', 'etab');
        $qb->join('etab.commune', 'comm');
        $qb->join('comm.departement', 'dep', 'WITH', 'dep =:zone')
            ->setParameter('zone', $zone);
        $qb->join('eleEtab.campagne', 'c', 'WITH', 'c =:campagne')
            ->setParameter('campagne', $campagne);

        return $qb->getQuery()->getResult();
    }

    /**
     *
     * @param EleCampagne $campagne
     * @param RefTypeEtablissement $typeEtab
     * @param string $zone
     * @param unknown $etatSaisie
     * @return unknown
     */
    public function queryBuilderFindByCampagneTypeEtabZone(EleCampagne $campagne, RefTypeEtablissement $typeEtab = null, $zone = null, $etatSaisie, $user = null, $idSousTypeElection = null, $isEreaErpdExclus = false)
    {
        $qb = $this->createQueryBuilder('eleEtab');

        $req = 'eleEtab.id as idEleEtab, etab.uai as uai, etab.libelle as nomEtab, typePrio.code as typePrioritaire,te.libelle as nomTypeEtab, eleEtab.validation as validation';
        $req .= ', ste.id as sousTypeElectionId, ste.code as sousTypeElectionCode, comm.libelle as nomCommune, comm.codePostal as codePostal, etab.actif as actif';

        $qb->select($req);

        $this->addCampagneTypeEtabEtatSaisieToQuery($qb, $campagne, $typeEtab, $zone, $etatSaisie, $user, $isEreaErpdExclus, $idSousTypeElection);

        // IEN
        // if($profil != RefProfil::CODE_PROFIL_IEN){
        $qb->orderBy('comm.codePostal', 'ASC');
        $qb->orderBy('ste.code', 'ASC');

        return $qb;
    }

    public function queryBuilderFindByCampagneTypeEtabZoneExport(EleCampagne $campagne, RefTypeEtablissement $typeEtab = null, $zone = null, $etatSaisie, $isEreaErpdExclus = false, $idSousTypeElection = null)
    {
        $qb = $this->createQueryBuilder('eleEtab');

        $this->addCampagneTypeEtabEtatSaisieExportToQuery($qb, $campagne, $typeEtab, $zone, $etatSaisie, $isEreaErpdExclus, $idSousTypeElection);

        $qb->orderBy('comm.codePostal', 'ASC');

        return $qb;
    }

    /**
     *
     * @param EleCampagne $campagne
     * @param RefTypeEtablissement $typeEtab
     * @param string $zone
     * @param unknown $etatSaisie
     */
    public function queryBuilderConsolidationByCampagneTypeEtabZoneEtatSaisie(EleCampagne $campagne, RefTypeEtablissement $typeEtab = null, $zone = null, $etatSaisie, RefUser $refUser = null, $isEreaErpdExclus = false, $idSousTypeElection = null)
    {
        $qb = $this->createQueryBuilder('eleEtab');
        $qb->join('eleEtab.participation', 'elePart');
        $qb->select('c.id, sum(elePart.nbInscrits) as nbIns, sum(elePart.nbVotants) as nbVotants, sum(elePart.nbVotants - elePart.nbNulsBlancs) as nbExpr, sum(elePart.nbNulsBlancs) as nbNulsBlancs,
						   	sum(elePart.nbSiegesPourvoir) as nbSiegPourvoir, sum(elePart.nbSiegesPourvus) as nbSiegPourvus, sum(elePart.nbSiegesSort) as nbSiegesSort,  
						   	count(eleEtab.id) as nbEtabExpr');

        $this->addCampagneTypeEtabEtatSaisieToQuery($qb, $campagne, $typeEtab, $zone, $etatSaisie, $refUser, $isEreaErpdExclus, $idSousTypeElection);

        return $qb;
    }

    /**
     * Ajout des joins et where aux requêtes
     */
    protected function addCampagneTypeEtabEtatSaisieToQuery($qb, $campagne, $typeEtab, $zone, $etatSaisie, $refUser = null, $isEreaErpdExclus = false, $idSousTypeElection = null)
    {

        // La zone est une liste d'établissements
        if (is_array($zone) ){
            $uais = array();
            foreach($zone as $etab){
                $uais[] = $etab->getUai();
            }
            $qb->add('where', $qb->expr()->in('etab.uai', ':uais'));
            $qb->setParameter('uais', $uais);
        } else {
            // Stats generales : CE/DE/IEN limiter aux etabs du perimetre
            if (null != $refUser
                && ($refUser->getProfil()->getCode() == RefProfil::CODE_PROFIL_IEN
                    || $refUser->getProfil()->getCode() == RefProfil::CODE_PROFIL_CE
                    || $refUser->getProfil()->getCode() == RefProfil::CODE_PROFIL_DE)) {
                $qb->where('etab.uai in ('.EpleUtils::getUais($refUser->getPerimetre()->getEtablissements()).')');
            }
        }

        // YME - HPQC DEFECT #220 RG_CONSULT_6_1
        $qb->andWhere('etab.actif = 1');

        // paramètre Campagne
        $qb->join('eleEtab.campagne', 'c', 'WITH', 'c =:campagne')
            ->setParameter('campagne', $campagne)
            ->join('eleEtab.etablissement', 'etab')
            ->leftJoin('eleEtab.sousTypeElection', 'ste')
            ->leftjoin('etab.typePrioritaire', 'typePrio');

        // Paramètre état des saisies
        if (! empty($etatSaisie)) {
            $qb->andWhere('eleEtab.validation IN (:etats)');
            $qb->setParameter('etats', $etatSaisie);
        }

        // Paramètre type établissement
        if (! empty($typeEtab)) {
            // si 2nd degré on affiche tous les etabs de degre = 2
            if($typeEtab->getId() == RefTypeEtablissement:: ID_TYP_2ND_DEGRE){
                $qb->join('etab.typeEtablissement', 'te');
                $qb->andWhere('te.degre = 2');
            } else {
                $qb->join('etab.typeEtablissement', 'te', 'WITH', 'te =:typeEtab')->setParameter('typeEtab', $typeEtab);
            }
        } else {
            $qb->join('etab.typeEtablissement', 'te');
        }

        // Evol 013E exclusion EREA-ERPD pour les élections PR
        if($isEreaErpdExclus){
            $qb->andWhere("te.id <> '".RefTypeEtablissement::ID_TYP_EREA_ERPD."'"); // YME 0145664
        }

        // Evol 013E ajout du sous-type d'élection 
        if (null != $idSousTypeElection){
            $qb->andWhere('ste.id = :steId');
            $qb->setParameter('steId', $idSousTypeElection);
        }

        if ($zone instanceof RefCommune) {
            $qb->join('etab.commune', 'comm', 'WITH', 'comm =:commune')->setParameter('commune', $zone);
        } else {
            $qb->join('etab.commune', 'comm');
        }
        if ($zone instanceof RefAcademie) {
            //First we check if the academie has not child:
            $checkChild = $this->_em->getRepository('EPLEElectionBundle:RefAcademie')->findAcademieFisuByParParent($zone->getCode());

            // DSDEN multidepartements Resultat : [ECT] fonctionnellement, tous les departements appartiennent a la meme academie
            if (null != $refUser && $refUser->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
                $qb->join('comm.departement', 'dept', 'WITH', 'dept.numero in ('.EpleUtils::getNumerosDepts($refUser->getPerimetre()->getDepartements()).')');
            } else {
                /*if(!is_null($zone->getAcademieFusion())){
                    $qb->join('comm.departement', 'dept')
                        ->Join('dept.academie', 'acad')
                        ->leftJoin('acad.AcademieFusion', 'acadf')
                        ->andWhere('acadf.code = :code')
                        ->setParameter('code', $zone->getAcademieFusion()->getCode());
                }else*/if(!empty($checkChild)){
                    $qb->join('comm.departement', 'dept')
                        ->Join('dept.academie', 'acad')
                        ->leftJoin('acad.AcademieFusion', 'acadf')
                        ->andWhere('acadf.code = :code')
                        ->setParameter('code', $zone->getCode());
                }else {
                    $qb->join('comm.departement', 'dept')
                        ->join('dept.academie', 'aca', 'WITH', 'aca =:academie')
                        ->setParameter('academie', $zone);
                }
            }
        }
        if ($zone instanceof RefDepartement) {
            $qb->join('comm.departement', 'dept', 'WITH', 'dept =:departement')->setParameter('departement', $zone);
        }
    }
    protected function addCampagneTypeEtabEtatSaisieExportToQuery($qb, $campagne, $typeEtab, $zone, $etatSaisie, $isEreaErpdExclus = false, $idSousTypeElection = null)
    {

        // paramètre Campagne
        $qb->join('eleEtab.campagne', 'c', 'WITH', 'c =:campagne')
            ->setParameter('campagne', $campagne)
            ->join('eleEtab.etablissement', 'etab')
            ->join('eleEtab.participation', 'participation')
            ->addSelect('participation')
            ->leftjoin('etab.typePrioritaire', 'typePrio');

        // Paramètre état des saisies
        if (! empty($etatSaisie)) {
            $qb->andWhere('eleEtab.validation IN (:etats)');
            $qb->setParameter('etats', $etatSaisie);
        }

        // Paramètre type établissement
        if ($typeEtab != null) {
            if($typeEtab->getId() == RefTypeEtablissement::ID_TYP_2ND_DEGRE){
                $qb->join('etab.typeEtablissement', 'te');
                $qb->andWhere('te.degre = 2');
            } else {
                $qb->join('etab.typeEtablissement', 'te', 'WITH', 'te =:typeEtab')->setParameter('typeEtab', $typeEtab);
            }
        } else {
            $qb->join('etab.typeEtablissement', 'te');
        }

        if ($zone instanceof RefCommune) {
            $qb->join('etab.commune', 'comm', 'With', 'comm =:commune')->setParameter('commune', $zone);
        } else {
            $qb->join('etab.commune', 'comm');
        }

        if ($zone instanceof RefDepartement) {
            $qb->join('comm.departement', 'dept', 'WITH', 'dept =:departement')->setParameter('departement', $zone);
        }

        if ($isEreaErpdExclus) {
            $qb->andWhere("te.code NOT LIKE '".RefTypeEtablissement::CODE_EREA_ERPD."'");
        } else {
            if ($idSousTypeElection != null) {
                $qb->join('eleEtab.sousTypeElection', 'ste');
                $qb->andWhere('ste.id =:id');
                $qb->setParameter('id', $idSousTypeElection);
            }
        }
    }

    /**
     *
     * @param : $campagne
     *            : obligatoire : EleCampagne
     * @param : $typeEtab
     *            : facultatif : RefTypeEtablissement : nul par défaut
     * @param : $zone
     *            : facultatif : RefAcademie ou RefDepartement ou RefCommune: nul par défaut
     * @param : $etatSaisie
     *            : array d'états d'avancement des saisies
     * @return ArrayCollection of EleEtablissement
     *         "findByTypeEtabZone" permet de récupérer les EleEtablissements avec participations
     *         pour pour les $typeEtab, $zone donnés
     */
    public function findByCampagneTypeEtabZone(EleCampagne $campagne, RefTypeEtablissement $typeEtab = null, $zone = null, $etatSaisie = array(EleEtablissement::ETAT_VALIDATION), $user = null, $idSousTypeElection = null, $isEreaErpdExclus = false)
    {
        return $this->queryBuilderFindByCampagneTypeEtabZone($campagne, $typeEtab, $zone, $etatSaisie, $user, $idSousTypeElection, $isEreaErpdExclus)
            ->getQuery()
            ->getResult();
    }
    public function findByCampagneTypeEtabZoneExport(EleCampagne $campagne, RefTypeEtablissement $typeEtab = null, $zone = null, $etatSaisie = array(EleEtablissement::ETAT_VALIDATION), $isEreaErpdExclus = false, $idSousTypeElection = null)
    {
        return $this->queryBuilderFindByCampagneTypeEtabZoneExport($campagne, $typeEtab, $zone, $etatSaisie, $isEreaErpdExclus, $idSousTypeElection)
            ->getQuery()
            ->getResult();
    }
    /**
     * Calcule les sommes utiles pour une campagne donnée
     * Paramètres optionnels : type étab, zone, état des saisies
     * Utilisé par ResultatController
     *
     * @param $em :
     *            entity manager
     * @param : $campagne
     *            : obligatoire : EleCampagne
     * @param : $typeEtab
     *            : facultatif : RefTypeEtablissement : nul par défaut
     * @param : $zone
     *            : facultatif : RefAcademie ou RefDepartement ou RefCommune: nul par défaut
     * @param : $etatSaisie
     *            : array d'états d'avancement des saisies
     * @return EleConsolidation
     */
    public function getConsolidationByCampagneTypeEtabZoneEtatSaisie($em, EleCampagne $campagne, RefTypeEtablissement $typeEtab = null, $zone = null, $etatSaisie, RefUser $refUser = null, $isEreaErpdExclus = false, $idSousTypeElection = null)
    {

        $query = $this->queryBuilderConsolidationByCampagneTypeEtabZoneEtatSaisie($campagne, $typeEtab, $zone, $etatSaisie, $refUser, $isEreaErpdExclus, $idSousTypeElection)
            ->getQuery();
        $result = $query->getResult();


        $consolidation = new EleConsolidation();
        $consolidation->setCampagne($campagne);

        if (($zone instanceof RefAcademie) or ($zone instanceof RefDepartement)) {
            $consolidation->setIdZone($zone->getIdZone());
        }

        if ($typeEtab instanceof RefTypeEtablissement) {
            if(RefTypeEtablissement::ID_TYP_2ND_DEGRE == $typeEtab->getId()) {
                $te = new RefTypeEtablissement();
                $te->setDegre(RefTypeEtablissement::SECOND_DEGRE);
                $consolidation->setTypeEtablissement($te);
            } else {
                $consolidation->setTypeEtablissement($typeEtab);
            }
        } else {
            $te = new RefTypeEtablissement();
            $te->setHasEclair(true);
            $consolidation->setTypeEtablissement($te);
        }

        $hasParticipation = false;
        $participation = new EleParticipation();
        if($campagne->getTypeElection()->getCodeUrlById() == RefTypeElection::CODE_URL_PARENT) {
            $queryModaVote = $this->queryBuilderModaliteVote($campagne, $typeEtab, $zone, $etatSaisie, $refUser, $isEreaErpdExclus, $idSousTypeElection)
                ->getQuery();
            $countModalVote = $queryModaVote->getResult();

            if (!empty($countModalVote)) {
                $participation->setConsolidationVoteUrneCorrespondance(0);
                $participation->setConsolidationVoteCorrespondance(0);
                foreach ($countModalVote as $cmpt) {
                    if($cmpt["id_modalite"] == RefModaliteVote::ID_MODALITE_VOTE_URNE_CORRESPONDANCE) {
                        $participation->setConsolidationVoteUrneCorrespondance($cmpt["nb_etab"]);
                    } elseif ($cmpt["id_modalite"] == RefModaliteVote::ID_MODALITE_VOTE_CORRESPONDANCE) {
                        $participation->setConsolidationVoteCorrespondance($cmpt["nb_etab"]);
                    }
                }
                $hasParticipation = true;
            }
        }

        if (! empty($result)) { // Construction d'une participation
            $consolidation->setNbEtabExprimes($result[0]['nbEtabExpr']);
            $participation->setNbInscrits($result[0]['nbIns']);
            $participation->setNbVotants($result[0]['nbVotants']);
            $participation->setNbNulsBlancs($result[0]['nbNulsBlancs']);
            $participation->setNbExprimes($result[0]['nbExpr']);
            $participation->setNbSiegesPourvoir($result[0]['nbSiegPourvoir']);
            $participation->setNbSiegesPourvus($result[0]['nbSiegPourvus']);
            $participation->setNbSiegesSort($result[0]['nbSiegesSort']);
            $hasParticipation = true;
        }

        if($hasParticipation) {
            $consolidation->setParticipation($participation);
        }

        // Résultats globaux
        $resultatsGlobaux = $this->_em->getRepository('EPLEElectionBundle:EleResultat')->findDatasEnCoursFromEleResultatByCampagneZoneTypeEtab($campagne, $zone, $typeEtab, $etatSaisie, $refUser, $isEreaErpdExclus, $idSousTypeElection);

        // Evol 015E affichage du nombre de sieges reellement attribue
        // Résultats globaux detaillés
        $resultatsDetailGlobaux = $this->_em->getRepository('EPLEElectionBundle:EleResultatDetail')->findDatasEnCoursFromEleResultatDetailByCampagneZoneTypeEtab($campagne, $zone, $typeEtab, $etatSaisie, $refUser, $isEreaErpdExclus, $idSousTypeElection);

        if (! empty($resultatsGlobaux)) {
            $fct_datasToEleResult = function ($datas) use($em)
            {
                $r = new EleResultat();
                $r->setOrganisation($em->getRepository('EPLEElectionBundle:RefOrganisation')
                    ->find($datas['idOrg']));
                $r->setNbVoix($datas['nbVoix']);
                $r->setNbSieges($datas['nbSieges']);
                $r->setNbSiegesSort($datas['nbSiegesSort']);
                $r->setNbCandidats($datas['nbCandidats']);
                return $r;
            };
            $resultats = array_map($fct_datasToEleResult, $resultatsGlobaux);

            // chercher les resultats detaillés de la zone
            if (! empty($resultatsDetailGlobaux)) {
                $fct_datasToEleResultDetail = function ($datas) use($em)
                {
                    $rd = new EleResultatDetail();
                    $rd->setOrganisation($em->getRepository('EPLEElectionBundle:RefOrganisation')
                        ->find($datas['idOrg']));
                    $rd->setNbVoix($datas['nbVoix']);
                    $rd->setNbSieges($datas['nbSieges']);
                    $rd->setNbSiegesSort($datas['nbSiegesSort']);
                    $rd->setNbCandidats($datas['nbCandidats']);
                    return $rd;
                };
                $resultatsDetails = array_map($fct_datasToEleResultDetail, $resultatsDetailGlobaux);
                //////////
                /// ANOMALIE 225211 [Mechri Atef]
                ///// Inutile de faire le traitement ci-dessous car les résultats ($resultats) calcule bien le nombre de sièges réels
                ///// à voir requête dans queryFindDatasResultatsEnCoursByCampagneZoneTypeEtab (Ripo EleEtablissement)
                ///// ce traitement aurait pu être correct si on calculait le nombre de sièges théoriques dans les résultats
                //////////

                // comparer les sieges rellement attribués dans les listes detaillées et le nombre se sieges theorique
                /*foreach ($resultats as $resultat) {
                    $nbCandidats = 0;
                    $nbSiegeTh = 0;
                    foreach ($resultatsDetails as $resultDetail) {
                        if ($resultDetail->getOrganisation()->getId() == $resultat->getOrganisation()->getId()) {
                            // le nombre de sieges reelement attribués à la liste
                            $nbCandidats = $nbCandidats + $resultDetail->getNbCandidats();
                            // le nombre de sieges theorique attribués à la liste
                            $nbSiegeTh = $nbSiegeTh + $resultDetail->getNbSieges();
                        }
                    }
                    if ($nbCandidats > 0 && $nbSiegeTh > 0) {
                        // la somme des nombres de siege reelement attribués à la zone de recherche
                        $nbSieges = $resultat->getNbSieges() - $nbSiegeTh + $nbCandidats;
                        $resultat->setNbSieges($nbSieges);
                    }
                }*/
                //Fin Anomalie 225211
            }

            $consolidation->setResultats($resultats);
        }

        return $consolidation;
    }


    public function findParticipationByNiveauCampagne($campagneId, $campagnePrecId, $typeZone, $typeEtablissement = null, $perimetre = null, $user = null, $campagneAnneeDeb = null) {

        $id = 'refDept.numero as id';
        $libelle = 'refDept.libelle';
        if($typeZone == 'academie'){
            $id = 'refAca.code as id';
            $libelle = 'refAca.libelle';
        }


        $stringQuery = '
        		SELECT '.$id.', '.$libelle.', eleCamp.id as idCampagne,
        		    sum(elePart.nbInscrits) as sumInscrits, sum(elePart.nbVotants) as sumVotants, sum(elePart.nbVotants - elePart.nbNulsBlancs) as sumExprimes, (sum(elePart.nbVotants)/sum(elePart.nbInscrits))*100 as p1,
        		    sum(elePart.nbSiegesPourvoir) as sumSiegesPourvoir, sum(elePart.nbSiegesPourvus)+ sum(COALESCE(elePart.nbSiegesSort,0)) as sumSiegesPourvus, ((sum(elePart.nbSiegesPourvus)+ sum(COALESCE(elePart.nbSiegesSort,0)))/sum(elePart.nbSiegesPourvoir))*100 as p2, 
        		    count(eleEtab.id) as sumEtabExprimes, count(eleEtab.id) as sumEtabTotal
				FROM EPLEElectionBundle:EleEtablissement eleEtab
        		JOIN EPLEElectionBundle:EleCampagne eleCamp WITH eleCamp.id = eleEtab.campagne
        		JOIN EPLEElectionBundle:EleParticipation elePart WITH elePart.id = eleEtab.participation
        		JOIN EPLEElectionBundle:RefEtablissement refEtab WITH refEtab.uai = eleEtab.etablissement 
        		JOIN EPLEElectionBundle:RefTypeEtablissement refTypeEtab WITH refTypeEtab.id = refEtab.typeEtablissement
        		JOIN EPLEElectionBundle:RefCommune refCommune WITH refEtab.commune = refCommune.id
                JOIN EPLEElectionBundle:RefDepartement refDept WITH refCommune.departement = refDept.numero
        		JOIN EPLEElectionBundle:RefAcademie refAca WITH refAca.code = refDept.academie ';
        //ce teste est ajoute sur un profile RECT fusion acad
        switch ($user->getProfil()->getCode()){
            case  RefProfil::CODE_PROFIL_DGESCO:
                //  $stringQuery .= ' JOIN EPLEElectionBundle:RefAcademieFusion refAcafu WITH refAcafu.id = refAca.AcademieFusion ';
                break;
            case  RefProfil::CODE_PROFIL_RECT:
                $acadUser = $this->_em->getRepository('EPLEElectionBundle:RefAcademie')->find($user->getIdZone());
                $lasteCampage = $this->_em->getRepository('EPLEElectionBundle:EleCampagne')->find($campagneId);
                $datelasteCampage = date($lasteCampage->getAnneeDebut().'-01-01');

                $hasParent = $acadUser->getAcademieFusion();
                $hasChild = $this->_em->getRepository('EPLEElectionBundle:RefAcademie')->findAcademieFisuByParParent($acadUser->getCode());;


                if($acadUser->getDateDesactivation() <= $datelasteCampage  && !is_null($hasParent) || !empty($hasChild)){
                    $stringQuery .= ' JOIN EPLEElectionBundle:RefAcademieFusion refAcafu WITH refAcafu.id = refAca.AcademieFusion ';
                }
                break;
            case  RefProfil::CODE_PROFIL_IEN:
                //INUTILE DE LIE LA REQUETE A L'ACADEMIE POUR L'IEN
                break;
            case  RefProfil::CODE_PROFIL_DSDEN:
                //INUTILE DE LIE LA REQUETE A L'ACADEMIE POUR L'IEN
                break;
            default:
                break;
        }
        $stringQuery .= 'WHERE eleCamp.id IN ( '.$campagneId.', '.$campagnePrecId. ')' . ' AND refEtab.actif = 1 AND eleEtab.validation = \'V\' ';

        if ($typeEtablissement != null) {
            if ($typeEtablissement->getId() == RefTypeEtablissement::ID_TYP_2ND_DEGRE) {
                $stringQuery .= ' AND refTypeEtab.id in ('.RefTypeEtablissement::ID_TYP_COLLEGE.', '.RefTypeEtablissement::ID_TYP_LYCEE.', '.RefTypeEtablissement::ID_TYP_LYC_PRO.')';
            } else {
                $stringQuery .= ' AND refTypeEtab.id = '.$typeEtablissement->getId();
            }
        } else {
            if ($perimetre != null && $perimetre->getDegres() != null) {
                $stringDegres = implode("','", $perimetre->getDegres());
                $stringQuery .= ' AND refTypeEtab.degre in (\'' . $stringDegres . '\')';
            }
        }

        // restriction par perimetre

        if ($perimetre != null && $perimetre->getAcademies() != null) {
            $stringAcademies = "";
            switch ($user->getProfil()->getCode()){
                case RefProfil::CODE_PROFIL_RECT:
                    foreach ($perimetre->getAcademies() as $uneAca) {
                        $stringAcademies .= "'" . $uneAca->getCode() . "',";
                    }
                    $stringAcademies = substr($stringAcademies, 0,  strlen($stringAcademies) - 1);
                    // Ce test été ajoute sur un profile RECT fusion acad
                    if($acadUser->getDateDesactivation() <= $lasteCampage  && !is_null($hasParent )) {
                        $stringQuery .= ' AND refAcafu.code in (' . $stringAcademies . ')';
                    }else{
                        $stringQuery .= ' AND refAca.code in (' . $stringAcademies . ')';
                    }
                    break;

                case RefProfil::CODE_PROFIL_IEN:
                    //Ne pas faire le where sur l'academie pour l'IEN (deja limité a ces etablissements/departements)
                    break;

                case RefProfil::CODE_PROFIL_DSDEN:
                    //Ne pas faire le where sur l'academie pour le DSDEN (deja limité a ces etablissements/departements)
                    break;

                case RefProfil::CODE_PROFIL_DGESCO:
                    //Ne pas faire le where sur l'academie pour le DEGESCO (vision globale)
                break;

                default:
                    foreach ($perimetre->getAcademies() as $uneAca) {
                        $stringAcademies .= "'" . $uneAca->getCode() . "',";
                    }
                    $stringAcademies = substr($stringAcademies, 0,  strlen($stringAcademies) - 1);
                    $stringQuery .= ' AND refAca.code in (' . $stringAcademies . ')';
                    break;
            }
        }

        if ($perimetre != null && $perimetre->getDepartements() != null) {
            $stringDepartements = "";
            foreach ($perimetre->getDepartements() as $unDept) {
                $stringDepartements .= "'" . $unDept->getNumero() . "',";
            }
            $taca = $perimetre->getDepartements();
            $stringDepartements = substr($stringDepartements, 0,  strlen($stringDepartements) - 1);
            $stringQuery .= ' AND refDept.numero in (' . $stringDepartements . ')';
        }

        // pour l IEN
        if ($perimetre != null && $perimetre->getEtablissements() != null) {
            $stringQuery .= ' AND refEtab.uai in ('.EpleUtils::getUais($perimetre->getEtablissements()).')';
        }

        $stringQuery .= '
				GROUP BY '.$libelle.', eleCamp.id
        		ORDER BY '.$libelle.', eleCamp.id desc';

        $query = $this->_em->createQuery($stringQuery);
        //echo $query->getSQL();die();

        return $query->getResult();
    }

    /**
     *
     * @param EleCampagne $campagne
     * @param RefEtablissement $etablissement
     */
    public function queryBuilderFindByCampagneEtablissement(EleCampagne $campagne, RefEtablissement $etablissement = null, $sousTypeElection = null, $etatsAvancement = null, $indCarence = null, $indDeficit = null)
    {
        $parameters = array();

        $qb = $this->createQueryBuilder('eleEtab');
        $qb->select('eleEtab')
            ->join('eleEtab.campagne', 'c', 'WITH', 'c =:campagne')
            ->join('eleEtab.participation', 'participation')
            ->addSelect('participation')
            ->join('eleEtab.etablissement', 'etab')
            ->leftJoin('eleEtab.sousTypeElection', 'sousTypeElect')
            ->where('1=1');

        $parameters['campagne'] = $campagne;

        if (null != $etablissement) {
            $qb->andWhere('eleEtab.etablissement =:etablissement');
            $parameters['etablissement'] = $etablissement;
        }

        $qb->setParameters($parameters);
        // sousTypeElection choisi A et ATTE ou SS
        if (null != $sousTypeElection) {
            if($sousTypeElection instanceof RefSousTypeElection){
                $qb->andWhere('eleEtab.sousTypeElection =:sousTypeElection');
                $qb->setParameter('sousTypeElection', $sousTypeElection);
            } else {
                // Defect 273 HPQC
                if($sousTypeElection->getId() == RefTypeElection::ID_TYP_ELECT_ASS_ATE){
                    $qb->andWhere('etab.typeEtablissement !=:typeEtablissement');
                    $qb->setParameter('typeEtablissement', $this->_em->getRepository('EPLEElectionBundle:RefTypeEtablissement')->find(RefTypeEtablissement::ID_TYP_EREA_ERPD));
                }
                // sousTypeElection choisi ASS et ATE ou PEE
                $listeSousTypeElection = $this->_em->getRepository('EPLEElectionBundle:RefSousTypeElection')->findSousTypesElectionsByRefTypeElection($sousTypeElection->getId());
                foreach($listeSousTypeElection as $sousTypeElect){
                    $qb->andWhere('eleEtab.sousTypeElection =:sousTypeElection');
                    $qb->setParameter('sousTypeElection', $sousTypeElect);
                }

            }
        }
        if(null != $etatsAvancement && !empty($etatsAvancement)){
            $qb->andWhere('eleEtab.validation IN (:etatsAvancement)');
            $qb->setParameter('etatsAvancement', $etatsAvancement);
        }

        if(null != $indCarence){
            $qb->andWhere('eleEtab.indCarence = 1');
        }

        if(null != $indDeficit){
            $qb->andWhere('eleEtab.indDeficit = 1');
            $qb->andWhere('c.typeElection != :typeElection');
            $qb->setParameter('typeElection', RefTypeElection::ID_TYP_ELECT_PARENT);
        }

        $qb->orderBy('etab.uai', 'ASC');

        return $qb;
    }

    /**
     *
     * @param : $campagne
     *            : obligatoire : EleCampagne
     * @param : $etablissement
     *            : facultatif : RefEtablissement : nul par défaut
     * @return ArrayCollection of EleEtablissement
     *         "findByCampagneEtablissement" permet de récupérer les EleEtablissements avec participations
     *         pour une $campagne et un $etablissement donnés
     *
     */
    public function findByCampagneEtablissement(EleCampagne $campagne, RefEtablissement $etablissement = null, RefSousTypeElection $sousTypeElection = null, $etatAvancement = null)
    {
        return $this->queryBuilderFindByCampagneEtablissement($campagne, $etablissement, $sousTypeElection, $etatAvancement)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les données de participation pour une campagne et un établissement donnés
     *
     * @param : $campagne
     *            : obligatoire : EleCampagne
     * @param : $etablissement
     *            : obligatoire : RefEtablissement : nul par défaut
     * @param : $sousTypeElection
     *            : obligatoire : RefSousTypeElection : nul par défaut
     * @return EleEtablissement or null
     *
     */
    public function findOneByCampagneEtablissement(EleCampagne $campagne, RefEtablissement $etablissement = null, $sousTypeElection = null, $etatsAvancement = null, $indCarence = null, $indDeficit = null)
    {
        try {
            return $this->queryBuilderFindByCampagneEtablissement($campagne, $etablissement, $sousTypeElection, $etatsAvancement, $indCarence, $indDeficit)
                ->getQuery()
                ->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $exception) {
            return null;
        }
    }

    /**
     *
     * @param : $campagne
     *            : obligatoire : EleCampagne
     *            Permet de purger les résultats des élections par établissement d'une campagne donnée
     */
    public function purgeEleEtabsCampagne(EleCampagne $campagne)
    {
        $id_campagne = $campagne->getId();
        $qb = $this->createQueryBuilder('eleEtab');
        $qb->delete()
            ->where('eleEtab.campagne = :campagne_id')
            ->setParameter('campagne_id', $id_campagne);

        $qb->getQuery()->execute();
    }

    /**
     *
     * @param : $campagne
     *            : obligatoire : EleCampagne
     *            Permet de valider les résultats des élections par établissement d'une campagne donnée
     */
    public function valideEleEtabsCampagne(EleCampagne $campagne)
    {
        $id_campagne = $campagne->getId();
        $qb = $this->createQueryBuilder('eleEtab');
        $qb->update()
            ->set('eleEtab.validation', '?1')
            ->where('eleEtab.campagne = :campagne_id')
            ->setParameter('campagne_id', $id_campagne)
            ->setParameter(1, EleEtablissement::ETAT_VALIDATION);

        $qb->getQuery()->execute();
    }

    /**
     *
     * Mise à jour de l'indicateur de tirage au sort
     *
     * @param unknown $indTirageSort
     */
    public function updateIndTirageSort($idEleEtab, $indTirageSort){
        $qb = $this->createQueryBuilder('eleEtab');
        $qb->update()
            ->set('eleEtab.indTirageSort', '?1')
            ->where('eleEtab.id = :id')
            ->setParameter(1, $indTirageSort)
            ->setParameter('id', $idEleEtab);

        $qb->getQuery()->execute();
    }

    /**
     *
     * Validation en masse des EleEtablissements
     *
     * @param unknown $listEleEtabIds
     * 					: obligatoire : Liste des ids des EleEtabs à valider
     */
    public function massValideEtabs($listEleEtabIds){
        $qb = $this->createQueryBuilder('eleEtab');
        $qb->update()
            ->set('eleEtab.validation', '?1')
            ->add('where', $qb->expr()->in('eleEtab.id', '?2'))
            ->setParameter(1, EleEtablissement::ETAT_VALIDATION)
            ->setParameter(2, $listEleEtabIds);

        $qb->getQuery()->execute();
    }


    /**
     *
     * @param EleCampagne $campagne
     * @param RefEtablissement $etablissement
     * @return EleEtablissement "getEleEtablissementGlobale" permet de récuperer les eleEtablissements
     *         pour une $campagne et une $etablissement donnés
     *         Les résultats associés à cet eleEtablissement seront triés par ordre croissant
     */
    public function getEleEtablissementGlobale($campagne, $etablissement, $sousTypeElection = null, $etatAvancement = null)
    {
        $eleEtablissement = new EleEtablissement();

        $eleEtablissementGlobaux = $this->findByCampagneEtablissement($campagne, $etablissement, $sousTypeElection, $etatAvancement);

        if (! empty($eleEtablissementGlobaux)) {
            $eleEtablissement = $eleEtablissementGlobaux[0];

            $resultatsEtablissement = $this->_em->getRepository('EPLEElectionBundle:EleResultat')->findByEleEtablissementOrderByOrdre($eleEtablissement);
            $eleEtablissement->setResultats($resultatsEtablissement);

            $resultatsEtablissement = $this->_em->getRepository('EPLEElectionBundle:EleResultatDetail')->findByEleEtablissement($eleEtablissement);
            $eleEtablissement->setResultatsDetailles($resultatsEtablissement);
        } else {
            $eleEtablissement = null;
        }
        return $eleEtablissement;
    }

    /**
     * Permet de récupérer toutes les infos de l'EleEtablissement à partir de l'Id
     * @param unknown $idEleEtab
     * @return NULL|\App\Entity\EleEtablissement
     */
    public function getEleEtablissementGlobaleById($idEleEtab){

        $qb = $this->createQueryBuilder('eleEtab');
        $qb->select('eleEtab')
            ->join('eleEtab.campagne', 'c')
            ->join('eleEtab.participation', 'participation')
            ->addSelect('participation')
            ->join('eleEtab.etablissement', 'etab')
            ->leftJoin('eleEtab.sousTypeElection', 'sousTypeElect')
            ->where('eleEtab.id = :idEleEtab')
            ->setParameter('idEleEtab', $idEleEtab);

        try{
            $eleEtab = $qb->getQuery()->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $exception) {
            return null;
        }

        $eleEtab = $this->getEleEtablissementGlobale($eleEtab->getCampagne(), $eleEtab->getEtablissement(), $eleEtab->getSousTypeElection());

        return $eleEtab;

    }

    /**
     *
     * @param EleCampagne $campagne
     * @param
     *            RefAcademie ou RefDepartement $zone
     * @param $validation :
     *            état de la saisie
     * @return count(EleEtablissement) "getNbEleEtabParCampagne" permet de compter le nombre de eleEtablissement
     *         pour une $campagne et une $zone données
     */
    public function getNbEleEtabParCampagne($campagne, $zone = null, $validation = 'S', $typeEtab = null, $user = null, $isEreaErpdExclus = false, $codeNatEtab = null, $idSousTypeElect = null, $indCarence = false, $indNvElection = false, $actif = false, $typeElection = null)
    {
        $query = $this->createQueryBuilder('e')
            ->select('count(e.id)')
            ->join('e.etablissement', 'etab')
            ->join('e.campagne', 'eleCamp')
            ->join('e.participation', 'p');


        $query->leftjoin('etab.commune', 'c');
        $query->leftJoin('etab.typeEtablissement', 'te');
        $query->where('e.campagne = :campagne');
        $query->setParameter('campagne', $campagne);

        // Le type d'élection
        // On teste les sous-types d'élection pour les EREA-ERPD
        // YME - 145755
        if(null != $typeElection && $typeElection instanceof RefSousTypeElection){
            $query->andWhere('e.sousTypeElection = :sousTypeElection');
            $query->setParameter('sousTypeElection', $typeElection);
        }

        // La nature de l'etablissement
        if (null != $codeNatEtab){
            $query->leftjoin('etab.uai_nature', 'nature');
            $query->andWhere('nature.type_nature = :codeNatEtab');
            $query->setParameter('codeNatEtab', $codeNatEtab);
        }

        // le nombre de carences
        if ($indCarence == true){
            $query->andWhere('e.indCarence = 1');
        }

        // le nombre de nouvelles elections à organiser
        if ($indNvElection == true){
            $query->andWhere('e.indDeficit = 1');
            $query->andWhere('te.degre = 2');
            $query->andWhere('eleCamp.typeElection != :typeElection');
            $query->setParameter('typeElection', RefTypeElection::ID_TYP_ELECT_PARENT);
        }

        // Evol 013E SousTypeElecion choisi
        if (null != $idSousTypeElect){
            if($idSousTypeElect == RefTypeElection::ID_TYP_ELECT_PEE || $idSousTypeElect == RefTypeElection::ID_TYP_ELECT_ASS_ATE){
                $query->join('e.campagne', 'cam')
                    ->join('cam.typeElection', 'typeElect');
                $query->andWhere('typeElect.id = :idSousTypeElect');
                $query->setParameter('idSousTypeElect', $idSousTypeElect);
            } else {
                $query->join('e.sousTypeElection', 'ste');
                $query->andWhere('ste.id = :idSousTypeElect');
                $query->setParameter('idSousTypeElect', $idSousTypeElect);
            }
        }

        if (null != $typeEtab){
            //Si 2nd degré
            if(RefTypeEtablissement::ID_TYP_2ND_DEGRE == $typeEtab->getId()){
                $query->andWhere('te.degre = 2');
            } else {
                $query->andWhere('etab.typeEtablissement = :typeEtab');
                $query->setParameter('typeEtab', $typeEtab);
            }
        } else {
            if ($user != null && $user->getPerimetre() != null && $user->getPerimetre()->getDegres() != null) {
                $query->andWhere('te.degre in (:degres)');
                $query->setParameter('degres', $user->getPerimetre()->getDegres());
            }
        }
        // Evol 013E RG_STATG_15
        if($isEreaErpdExclus){
            $query->andWhere("te.code NOT LIKE '".RefTypeEtablissement::CODE_EREA_ERPD."'");
        }

        if ($zone instanceof RefDepartement) {
            $query->leftJoin('c.departement', 'd');
            $query->andWhere('d.numero = :dep');
            $query->setParameter('dep', $zone);
        } else if ($zone instanceof RefAcademie) {
            $query->leftJoin('c.departement', 'd');
            // DSDEN multidepartements Resultat : [ECT] fonctionnellement, tous les departements appartiennent a la meme academie
            if (null != $user && $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
                $query->andWhere('d.numero in ('.EpleUtils::getNumerosDepts($user->getPerimetre()->getDepartements()).')');
            } else {
                $check = $this->_em->getRepository('EPLEElectionBundle:RefAcademie')->findAcademieFisuByParParent($zone->getCode());
                if (null != $zone->getCode()) { // ajout DSDEN multidepartements

                    if (NULL != $check) {
                        $query->leftJoin('d.academie', 'acad')
                            ->leftJoin('acad.AcademieFusion', 'acadf')
                            ->andWhere('acadf.code = :code')
                            ->setParameter('code', $zone );
                    }else {
                        $query = $query->andWhere('d.academie = :aca');
                        $query = $query->setParameter('aca', $zone);


                    }
                } else {

                    $query = $query->andWhere('d.numero in ('.EpleUtils::getNumerosDepts($zone->getDepartements()).')');
                }
            }
        }

        // Stats generales : CE/DE/IEN limiter aux etabs du perimetre
        if (null != $user
            && ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_IEN
                || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_CE
                || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DE)) {
            $query->andWhere('etab.uai in ('.EpleUtils::getUais($user->getPerimetre()->getEtablissements()).')');
        }
        // BBL defects HPQC 240- 239
        if($validation != EleEtablissement::ETAT_TOUS){
            $query->andWhere('e.validation in (:validation)');
            $query->setParameter('validation', $validation);
        }

        // BBL Si actif est positionné on prend que les etabs actifs sinon on prend tous
        if($actif == true){
            $query->andWhere('etab.actif = true');
        }

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     *
     * @param EleCampagne $campagne
     * @param
     *            RefAcademie ou RefDepartement $niveau
     * @param $typeEtablissement :
     *            état de la saisie
     * @return List<Departement ou Academie>
     *         "findListEtablissementsByCampagneNiveau" permet de récuperer la liste des eleEtablissements
     *         pour une $campagne, un $niveau et un $typeEtablissement donnés
     */
    public function findListEtablissementsByCampagneNiveau($campagne, $niveau, $typeEtablissement = null)
    {
        $qb = $this->createQueryBuilder('eleEtab');
        $qb->join('eleEtab.campagne', 'eleCamp');
        $qb->join('eleEtab.participation', 'elePart');
        $qb->join('eleEtab.etablissement', 'etab');
        $qb->join('etab.typeEtablissement', 'eleTypeEtab');

        if ($niveau == 'departement') {
            $qb->join('etab.commune', 'eleCom');
            $qb->join('eleCom.departement', 'eleDepart');
            $qb->select('eleDepart.numero as idZone, eleDepart.libelle as libelle, elePart.id as Id, sum(elePart.nbInscrits) as nbIns, sum(elePart.nbVotants) as nbVotants, sum(elePart.nbExprimes) as nbExpr,
							   	sum(elePart.nbSiegesPourvoir) as nbSiegPourvoir, sum(elePart.nbSiegesPourvus) as nbSiegPourvus,
							   	count(eleEtab.id) as nbEtabExpr');
            $qb->where('eleEtab.campagne = :campagne');
            $qb->setParameter('campagne', $campagne);
            $qb->groupBy('eleDepart.numero');
        }

        if ($niveau == 'academie') {
            $qb->join('etab.commune', 'eleCom');
            $qb->join('eleCom.departement', 'eleDepart');
            $qb->join('eleDepart.academie', 'eleAcad');
            $qb->select('eleAcad.code as idZone, eleAcad.libelle as libelle,eleDepart.libelle as libelleDep, elePart.id as Id, sum(elePart.nbInscrits) as nbIns, sum(elePart.nbVotants) as nbVotants, sum(elePart.nbExprimes) as nbExpr,
						   	sum(elePart.nbSiegesPourvoir) as nbSiegPourvoir, sum(elePart.nbSiegesPourvus) as nbSiegPourvus,
						   	count(eleEtab.id) as nbEtabExpr');
            $qb->where('eleEtab.campagne = :campagne');
            $qb->setParameter('campagne', $campagne);
            $qb->groupBy('eleAcad.code');
        }

        if ($typeEtablissement instanceof RefTypeEtablissement) {
            $qb->Andwhere('etab.typeEtablissement = :type');
            $qb->setParameter('type', $typeEtablissement);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     *
     * @param EleCampagne $campagne
     * @param EleCampagne $campagnePrec
     * @param
     *            RefAcademie ou RefDepartement $niveau
     * @param RefTypeEtablissement $typeEtablissement
     * @return List<Departement ou Academie> consolidées
     *         "findParticipationConsolidationByNiveauCampagne" permet de récuperer la liste des eleEtablissements et des consolidations
     *         pour les campagnes $campagne et $campagnePrec en fonction d'un $niveau et d'un $typeEtablissement donnés
     */
    public function findParticipationConsolidationByNiveauCampagne($campagneId, $campagnePrecId, $typeZone, $typeEtablissement = null, $perimetre = null)
    {
        $id = 'refDept.numero as id';
        $libelle = 'refDept.libelle';
        if($typeZone == 'academie'){
            $id = 'refAca.code as id';
            $libelle = 'refAca.libelle';
        }

        $stringQuery = '
        		SELECT '.$id.', '.$libelle.', eleCamp.id as idCampagne, 
        		    sum(elePart.nbInscrits) as sumInscrits, sum(elePart.nbVotants) as sumVotants, sum(elePart.nbVotants - elePart.nbNulsBlancs) as sumExprimes, (sum(elePart.nbVotants)/sum(elePart.nbInscrits))*100 as p1,
        		    sum(elePart.nbSiegesPourvoir) as sumSiegesPourvoir, sum(elePart.nbSiegesPourvus) as sumSiegesPourvus, (sum(elePart.nbSiegesPourvus)/sum(elePart.nbSiegesPourvoir))*100 as p2,
        		    count(eleEtab.etablissement) as sumEtabExprimes
				FROM EPLEElectionBundle:EleEtablissement eleEtab
                JOIN EPLEElectionBundle:EleCampagne eleCamp WITH eleCamp.id = eleEtab.campagne
                JOIN EPLEElectionBundle:EleParticipation elePart WITH elePart.id = eleEtab.participation
                JOIN EPLEElectionBundle:RefEtablissement refEtab WITH refEtab.uai = eleEtab.etablissement
                JOIN EPLEElectionBundle:RefTypeEtablissement refTypeEtab WITH refTypeEtab.id = refEtab.typeEtablissement
                JOIN EPLEElectionBundle:RefCommune refComm WITH refComm.id = refEtab.commune
                JOIN EPLEElectionBundle:RefDepartement refDept WITH refDept.numero = refComm.departement
                JOIN EPLEElectionBundle:RefAcademie refAca WITH refAca.code = refDept.academie
        		WHERE eleCamp.id IN ( '.$campagneId.', '.$campagnePrecId.')
        		AND eleEtab.validation = \'V\' ';
        if (null != $typeEtablissement){
            $stringQuery .= '
        		AND refTypeEtab.id = '.$typeEtablissement->getId();
        } else {
            if ($perimetre != null && $perimetre->getDegres() != null) {
                $stringDegres = implode("','", $perimetre->getDegres());
                $stringQuery .= ' AND refTypeEtab.degre in (\'' . $stringDegres . '\')';
            }
        }

        // restriction par perimetre
        if ($perimetre != null && $perimetre->getAcademies() != null) {
            $stringAcademies = "";
            foreach ($perimetre->getAcademies() as $uneAca) {
                $stringAcademies .= "'" . $uneAca->getCode() . "',";
            }
            $stringAcademies = substr($stringAcademies, 0,  strlen($stringAcademies) - 1);
            $stringQuery .= ' AND refAca.code in (' . $stringAcademies . ')';
        }

        if ($perimetre != null && $perimetre->getDepartements() != null) {
            $stringDepartements = "";
            foreach ($perimetre->getDepartements() as $unDept) {
                $stringDepartements .= "'" . $unDept->getNumero() . "',";
            }
            $stringDepartements = substr($stringDepartements, 0,  strlen($stringDepartements) - 1);
            $stringQuery .= ' AND refDept.numero in (' . $stringDepartements . ')';
        }

        $stringQuery .= '
				GROUP BY '.$libelle.', eleCamp.id
        		ORDER BY '.$libelle.', eleCamp.id desc';

        $query = $this->_em->createQuery($stringQuery);

        return $query->getResult();

    }

    /**
     *
     * @param EleCampagne $campagne
     * @param
     *            RefAcademie ou RefDepartement $niveau
     * @param RefTypeEtablissement $typeEtablissement
     * @return List<Departement ou Académie> avec les informations sur les participations
     *         "findParticipationDetailleeParTypeZoneEtTypePrioritaire" permet de récuperer la liste des départements ou des académies
     *         ainsi que les identifiants des participations
     *         pour une $campagne, un $niveau et un $typeEtablissement donnés
     */
    public function findParticipationDetailleeParTypeZoneEtTypePrioritaire($campagneId, $campagnePrecId, $typeZone, $idTypeEtablissement = null, $perimetre = null, $user = null)
    {
        $libelle = 'refDept.libelle';
        if($typeZone == 'academie'){
            $libelle = 'refAca.libelle';
        }

        $stringQuery = 'SELECT eleCamp.id as idCampagne, '.$libelle.', refTypePrio.code, sum(elePart.nbInscrits) as sumInscrits, sum(elePart.nbVotants) as sumVotants, 
        		sum(elePart.nbVotants - elePart.nbNulsBlancs) as sumExprimes, (sum(elePart.nbVotants)/sum(elePart.nbInscrits))*100 as p
                FROM EPLEElectionBundle:EleEtablissement eleEtab
                JOIN EPLEElectionBundle:EleCampagne eleCamp WITH eleCamp.id = eleEtab.campagne
                JOIN EPLEElectionBundle:EleParticipation elePart WITH elePart.id = eleEtab.participation
                JOIN EPLEElectionBundle:RefEtablissement refEtab WITH refEtab.uai = eleEtab.etablissement
                JOIN EPLEElectionBundle:RefTypePrioritaire refTypePrio WITH refTypePrio.id = refEtab.typePrioritaire
                JOIN EPLEElectionBundle:RefTypeEtablissement refTypeEtab WITH refTypeEtab.id = refEtab.typeEtablissement
                JOIN EPLEElectionBundle:RefCommune refComm WITH refComm.id = refEtab.commune
                JOIN EPLEElectionBundle:RefDepartement refDept WITH refDept.numero = refComm.departement
                JOIN EPLEElectionBundle:RefAcademie refAca WITH refAca.code = refDept.academie
                WHERE eleCamp.id IN ( '.$campagneId.', '.$campagnePrecId.')
        		AND eleEtab.validation = \'V\' ';
        if (null != $idTypeEtablissement){
            if($idTypeEtablissement == RefTypeEtablissement::ID_TYP_2ND_DEGRE) {
                $stringQuery .= ' AND refTypeEtab.degre = 2';
            } else {
                $stringQuery .= ' AND refTypeEtab.id = ' . $idTypeEtablissement;
            }
        } else {
            if ($perimetre != null && $perimetre->getDegres() != null) {
                $stringDegres = implode("','", $perimetre->getDegres());
                $stringQuery .= ' AND refTypeEtab.degre in (\'' . $stringDegres . '\')';
            }
        }

        // restriction par perimetre
        if ($perimetre != null && $perimetre->getAcademies() != null) {
            switch ($user->getProfil()->getCode()){
                case  RefProfil::CODE_PROFIL_DGESCO:
                    //INUTILE DE LIE LA REQUETE A L'ACADEMIE POUR LE DEGESCO
                    break;
                case  RefProfil::CODE_PROFIL_IEN:
                    //INUTILE DE LIE LA REQUETE A L'ACADEMIE POUR L'IEN
                    break;
                case  RefProfil::CODE_PROFIL_DSDEN:
                    //INUTILE DE LIE LA REQUETE A L'ACADEMIE POUR LE DSDEN
                    break;
                default:
                    $stringAcademies = "";
                    foreach ($perimetre->getAcademies() as $uneAca) {
                        $stringAcademies .= "'" . $uneAca->getCode() . "',";
                    }
                    $stringAcademies = substr($stringAcademies, 0,  strlen($stringAcademies) - 1);
                    $stringQuery .= ' AND refAca.code in (' . $stringAcademies . ')';
                    break;
            }
        }

        if ($perimetre != null && $perimetre->getDepartements() != null) {
            $stringDepartements = "";
            foreach ($perimetre->getDepartements() as $unDept) {
                $stringDepartements .= "'" . $unDept->getNumero() . "',";
            }
            $stringDepartements = substr($stringDepartements, 0,  strlen($stringDepartements) - 1);
            $stringQuery .= ' AND refDept.numero in (' . $stringDepartements . ')';
        }

        if ($perimetre != null && $perimetre->getEtablissements() != null) {
            $stringQuery .= ' AND refEtab.uai in ('.EpleUtils::getUais($perimetre->getEtablissements()).')';
        }

        $stringQuery .= '
				GROUP BY eleCamp.id, '.$libelle.', refTypePrio.code
        		ORDER BY '.$libelle.', refTypePrio.code, eleCamp.id desc';

        $query = $this->_em->createQuery($stringQuery);

        return $query->getResult();
    }

    /**
     *
     * @param EleCampagne $campagne
     * @param
     *            RefAcademie ou RefDepartement $niveau
     * @param RefTypeEtablissement $typeEtablissement
     * @return List<Departement ou Académie> avec les informations concernants les priorités
     *         "findListParticipationsByDepartementAcademy" permet de récuperer la liste des participations
     *         par académie ou département
     *
     */
    public function findListParticipationsByDepartementAcademy($campagne, $niveau, $typeEtablissement)
    {


        $listParticipations = array();

        if ($niveau == 'departement') {
            $listDept = $this->_em->getRepository('EPLEElectionBundle:RefDepartement')->findListDepartements();

            $i = 0;

            foreach ($listDept as $eleDept) {

                $listPart = $this->findListParticipationDepartementsAcademiesByCampagneNiveau($campagne, $niveau, $eleDept->getNumero(), $typeEtablissement);

                if ($listPart != null) {
                    $listParticipations[$i]['libelle'] = $eleDept->getLibelle();
                    $listParticipations[$i]['list'] = $listPart;
                } else {
                    $listParticipations[$i] = null;
                }
                $i ++;
            }
        } else {
            $listAcad = $this->_em->getRepository('EPLEElectionBundle:RefAcademie')->findListAcademies();

            $i = 0;

            foreach ($listAcad as $eleAcad) {

                $listPart = $this->findListParticipationDepartementsAcademiesByCampagneNiveau($campagne, $niveau, $eleAcad->getCode(), $typeEtablissement);

                if ($listPart != null) {
                    $listParticipations[$i]['libelle'] = $eleAcad->getLibelle();
                    $listParticipations[$i]['list'] = $listPart;
                } else {
                    $listParticipations[$i] = null;
                }

                $i ++;
            }
        }

        return $listParticipations;
    }

    /**
     *
     * @param
     *            List<EleParticipation> liste des participations par Académie ou Département
     * @return La liste des sommes par priorité, pour chaque Académie ou Départment à partir de la liste des participations
     *         "findSumElePrioritaireByDepartementAcademy" permet de récuperer la liste des sommes par priorité, par académie ou département
     *         à partir de la liste des participations
     */
    public function findSumElePrioritaireByDepartementAcademy($listParticipations)
    {
        $somme = array();

        $i = 0;

        if ($listParticipations != null) {
            foreach ($listParticipations as $partcipation) {
                // echo "**".$partcipation['Id'];
                $listElePrio = $this->_em->getRepository('EPLEElectionBundle:ElePrioritaire')->findListElePrioritaireParParticipation($partcipation['Id']);

                if ($listElePrio != null) {
                    $i ++;
                    $compteur = 0;

                    foreach ($listElePrio as $elePrio) {

                        if ($i == 1) {
                            $somme[$compteur]['categorie'] = $elePrio['categorie'];
                            $somme[$compteur]['nbIns'] = $elePrio['nbIns'];
                            $somme[$compteur]['nbVotants'] = $elePrio['nbVotants'];
                            $somme[$compteur]['nbExpr'] = $elePrio['nbExpr'];
                            $somme[$compteur]['nbIns'] = $elePrio['nbIns'];

                            if ($elePrio['nbIns'] != 0) {
                                $somme[$compteur]['pourcent'] = $elePrio['nbVotants'] / $elePrio['nbIns'];
                            } else {
                                $somme[$compteur]['pourcent'] = 0;
                            }
                        } elseif ($i > 1) {
                            $somme[$compteur]['categorie'] = $elePrio['categorie'];
                            $somme[$compteur]['nbIns'] += $elePrio['nbIns'];
                            $somme[$compteur]['nbVotants'] += $elePrio['nbVotants'];
                            $somme[$compteur]['nbExpr'] += $elePrio['nbExpr'];
                            $somme[$compteur]['nbIns'] += $elePrio['nbIns'];

                            if ($elePrio['nbIns'] != 0) {
                                $somme[$compteur]['pourcent'] += $elePrio['nbVotants'] / $elePrio['nbIns'];
                            }
                        }

                        $compteur ++;
                    }
                }
            }
        }

        return $somme;
    }

    /**
     *
     * @param unknown $yearOld
     */
    public function findObsoletePVs($yearOld){

        $qb = $this->createQueryBuilder('eleEtab');
        $qb->join('eleEtab.fichier', 'eleFichier');
        $qb->where('eleFichier.date < :date');
        $qb->setParameter('date', new \DateTime('-'.$yearOld.' year'));
        return $qb->getQuery()->getResult();

    }

    /**
     * retourne un tableau associatif des uai de ele_etablissement utilisés dans l'import ramsese
     *
     */
    public function getArrayEleEtablissementUai() {
        $sql = "SELECT distinct(ele_etablissement.uai) as d_uai FROM ele_etablissement";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();
        $array = array();
        while ($row = $stmt->fetch()) {
            $array[$row['d_uai']] = $row['d_uai'];
        }

        return $array;
    }

    public function findEtbsByCampagneZone($campagneIds, $departement_numero, $uais, $idTypeEtab, $natEtab, $idTypesElect, $idSousTypeElect,  $etatsAvancement, $ind_carence, $ind_deficit){

        $i = 0;
        $sql = '';
        foreach ($campagneIds as $campagneId){
            $sql .= '
				SELECT
				etab.uai,
				etab.libelle,
				etab.actif,
				rteb.degre,
				campagne.id as id_campagne,
				campagne.annee_debut,
				campagne.annee_fin,
				campagne.id_type_election,
				campagne.date_debut_saisie,
				campagne.date_fin_saisie,
				campagne.date_debut_validation,
				campagne.date_fin_validation,
				campagne.archivee,
				campagne.post_editable,
				eleEtab.id,
				eleEtab.validation,
				eleEtab.id_sous_type_election,
				eleEtab.ind_tirage_sort,
				eleEtab.ind_carence,
				eleEtab.ind_deficit,
				rte.code as code_type_election,
				rste.code as code_sous_type_election,
				alerte.code_type_alerte,
				etab.id_type_etablissement
				FROM ele_campagne campagne,
					ref_type_etablissement rteb,
					ref_type_election rte,
					ref_zone_nature rzn,
					ref_etablissement etab
				LEFT OUTER JOIN ele_etablissement eleEtab ON (etab.uai = eleEtab.uai) AND eleEtab.id_campagne = '.$campagneId.'
				LEFT OUTER JOIN ele_alerte alerte ON (eleEtab.id = alerte.id_ele_etablissement)
				LEFT OUTER JOIN ref_sous_type_election rste ON (rste.id = eleEtab.id_sous_type_election)
				WHERE campagne.id = '.$campagneId.'
				AND etab.actif = 1
				AND etab.id_commune IN (select id FROM ref_commune where departement = '.$departement_numero.')
				AND campagne.id_type_election = rte.id
				AND rteb.id = etab.id_type_etablissement
				AND rzn.uai_nature = etab.uai_nature';

            // Critère de sélection type établissement
            if(null != $idTypeEtab){
                if($idTypeEtab == RefTypeEtablissement::ID_TYP_2ND_DEGRE){
                    $sql .= '
				AND rteb.degre = 2';
                }else{
                    $sql .= '
				AND etab.id_type_etablissement = '.$idTypeEtab;
                }
            }

            // Critère de sélection zone nature
            if(null != $natEtab){
                $sql .= '
    			AND rzn.type_nature = "'.$natEtab.'"
    			';
            }

            // Crtitère de sélection type d'élection
            if(!empty($idTypesElect)){

                // Critère de sélection sous-type d'élection
                if(null != $idSousTypeElect){
                    $sql .= '
	    			AND rte.id = '.$idSousTypeElect;
                } else {


                    $j = 0;
                    $sql .= '
    				AND rte.id IN (';
                    foreach ($idTypesElect as $idTypeElect){
                        $sql .= $idTypeElect;
                        if($j < sizeof($idTypesElect) -1 ){
                            $sql .= ', ';
                        }
                        $j++;
                    }
                    $sql .= ') 
    					';

                }
            }

            // Critère de sélection d'avancement des saisies et statut des PVs
            if(!empty ($etatsAvancement)){
                $j = 0;
                $sql .= '
    					AND (';
                foreach($etatsAvancement as $etatAvancement){
                    if('X' == $etatAvancement){
                        $sql .= ' eleEtab.id IS NULL';
                    }else{
                        $sql .= ' eleEtab.validation = "'.$etatAvancement.'"';
                    }

                    if($j < sizeof($etatsAvancement) -1 ){
                        $sql .= ' OR ';
                    }
                    $j++;
                }
                $sql .= ')
    					';
            }

            // Critère de sélection sur le PV de carence
            if(null != $ind_carence){
                $sql .= '
    					AND eleEtab.ind_carence = 1';
            }

            // Critère de sélection sur des nouvelles élections à organiser
            if(null != $ind_deficit){
                $sql .= '
    					AND eleEtab.ind_deficit = 1';
            }

            if($i < sizeof($campagneIds) -1 ){
                $sql .= '
    					
				UNION';
            }
            $i++;

        }

        $sql .= '
    			
				ORDER BY uai, libelle, FIELD(id_type_election, 3, 1, 2), FIELD(id_sous_type_election, 10, 11)
    			';

        /*
        echo '<pre>';
        var_dump($sql);
        die();
        */

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();
        $result = array();
        while ($row = $stmt->fetch()) {
            $result[] = $row;
        }

        return $result;

    }




    public function findParticipationByNiveauCampagneANew($campagneId, $campagnePrecId, $typeZone, $typeEtablissement = null, $perimetre = null, $user = null, $campagneAnneeDeb = null) {

        $id = 'refDept.numero as id';
        $libelle = 'refDept.libelle';
        if($typeZone == 'academie'){
            $id = 'refAca.code as id';
            $libelle = 'refAca.libelle';
        }


        $stringQuery = '
        		SELECT '.$id.', '.$libelle.', eleCamp.id as idCampagne, 
        		    sum(elePart.nbInscrits) as sumInscrits, sum(elePart.nbVotants) as sumVotants, sum(elePart.nbVotants - elePart.nbNulsBlancs) as sumExprimes, (sum(elePart.nbVotants)/sum(elePart.nbInscrits))*100 as p1,
        		    sum(elePart.nbSiegesPourvoir) as sumSiegesPourvoir, sum(elePart.nbSiegesPourvus)+ sum(COALESCE(elePart.nbSiegesSort,0)) as sumSiegesPourvus, ((sum(elePart.nbSiegesPourvus)+ sum(COALESCE(elePart.nbSiegesSort,0)))/sum(elePart.nbSiegesPourvoir))*100 as p2, 
        		    count(eleEtab.id) as sumEtabExprimes, count(eleEtab.id) as sumEtabTotal, refAcafu.code as parentID, refAcafu.libelle parentLibelle
				FROM EPLEElectionBundle:EleEtablissement eleEtab
        		JOIN EPLEElectionBundle:EleCampagne eleCamp WITH eleCamp.id = eleEtab.campagne
        		JOIN EPLEElectionBundle:EleParticipation elePart WITH elePart.id = eleEtab.participation
        		JOIN EPLEElectionBundle:RefEtablissement refEtab WITH refEtab.uai = eleEtab.etablissement 
        		JOIN EPLEElectionBundle:RefTypeEtablissement refTypeEtab WITH refTypeEtab.id = refEtab.typeEtablissement
        		JOIN EPLEElectionBundle:RefCommune refCommune WITH refEtab.commune = refCommune.id
                JOIN EPLEElectionBundle:RefDepartement refDept WITH refCommune.departement = refDept.numero
        		JOIN EPLEElectionBundle:RefAcademie refAca WITH refAca.code = refDept.academie ';
        //ce teste est ajoute sur un profile RECT fusion acad
        switch ($user->getProfil()->getCode()){
            case  RefProfil::CODE_PROFIL_DGESCO:
                $stringQuery .= ' JOIN EPLEElectionBundle:RefAcademieFusion refAcafu WITH refAcafu.id = refAca.AcademieFusion ';
                break;
            case  RefProfil::CODE_PROFIL_RECT:
                $acadUser = $this->_em->getRepository('EPLEElectionBundle:RefAcademie')->find($user->getLogin());
                $lasteCampage = $this->_em->getRepository('EPLEElectionBundle:EleCampagne')->find($campagneId);
                $datelasteCampage = date($lasteCampage->getAnneeDebut().'-01-01');

                $hasParent = $acadUser->getAcademieFusion();
                $hasChild = $this->_em->getRepository('EPLEElectionBundle:RefAcademie')->findAcademieFisuByParParent($acadUser->getCode());;


                if($acadUser->getDateDesactivation() <= $datelasteCampage  && !is_null($hasParent) || !empty($hasChild)){
                    $stringQuery .= ' JOIN EPLEElectionBundle:RefAcademieFusion refAcafu WITH refAcafu.id = refAca.AcademieFusion ';
                }
                break;
            default:
                break;
        }
        $stringQuery .= 'WHERE eleCamp.id IN ( '.$campagneId.', '.$campagnePrecId. ')' . ' AND refEtab.actif = 1 AND eleEtab.validation = \'V\' ';

        if ($typeEtablissement != null) {
            if ($typeEtablissement->getId() == RefTypeEtablissement::ID_TYP_2ND_DEGRE) {
                $stringQuery .= ' AND refTypeEtab.id in ('.RefTypeEtablissement::ID_TYP_COLLEGE.', '.RefTypeEtablissement::ID_TYP_LYCEE.', '.RefTypeEtablissement::ID_TYP_LYC_PRO.')';
            } else {
                $stringQuery .= ' AND refTypeEtab.id = '.$typeEtablissement->getId();
            }
        } else {
            if ($perimetre != null && $perimetre->getDegres() != null) {
                $stringDegres = implode("','", $perimetre->getDegres());
                $stringQuery .= ' AND refTypeEtab.degre in (\'' . $stringDegres . '\')';
            }
        }

        // restriction par perimetre

        if ($perimetre != null && $perimetre->getAcademies() != null) {
            $stringAcademies = "";
            switch ($user->getProfil()->getCode()){
                case RefProfil::CODE_PROFIL_RECT:
                    foreach ($perimetre->getAcademies() as $uneAca) {
                        $stringAcademies .= "'" . $uneAca->getCode() . "',";
                    }
                    $stringAcademies = substr($stringAcademies, 0,  strlen($stringAcademies) - 1);
                    // Ce test été ajoute sur un profile RECT fusion acad
                    if($acadUser->getDateDesactivation() <= $lasteCampage  && !is_null($hasParent )) {
                        $stringQuery .= ' AND refAcafu.code in (' . $stringAcademies . ')';
                    }else{
                        $stringQuery .= ' AND refAca.code in (' . $stringAcademies . ')';
                    }
                    break;

                case RefProfil::CODE_PROFIL_DGESCO:
                    if(!is_null($campagneAnneeDeb)){
                        $datelasteCampage = date($campagneAnneeDeb .'-01-01');
                        $activeAcad =  $this->_em->getRepository('EPLEElectionBundle:RefAcademie')->listeActiveAcademiesByDateCampagne($datelasteCampage);
                        //get the active academies by Year
                        foreach ($activeAcad as $uneAca) {
                            $stringAcademies .= "'" . $uneAca->getCode() . "',";
                        }
                    }else{
                        foreach ($perimetre->getAcademies() as $uneAca) {
                            $stringAcademies .= "'" . $uneAca->getCode() . "',";
                        }
                    }

                    $stringAcademies = substr($stringAcademies, 0,  strlen($stringAcademies) - 1);
                    $stringQuery .= ' AND refAcafu.code in (' . $stringAcademies . ')';
                    break;

                default:
                    foreach ($perimetre->getAcademies() as $uneAca) {
                        $stringAcademies .= "'" . $uneAca->getCode() . "',";
                    }
                    $stringAcademies = substr($stringAcademies, 0,  strlen($stringAcademies) - 1);
                    $stringQuery .= ' AND refAca.code in (' . $stringAcademies . ')';
                    break;
            }
        }

        if ($perimetre != null && $perimetre->getDepartements() != null) {
            $stringDepartements = "";
            foreach ($perimetre->getDepartements() as $unDept) {
                $stringDepartements .= "'" . $unDept->getNumero() . "',";
            }
            $taca = $perimetre->getDepartements();
            $stringDepartements = substr($stringDepartements, 0,  strlen($stringDepartements) - 1);
            $stringQuery .= ' AND refDept.numero in (' . $stringDepartements . ')';
        }

        // pour l IEN
        if ($perimetre != null && $perimetre->getEtablissements() != null) {
            $stringQuery .= ' AND refEtab.uai in ('.EpleUtils::getUais($perimetre->getEtablissements()).')';
        }

        $stringQuery .= '
				GROUP BY '.$libelle.', eleCamp.id
        		ORDER BY '.$libelle.', eleCamp.id desc';

        $query = $this->_em->createQuery($stringQuery);
        // echo $query->getSQL();die();
        // echo  $waww =   $query->getSQL(); die();

        return $query->getResult();
    }

    /**
     *
     * @param EleCampagne $campagne
     * @param RefTypeEtablissement $typeEtab
     * @param string $zone
     * @param unknown $etatSaisie
     */
    public function queryBuilderModaliteVote(EleCampagne $campagne, RefTypeEtablissement $typeEtab = null, $zone = null, $etatSaisie, RefUser $refUser = null, $isEreaErpdExclus = false, $idSousTypeElection = null)
    {
        $qb = $this->createQueryBuilder('eleEtab');
        $qb->join('eleEtab.participation', 'elePart');
        $qb->join('elePart.modaliteVote', 'refModaliteVote');
        $qb->select('refModaliteVote.id as id_modalite, count(eleEtab) as nb_etab');

        $this->addCampagneTypeEtabEtatSaisieToQuery($qb, $campagne, $typeEtab, $zone, $etatSaisie, $refUser, $isEreaErpdExclus, $idSousTypeElection);
        $qb->groupBy('refModaliteVote.id');
        return $qb;
    }
}
