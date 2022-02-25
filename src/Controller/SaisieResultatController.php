<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use App\Entity\EleEtablissement;
use App\Entity\EleParticipation;
use App\Entity\EleResultat;
use App\Entity\RefTypeElection;
use App\Entity\RefSousTypeElection;
use App\Entity\RefProfil;
use App\Entity\RefTypeAlerte;
use App\Entity\EleAlerte;
use App\Entity\EleCampagne;

use App\Utils\EpleUtils;

use App\Form\EleEtablissementType;
use App\Form\NbSiegesTirageAuSortType;
use App\Entity\RefTypeEtablissement;
use App\Entity\RefAcademie;

class SaisieResultatController extends AbstractController {

	/**
	 * Fonction permettant d'afficher la page d'édition des résultats (Participations et Résultats)
	 * et de sauvegarder ces résultats après vérification des données entrées
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param string $etablissementUai
	 * @param string $codeUrlTypeElect
	 * @param string $statut modifie le statut de l'objet
	 * @throws AccessDeniedException
	 */
	public function editionAction(\Symfony\Component\HttpFoundation\Request $request, $etablissementUai, $codeUrlTypeElect, $retourLstRech)
	{
		$em = $this->getDoctrine()->getManager();
		$params = array();
		$joursCalendaires = $this->container->getParameter('jours_calendaires');
		$typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
		$sousTypeElection = null;		
		// anomalie 0168664
		if(null == $typeElectionId || $codeUrlTypeElect == RefSousTypeElection::CODE_URL_A_ATTE || $codeUrlTypeElect == RefSousTypeElection::CODE_URL_SS){
			$sousTypeElectionId = RefSousTypeElection::getIdRefSousTypeElectionByCodeUrl($codeUrlTypeElect);
			$sousTypeElection = $em->getRepository('EPLEElectionBundle:RefSousTypeElection')->find($sousTypeElectionId);
			$typeElection = $sousTypeElection->getTypeElection();
			$typeElectionId = $typeElection->getId();
			$params['sousTypeElect'] = $sousTypeElection;
			$request->getSession()->set('sousTypeElectionId', $sousTypeElectionId);
		} else {
			$typeElection = $em->getRepository('EPLEElectionBundle:RefTypeElection')->find($typeElectionId);
		}
		
		if (empty($typeElection) && null != $sousTypeElection) { throw $this->createNotFoundException('Le type d\'élection '.$codeUrlTypeElect.' n\'a pas été trouvé.'); }
		$params['typeElect'] = $typeElection;
		$request->getSession()->set('typeElectionId', $typeElection->getId());
		
		$user = $this->get('security.context')->getToken()->getUser();
		//$zoneGlobalUser = EpleUtils::getZone($em, $user->getIdZone());

		$campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagneNonArchive($typeElection);
		if (empty($campagne)) { throw $this->createNotFoundException('Aucune campagne ouverte pour ce type d\'élection n\'a pas été trouvé.'); }
		$params['campagne'] = $campagne;

		$etablissement = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findOneByUai($etablissementUai);
		if (empty($etablissement)) { throw $this->createNotFoundException('L\'établissement  n\'a pas été trouvé.'); }
		$params['etablissement'] = $etablissement;
		
		$eleEtab = $em->getRepository('EPLEElectionBundle:EleEtablissement')->getEleEtablissementGlobale($campagne, $etablissement, $sousTypeElection);
		
		if (!$user->canEditEtab($etablissement, $campagne, $joursCalendaires, $eleEtab)) {
			throw new AccessDeniedException();
		}
		
		if (!empty($eleEtab)) {
			$eleEtablissement = $eleEtab;
		} else {
			$eleEtablissement = new EleEtablissement();
			$eleEtablissement->setCampagne($campagne);
			$eleEtablissement->setEtablissement($etablissement);
			$eleEtablissement->setParticipation(new EleParticipation());
			$eleEtablissement->setSousTypeElection($sousTypeElection);

			// Données par défaut pour le nombre de sièges
			// Chiffres définis dans settings.yml
			$settings = $this->container->getParameter('nb_sieges_typeElection_typeEtab');
			if (isset($settings[$typeElection->getCode()][$etablissement->getTypeEtablissement()->getCode()])) {
				$eleEtablissement->getParticipation()->setNbSiegesPourvoir($settings[$typeElection->getCode()][$etablissement->getTypeEtablissement()->getCode()]);
			}
			
			// RG_AIDE_SAISIE_10 élections des Personnels PEE, et ASS/ATE : EREA, ERPD [4 sièges PEE, 2 sièges ASS, 2 sièges ATE]
			// i.e type election = PEE && type etablissement = EREA-ERPD => nb sieges à pourvoir = EREA-ERPD-PEE
			// i.e type election = A et ATTE && type etablissement = EREA-ERPD => nb sieges à pourvoir = EREA-ERPD-ATE
			// i.e type election = SS && type etablissement = EREA-ERPD => nb sieges à pourvoir = EREA-ERPD-ASS
			if ($etablissement->getTypeEtablissement()->getCode() == RefTypeEtablissement::CODE_EREA_ERPD) {
				if ($typeElection->getId() == RefTypeElection::ID_TYP_ELECT_PEE) {
					$eleEtablissement->getParticipation()->setNbSiegesPourvoir($settings[$typeElection->getCode()]['EREA-ERPD-PEE']);
				} else if ( null != $sousTypeElection && $sousTypeElection->getId() == RefSousTypeElection::ID_TYP_ELECT_A_ATTE) {
					$eleEtablissement->getParticipation()->setNbSiegesPourvoir($settings[$typeElection->getCode()]['EREA-ERPD-ATE']);
				} else if ( null != $sousTypeElection && $sousTypeElection->getId() == RefSousTypeElection::ID_TYP_ELECT_SS) {
					$eleEtablissement->getParticipation()->setNbSiegesPourvoir($settings[$typeElection->getCode()]['EREA-ERPD-ASS']);
				}
			}
		}

		// 014E retour au tdb déplié
		$request->getSession()->set('dept_num', $etablissement->getCommune()->getDepartement()->getNumero());
		$params['tdbRetour'] = $request->getSession()->get('tdbRetour');
		
		$listeOrganisation = $em->getRepository('EPLEElectionBundle:RefOrganisation')->findBy(array('typeElection'=>$typeElectionId, 'obsolete'=>false), array('detaillee' => 'asc', 'ordre'=>'asc', 'libelle' => 'asc'));
		$params['nb_organisation'] = sizeof($listeOrganisation);
		foreach ($listeOrganisation as $organisation) {
			$existe = false;
			foreach ($eleEtablissement->getResultats() as $resultat) {
				if ($resultat->getOrganisation()->getId() == $organisation->getId()) {
					$existe = true;
				}
			}
			if (!$existe) {
				$eleResultat = new EleResultat();
				$eleResultat->setOrganisation($organisation);
				$eleResultat->setElectionEtab($eleEtablissement);
				$eleEtablissement->addResultat($eleResultat);
			}
		}

		$params['eleEtablissement'] = $eleEtablissement;

		$params['nb_resultats_detailles'] = sizeof($eleEtablissement->getResultatsDetailles());
				
		$form = $this->createForm(new EleEtablissementType($em), $eleEtablissement);
		
		/** ####################### SOUMISSION DU FORMULAIRE ############################## */
		
		if ($request->getMethod() == 'POST') {
			$form->bind($request);
			if ($form->isValid()) {

				$datasEleEtablissement = $form->getData();
				$datasEleEtablissement->setSousTypeElection($sousTypeElection);

				$datasCampagne = $datasEleEtablissement->getCampagne();
				$datasEtablissementUai = $datasEleEtablissement->getEtablissement()->getUai();
				$datasParticipation = $datasEleEtablissement->getParticipation();
				
				// 013E initialisation indCarence, indDeficit, indTirageSort et de la liste des alertes pour $datasEleEtablissement
				$datasEleEtablissement->setIndCarence(0);
				$datasEleEtablissement->setIndDeficit(0);
				if($datasEleEtablissement->getIndTirageSort() == null) { $datasEleEtablissement->setIndTirageSort(0); } //SESAM 0316056 : garder indice Tirage apres retour pour anomalie
				
				// 013E initialisation de nbSiegesSort pour $datasParticipation
                $datasParticipation->setNbSiegesSort(null);
				
				// mantis 146200 : suppression des eleAlertes au moment de l'enregistrement de la nouvelle élection mais plus au téléchargement du PV
// 				if (!$user->canSaisieNouvelleElection($etablissement, $eleEtablissement, $campagne, $joursCalendaires)) {
				$listeAlerte = $em->getRepository('EPLEElectionBundle:EleAlerte')->findBy(array('electionEtab'=>$datasEleEtablissement->getId()));
				if (count($listeAlerte) > 0) {
					foreach ($listeAlerte as $alerte) {
						$em->remove($alerte);
					}
					$em->flush();
				}
// 				}
				
				// Test afin de savoir si les résultats sont renseignés
				$nbOrganisation = 0;
				$nbVoixNul = 0;
				
				// 013E nombre candidats titulaires
				$nbTotalCandidats = 0;
				
				foreach ($datasEleEtablissement->getResultats() as $key => $datasResultat) {
					$nbOrganisation += 1;
					if ($datasResultat->getNbVoix() == 0) $nbVoixNul += 1;
				}
				// Si on a au moins un résultat de renseigné alors on persist les résultats
				if (($nbOrganisation - $nbVoixNul) != 0) {
					foreach ($datasEleEtablissement->getResultats() as $datasResultat) {
						$nbTotalCandidats += $datasResultat->getNbCandidats();
						$em->persist($datasResultat);
					}

					// Reconstruction de la liste des résultats détaillés
					$listeResultatsDetailles = $em->getRepository('EPLEElectionBundle:EleResultatDetail')->findBy(array('electionEtab'=>$datasEleEtablissement->getId()));
					if(count($listeResultatsDetailles) > 0){
    					foreach ($listeResultatsDetailles as $resultatDetaille){
    					   $em->remove($resultatDetaille);
    					}
    					$em->flush();
					}

					foreach ($datasEleEtablissement->getResultatsDetailles() as $datasResultatDetaille) {
					    // Il faut renseigner l'EleEtablissement pour les nouveaux résultats détaillés car l'objet n'est pas complètement hydraté par javascript
					    if ($datasResultatDetaille->getLibelle() != null) {
					    	$datasResultatDetaille->setElectionEtab($datasEleEtablissement);
					   		$em->persist($datasResultatDetaille);
					    }
					}
					
					// 013E deficit de candidats : nb Sieges pourvus < nb Sieges a pourvoir
					if ($datasParticipation->getNbSiegesPourvus() < $datasParticipation->getNbSiegesPourvoir()) {
						$datasEleEtablissement->setIndDeficit(1);
						
						// 013E creation alerte type DEFICIT
						$typeAlerte = $em->getRepository('EPLEElectionBundle:RefTypeAlerte')->find(RefTypeAlerte::CODE_DEFICIT);
						$eleAlerte = new EleAlerte();
						$eleAlerte->setElectionEtab($datasEleEtablissement);
						$eleAlerte->setTypeAlerte($typeAlerte);
						$em->persist($eleAlerte);
					}
					
				} else {
				    $datasEleEtablissement->setResultats(null);
				    
				    // 013E carence de candidats : verifier que nbInscrits est renseigné avec une valeur >= 0
				    // pas de résultat, nbTotalCandidatsTitulaires == 0
				    // verifier que nb votants, de bulletins nuls ou blancs, de suffrages exprimés, de sièges pourvus = 0
				    if ($datasParticipation->getNbInscrits() >= 0 && $nbTotalCandidats == 0
						&& $datasParticipation->getNbVotants() == 0 && $datasParticipation->getNbNulsBlancs() == 0 
				    	&& $datasParticipation->getNbExprimes() == 0 && $datasParticipation->getNbSiegesPourvus() == 0) {
						$datasEleEtablissement->setIndCarence(1);

						//Cas de carence = aucune modalite de vote
						$datasParticipation->setModaliteVote(null);

						// 013E creation alerte type CARENCE
						$typeAlerte = $em->getRepository('EPLEElectionBundle:RefTypeAlerte')->find(RefTypeAlerte::CODE_CARENCE);
						$eleAlerte = new EleAlerte();
						$eleAlerte->setElectionEtab($datasEleEtablissement);
						$eleAlerte->setTypeAlerte($typeAlerte);
						$em->persist($eleAlerte);
						
						// Suppression des résultats détaillés au cas où ils existent (UC modification resultat)
						$listeResultatsDetailles = $em->getRepository('EPLEElectionBundle:EleResultatDetail')->findBy(array('electionEtab'=>$datasEleEtablissement->getId()));
						if (count($listeResultatsDetailles) > 0) {
							foreach ($listeResultatsDetailles as $resultatDetaille) {
								$em->remove($resultatDetaille);
							}
							$em->flush();
						}
					}
				}

				// Modification de l'état de la saisie si DSDEN
				// Passage à transmission directe pour les DSDEN qui viennent de saisir les résultats
				if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT){
				    $datasEleEtablissement->setValidation(EleEtablissement::ETAT_TRANSMISSION);
                    if($datasEleEtablissement->getIndTirageSort() == 2) { $datasEleEtablissement->setIndTirageSort(1); } //SESAM 0316056 : rerendre le tirage au sort disponible apres un retour pour anomalie
                }

				$em->persist($datasParticipation);
				$em->persist($datasEleEtablissement);
				$em->flush();

				$array = array('uai'=>$datasEtablissementUai, 'codeUrlTypeElect'=> $datasCampagne->getTypeElection()->getCodeUrlById());
				if(null != $sousTypeElection){
					$array['codeUrlTypeElect'] = $datasEleEtablissement->getSousTypeElection()->getCodeUrlById();
				}
				// 014E retour à la liste des établissements dans la recherche par etab
				if($retourLstRech){
					$array['retourLstRech'] = $retourLstRech;
				}
                $array['fromEdit'] = '1';
				return $this->redirect($this->generateUrl('EPLEElectionBundle_resultats_etablissement', $array));
			}
		}

		// Vérification des droits d'accès au PV
		if($user->canGetPVVierge($etablissement)){
			$params['accesPVVierge'] = true;
		}
		if($user->canGetPVRempli($etablissement) && $eleEtablissement->isTransmis()){
	        $params['accesPVRempli'] = true;
		}

		// On affiche le bouton "Lever les contôles de saisie" lorsque l'utilisateur peut saisir les résultats sans contrôles.
		if($user->canBypassControleResultatSaisie($etablissement, $campagne, $joursCalendaires)){
		    $params['accesLeveeControlesSaisie'] = true;
		}
		// Sésam 257871 : Contrôle de saisie toujours actif au chargement de la page.
		// Doit être désactivé manuellement par l'utilisateur par mesure de sécurité.
		$params['controle_saisie'] = true;

		$params['form'] = $form->createView();
		$params['warning']= $this->container->getParameter('warning');
		// 014E retour à la liste des établissements ou tdb
		$params['retourLstRech']= $retourLstRech;

		// 014E Message de confirmation dans la pop up dans le cas d'un deficit de candidats
		$params['confirmDeficit'] = $this->container->getParameter('message_deficit_confirm');
		// 014E externalisation du message bloquant RG_SAISIE_135
		$params['msgCandidatTitulaire'] = $this->container->getParameter('message_candidat_titulaire');


		return $this->render('EPLEElectionBundle:SaisieResultat:saisieResultats.html.twig', $params);
	}

	/**
	 * Aide à la saisie du nombre de sièges à pourvoir
	 * Appelé dans une lightbox
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param string $etablissementUai
	 * @param string $typeElectionId
	 */
	public function aideNbSiegesAction(\Symfony\Component\HttpFoundation\Request $request, $etablissementUai, $typeElectionId)
	{
		$em = $this->getDoctrine()->getManager();
		$typeElection = $em->getRepository('EPLEElectionBundle:RefTypeElection')->find($typeElectionId);
		$etablissement = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findOneByUai($etablissementUai);

		$params = array();

		if($etablissement->getCommune()->getDepartement()->getAcademie()->getCode() == RefAcademie::CODE_ACA_MAYOTTE){
			// EVOL 013E Cas particulier Mayotte
			$settings = $this->container->getParameter('nb_sieges_typeElection_typeEtab_mayotte');
			$params['tabSettings'] = $settings[$typeElection->getCode()];
		} else {
			$settings = $this->container->getParameter('nb_sieges_typeElection_typeEtab');	
			if (isset($settings[$typeElection->getCode()])) {
				$params['tabSettings'] = $settings[$typeElection->getCode()];
			} else {
				$params['tabSettings'] = array();
			}
		}

		$params['typeElection'] = $typeElection;
		$params['typeEtablissement'] = $etablissement->getTypeEtablissement();
		$params['alert']= $this->container->getParameter('alert');

		$params['libelle_aide_ecole'] = $this->container->getParameter('libelle_aide_ecole');
		$params['libelle_aide_college_moins_600'] = $this->container->getParameter('libelle_aide_college_moins_600');
		$params['libelle_aide_college'] = $this->container->getParameter('libelle_aide_college');
		$params['libelle_aide_lycee'] = $this->container->getParameter('libelle_aide_lycee');
		$params['libelle_aide_erea'] = $this->container->getParameter('libelle_aide_erea');
		$params['libelle_aide_erpd'] = $this->container->getParameter('libelle_aide_erpd');
		$params['libelle_aide_erea_et_erpd'] = $this->container->getParameter('libelle_aide_erea_et_erpd');
		$params['libelle_aide_indiquer_nombre_classes'] = $this->container->getParameter('libelle_aide_indiquer_nombre_classes');
		$params['libelle_aide_nombre_classes'] = $this->container->getParameter('libelle_aide_nombre_classes');
		$params['libelle_aide_question_college'] = $this->container->getParameter('libelle_aide_question_college');
		$params['libelle_aide_indiquer_type_etablissement'] = $this->container->getParameter('libelle_aide_indiquer_type_etablissement');
		$params['nombre_limite_ecole'] = $this->container->getParameter('nombre_limite_ecole');
		$params['message_erreur_nombre_limite_ecole'] = $this->container->getParameter('message_erreur_nombre_limite_ecole');
		$params['libelle_aide_titre'] = $this->container->getParameter('libelle_aide_titre');


		return $this->render('EPLEElectionBundle:SaisieResultat:aideNbSiegesAPourvoir.html.twig', $params);
	}
	
	/**
	 * Action pour saisir le nombre de sièges pourvus par tirage au sort
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param unknown $etablissementUai
	 * @param unknown $codeUrlTypeElect
	 * @throws AccessDeniedException
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 */
	public function nbSiegesTirageAuSortAction(\Symfony\Component\HttpFoundation\Request $request, $uai, $codeUrlTypeElect)
	{
		$em = $this->getDoctrine()->getManager();

        $joursCalendaires = $this->container->getParameter('jours_calendaires');
        $joursCalendairesIen = $this->container->getParameter('jours_calendaires_ien');

        $typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $sousTypeElection = null;
        if (null == $typeElectionId) {
        	$sousTypeElectionId = RefSousTypeElection::getIdRefSousTypeElectionByCodeUrl($codeUrlTypeElect);
        	$sousTypeElection = $em->getRepository('EPLEElectionBundle:RefSousTypeElection')->find($sousTypeElectionId);
			$typeElection = $sousTypeElection->getTypeElection();
			$typeElectionId = $typeElection->getId();
        } else {
        	$typeElection = $em->getRepository('EPLEElectionBundle:RefTypeELection')->find($typeElectionId);
        }

        if (null == $typeElection && null == $sousTypeElection) {
            throw $this->createNotFoundException('Type élection '.$codeUrlTypeElect.' inconnu');
        }

        $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne($typeElectionId);
        if (empty($campagne)) {
            throw $this->createNotFoundException('Les résultats de l\'établissement n\'ont pas été trouvés car la campagne est inconnue.');
        }

        $etablissement = $em->getRepository('EPLEElectionBundle:RefEtablissement')->find($uai);

        $params = $this->getParametersForConsultationResultatsEtablissement($campagne, $uai, $sousTypeElection);
        
        $user = $this->get('security.context')->getToken()->getUser();
        if (!$user->canSaisieTirageAuSort($etablissement, $params['electEtablissement'], $campagne, $joursCalendaires, $joursCalendairesIen)) {
        	throw new AccessDeniedException();
        }

        $params['etablissement'] = $etablissement;
        $form = $this->createForm(new NbSiegesTirageAuSortType(), null);
        
        $params['form'] = $form->createView();
        
        if ($request->getMethod() == 'POST') {
        	$form->bind($request);
        	if ($form->isValid()) {
        		$dataRequestArray = $form->getData();
        
        		// recuperation de eleEtablissement, eleParticipation
        		$datasEleEtablissement = $params['electEtablissement'];
        		$datasParticipation = $datasEleEtablissement->getParticipation();
        		
				//$datasParticipation->setNbSiegesSort($nbSiegesTirageAuSort);
                $em->getRepository('EPLEElectionBundle:EleEtablissement')->updateIndTirageSort(
                	$datasEleEtablissement->getId(),
                	EleEtablissement::ETAT_TIRAGE_AU_SORT_IEN
                );  
                
                $em->getRepository('EPLEElectionBundle:EleParticipation')->updateNbSiegesSort(
                	$datasParticipation->getId(),
                	$dataRequestArray['nbSiegesTirageAuSort']
                ); 
                
                $em->clear();
        		
        		// mantis 146200 : suppression des eleAlertes au moment de l'enregistrement du nbSiegesTirageAuSort mais plus au téléchargement du PV de tirage au sort
        		$listeAlerte = $em->getRepository('EPLEElectionBundle:EleAlerte')->findBy(array('electionEtab'=>$datasEleEtablissement->getId()));
        		if (count($listeAlerte) > 0) {
        			foreach ($listeAlerte as $alerte) {
        				$em->remove($alerte);
        			}
        			$em->flush();
        		}
        		
        		$array = array('uai'=>$uai, 'codeUrlTypeElect'=> $typeElection->getCodeUrlById());
        		if(null != $sousTypeElection){
        			$array['codeUrlTypeElect'] = $etablissement->getSousTypeElection()->getCodeUrlById();
        		}
        		return $this->redirect($this->generateUrl('EPLEElectionBundle_resultats_etablissement', $array));
        	}
        }

        $params['erreurs'] = $this->container->getParameter('erreurs');
        $params['warning'] = $this->container->getParameter('warning');
        return $this->render('EPLEElectionBundle:SaisieResultat:nbSiegesTirageAuSort.html.twig', $params);
	}
	
	private function getParametersForConsultationResultatsEtablissement(EleCampagne $campagne, $uai, RefSousTypeElection $sousTypeElection = null) {
		$em = $this->getDoctrine()->getManager();
		$params = array();
		$etab = $em->getRepository('EPLEElectionBundle:RefEtablissement')->find($uai);
	
		if (empty($etab)) {
			throw $this->createNotFoundException('Les résultats n\'ont pas été trouvés car l\'établissement est inconnu (' . $uai . ').');
		}
	
		if (null != $sousTypeElection){
			$params['sousTypeElect'] = $sousTypeElection;
		}
	
		$params['typeElect'] = $campagne->getTypeElection();
		$rech = array(
				'etablissement' => $etab->getUai(),
				'campagne' => $campagne->getId()
		);
	
		if (null != $sousTypeElection){
			$rech['sousTypeElection'] = $sousTypeElection->getId();
		}
	
		$eleEtab = $em->getRepository('EPLEElectionBundle:EleEtablissement')->findOneBy($rech);
	
		if (empty($eleEtab)) {
			$eleEtab = new EleEtablissement();
			$eleEtab->setCampagne($campagne);
			$eleEtab->setEtablissement($etab);
			$eleEtab->setValidation(null);
		} else {
			$res = $em->getRepository('EPLEElectionBundle:EleResultat')->findByEleEtablissementOrderByOrdre($eleEtab);
			// Evol 015E afficher le nombre de sieges reellement attribué
			if ($res != null) {
				foreach ($res as $resultat) {
					if ($resultat->getNbSieges() != null && $resultat->getNbCandidats() != null)
						$resultat->setNbSieges(min($resultat->getNbSieges(), $resultat->getNbCandidats()));
				}
			}

            $resDetail = $em->getRepository('EPLEElectionBundle:EleResultatDetail')->findByEleEtablissement($eleEtab);

            if ($resDetail != null) {
				foreach ($resDetail as $resultatDetail) {
					if ($resultatDetail->getNbSieges() != null && $resultatDetail->getNbCandidats() != null)
						$resultatDetail->setNbSieges(min($resultatDetail->getNbSieges(), $resultatDetail->getNbCandidats()));
				}
			}
			
			// si listes detaillees le nombre de sieges reel est la somme des sieges reel detaille
			if ($res != null && $resDetail != null) {
				foreach ($res as $result) {
					$nbSiegeReel = 0;
					foreach ($resDetail as $resultDetail) {
						if ($resultDetail->getOrganisation()->getId() == $result->getOrganisation()->getId())
							$nbSiegeReel = $nbSiegeReel + $resultDetail->getNbSieges();
					}
					if ($nbSiegeReel > 0)
						$result->setNbSieges($nbSiegeReel);
				}
			}
			
			$eleEtab->setResultats($res);
			$eleEtab->setResultatsDetailles($resDetail);
		}
	
		$params['electEtablissement'] = $eleEtab;
		$params['campagne'] = $campagne;
	
		return $params;
	}
}