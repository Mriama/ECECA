<?php

namespace App\Controller;

use App\Entity\EleConsolidation;
use App\Entity\EleEtablissement;
use App\Entity\EleParticipation;
use App\Entity\RefEtablissement;
use App\Controller\BaseController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\EleCampagne;
use App\Entity\RefTypeElection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\TypeElectionType;


class CampagneController extends BaseController
{
	/**
	 * Fonction permettant d'afficher l'index de campagne (Page permettant de rechercher une campagne par type d'élection)
	 * @throws AccessDeniedException
	 * 
	 * @Route("campagnes", name="campagnes")
	 */
	public function indexAction(Request $request) 
	{
		$em = $this->getDoctrine()->getManager();
		// if (false === $this->container->get('security.authorization_checker')->isGranted('ROLE_GEST_CAMP')) {
		// 	throw new AccessDeniedException();
		// }
		$user = $this->getUser();
		
		$repoCampagne = $em->getRepository(EleCampagne::class);
		$repoTypeElection = $em->getRepository(RefTypeElection::class);
		
		// Récupération du Type d'élection précédemment sélectionné
		$typeElectIdSession = $request->getSession()->get('typeElectIdSession');
		if($typeElectIdSession != null){
			$te_defaultValue = $repoTypeElection->find($typeElectIdSession);
		}else{
			$te_defaultValue = null;
		}

		if (empty($te_defaultValue)) 
		{
			// Choix du type d'élection 1 par défaut si aucun choix précédemment effectué ("ASS et ATE")
			$te_defaultValue = $repoTypeElection->find(1);
		}
		
		$form = $this->createForm(TypeElectionType::class,$te_defaultValue );
		
		$params['form'] =  $form->createView();
		// Indication d'une élection de type 'Parents'
		$params['isTypeElectionParent'] = ($te_defaultValue!=null and
				$te_defaultValue->getId()== RefTypeElection::ID_TYP_ELECT_PARENT)
				? true : false;
		
		if ($request->getMethod() == 'POST')
		{
			$form->handleRequest($request);
			
			if ($form->isSubmitted() && $form->isValid())
			{
				// Récupération du type de campagne sélectionné
				$dataRequestArray = $form->getData();
				$typeElection = $dataRequestArray['typeElection'];
				$this->get('session')->set('typeElectIdSession', $typeElection->getId());
				
				// Récupération de la dernière campagne pour ce type d'élection
				$campagne = $repoCampagne->getLastCampagne($typeElection->getId());

				if ($campagne != null)
				{
					$campagneId = $campagne->getId();
				}
				else
				{
					// Aucune campagne n'existe pour ce type d'élection, on âsse en mode initialisation de 
					// Campagne (si la date le permet)
					$campagneId = 0;
				}
				
				return $this->redirect($this->generateUrl('EPLEAdminBundle_campagne_modifier',array('campagneId'=>$campagneId)));
			}
		}
		
		return $this->render('campagne/index.html.twig', $params);
	}
	
	// *******************************************************************************************
	// *******************************************************************************************
	// *******************************************************************************************
	
	/**
	 * Fonction permettant de créer ou de modifier une campagne en controlant les informations entrées par l'utilisateur
	 * @param integer $campagneId
	 * @throws AccessDeniedException
	 */
	public function modifierAction($campagneId=0)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_GEST_CAMP')) {
			throw new AccessDeniedException();
		}
		
		$em = $this->getDoctrine()->getManager();
		$repoCampagne = $this->getDoctrine()->getRepository(EleCampagne::class);
		$repoTypeElection = $this->getDoctrine()->getRepository(RefTypeElection::class);
		$request = $this->get('request');
		
		$isClosed = false;
		
		if ($campagneId == 0) 
		{	
			// Création d'une nouvelle campagne d'élection pour le type d'élection choisi si la date le permet
			$typeElectIdSession = $this->get('session')->get('typeElectIdSession');
			$typeElection = $repoTypeElection->find($typeElectIdSession);
			
			if ($typeElection == null)
			{
				// Choix du type d'élection 1 par défaut si aucun choix précédemment effectué ("ASS & ATE")
				$typeElection = $repoTypeElection->find(1);
			}
			
			// On teste si on est en droit d'initialiser une nouvelle campagne pour ce type d'élection
			// à savoir la campagne précédente est archivée (ou non existante) et la date est valide (comprise entre 
			// le 01/01 et le 31/12 de l'année de début de la campagne
			$dateCourante = new \DateTime();
			$anneeDebutCampagne = $repoCampagne->getAnneeDebutNewCampagne($typeElection->getId(),$dateCourante);
			switch($anneeDebutCampagne)
			{
				case -1 :
					// La date ne permet pas d'initialiser une nouvelle campagne
					$campagne = new \App\Entity\EleCampagne($typeElection);
					$this->get('session')->getFlashBag()->set('erreur', 'La nouvelle campagne ne peut être initialisée qu\'à compter du 1er janvier.');
					break;
				case 0 :
					// La campagne précédente n'est pas archivée
					$campagne = new \App\Entity\EleCampagne($typeElection);
					$this->get('session')->getFlashBag()->set('erreur', 'La campagne précédente n\'a pas été archivée.');
					break;
				default :
					// Toutes les conditions requises pour l'initialisation d'une nouvelle campagne
					// sont vérifiées
					$campagne = new \App\Entity\EleCampagne($typeElection);
					$campagne->setAnneeDebut($anneeDebutCampagne);
					$campagne->setAnneeFin($anneeDebutCampagne + 1);
			}	
		} 
		else 
		{
			// Récupération de la campagne à modifier (si cela est encore possible)
			$campagne = $repoCampagne->find($campagneId);
			if ($campagne == null)
			{
				throw $this->createNotFoundException('La campagne d\'élections n\'a pas été trouvée.');
			}
			else
			{
				$anneeDebutCampagne = $campagne->getAnneeDebut();
			}
		}
		
		if ($campagne != null)
		{
			$formBuilder = $this->createFormBuilder($campagne)
						        ->add('dateDebutSaisie', 'date', array('read_only'=>$campagne->getArchivee(),
						        		                               'label'  => '* Date de début de saisie',
														        	   'widget' => 'single_text',
														        	   'format' => 'dd/MM/yyyy',
						        									   'max_length' => 10,
														        	   'required' => true,
									                                   'error_bubbling' => true,
						                                               'disabled'=>($campagne->getfermee() ? true : $campagne->isClosed()),
						        		                               'invalid_message' => "La date de début de saisie n'est pas valide"))
						        ->add('dateFinSaisie', 'date', array('read_only'=>$campagne->getArchivee(),
						        		                             'label'  => '* Date de fin de saisie',
						        		                             'widget' => 'single_text',
						        		                             'format' => 'dd/MM/yyyy',
						        									 'max_length' => 10,
									                                 'required' => true,
									                                 'error_bubbling' => true,
						                                             'disabled'=>($campagne->getfermee() ? true : $campagne->isClosed()),
						        		                             'invalid_message' => "La date de fin de saisie n'est pas valide"))
								->add('dateDebutValidation', 'date', array('read_only'=>$campagne->getArchivee(),
        		                             		     				   'label'  => '* Date de début de validation',
						        		                             	   'widget' => 'single_text',
						        		                             	   'format' => 'dd/MM/yyyy',
						        		                             	   'max_length' => 10,
						        		                             	   'required' => true,
						        		                             	   'error_bubbling' => true,
								                                           'disabled'=>($campagne->getfermee() ? true : $campagne->isClosed()),
						        		                             	   'invalid_message' => "La date de début de validation n'est pas valide"))
								->add('dateFinValidation', 'date', array('read_only'=>$campagne->getArchivee(),
        		                             				             'label'  => '* Date de fin de validation',
        		                             				             'widget' => 'single_text',
        		                             				             'format' => 'dd/MM/yyyy',
        		                             				             'max_length' => 10,
        		                             				             'required' => true,
        		                             				             'error_bubbling' => true,
								                                         'disabled'=>$campagne->getArchivee(),
        		                             				             'invalid_message' => "La date de fin de validation n'est pas valide"));
			
			// On teste si il s'agit d'une élection de type parents d'élèves
			$isTypeElectionParent = ($campagne->getTypeElection()->getId()== RefTypeElection::ID_TYP_ELECT_PARENT);
			/* YME 013E SUPPRIME
			if ($isTypeElectionParent)
			{
				$formBuilder->add('dateDebutConsultation', 'date', array('read_only'=>$campagne->getArchivee(),
					        		                                     'label'  => 'Date de début de consultation',
														        		 'widget' => 'single_text',
														        		 'format' => 'dd/MM/yyyy',
					        										     'max_length' => 10,
									                                     'required' => false,
									                                     'error_bubbling' => true,
						        		                                 'invalid_message' => "La date de début de consultation n'est pas valide"))
					        ->add('dateFinConsultation', 'date', array('read_only'=>$campagne->getArchivee(),
					        		                                   'label'  => 'Date de fin de consultation',
													        		   'widget' => 'single_text',
													        		   'format' => 'dd/MM/yyyy',
					        										   'max_length' => 10,
									                                   'required' => false,
									                                   'error_bubbling' => true,
						        		                               'invalid_message' => "La date de fin de consultation n'est pas valide"));
			
			}*/
			
			// Evol case à cocher campagne modifiable hors période de saisie
			$formBuilder->add('postEditable','checkbox', array('label'     => 'Editable hors période de saisie ?',
			    'required'  => false,'disabled'=>($campagne->getfermee() ? true : $campagne->isClosed()),
			));

                                						 
			$form = $formBuilder->getForm();
			$isArchivee = $campagne->getArchivee();
			$isClosed = $campagne->isClosed();
			
			// BBL 014E Re ouvertude d'une campagne en validation
			if ($isClosed && !$campagne->getFermee()){
				$campagne->setFermee(1);
				$em->persist($campagne);
				$em->flush();
			}
			
			// On teste si la date courante permet l'archivage
			if ($campagneId == 0)
			{
				// Une campagne ne peut être archivée lors de son initialistaion
				$isArchivable = false;
			}
			else
			{
				$isArchivable = $campagne->isArchivable();
			}
		}
		else
		{
			$formBuilder = $this->createFormBuilder();
			$form = $formBuilder->getForm();
			$isTypeElectionParent = ($typeElection->getId()==\App\Entity\RefTypeElection::ID_TYP_ELECT_PARENT);
			$isArchivee = true;
			$isArchivable = false;
		}
		
		if ($request->getMethod() == 'POST' ) // Appel au controller suite au submit de la form
		{
			$form->handleRequest($request);
			if ($form->isSubmitted() && $form->isValid()) {
				$campagneEnCours = $form->getData();
				$em->persist($campagneEnCours);
				$em->flush();
				
				$this->get('session')->getFlashBag()->set('info', 'Campagne sauvegardée.');
				$this->get('session')->set('typeElectIdSession', $campagneEnCours->getTypeElection()->getId());
				return $this->redirect($this->generateUrl('EPLEAdminBundle_campagnes'));
			}
		}
		
		// Affichage de la page du formulaire (2 cas) :
		// 1) Arrivée sur la page
		// 2) Retour après erreur suite au submit
		return $this->render('campagne/edit.html.twig', array("form"=>$form->createView(),
			"isTypeElectionParent"=>$isTypeElectionParent,
			"annee"=>$anneeDebutCampagne,
			"isArchivee"=>$isArchivee,
			"isArchivable"=>$isArchivable,
			"isClosed"=>$isClosed));
	}
	
	// *******************************************************************************************
	// *******************************************************************************************
	// *******************************************************************************************
	
	/**
	 * Fonction permettant d'archiver une campagne finie, une purge des campagne de plus de 10 ans est effectué
	 * @param integer $campagneId
	 * @throws AccessDeniedException
	 */
	public function archiverAction($campagneId)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_GEST_CAMP')) {
			throw new AccessDeniedException();
		}
		$em = $this->getDoctrine()->getManager();
		$repoCampagne = $this->getDoctrine()->getRepository(EleCampagne::class);
		$repoConsolidation = $this->getDoctrine()->getRepository(EleConsolidation::class);
		$repoEleEtab = $this->getDoctrine()->getRepository(EleEtablissement::class);
		$repoElePart = $this->getDoctrine()->getRepository(EleParticipation::class);
		$repoRefEtab = $this->getDoctrine()->getRepository(RefEtablissement::class);

		$campagne = $repoCampagne->find($campagneId);
		
		if ($campagne === null)
		{
			throw $this->createNotFoundException('La campagne d\'élections n\'a pas été trouvée.');
		}
		
		if ($campagne->getArchivee())
		{
			
			$this->get('session')->getFlashBag()->set('erreur', 'Cette campagne est déjà archivée.');
		}
		
		else 
		{
			
			try 
			{
				// Récupération de l'année de la campagne en cours
				$anneeDebutCampagneEnCours = $campagne->getAnneeDebut();
				
				// Suppressions des résultats consolidés de plus de 10 ans
				$purgeYearsCampagneArchiver = $this->getParameter("purge_years_campagne_archiver");
				$anneeDebutCampagneASupprrimer = $anneeDebutCampagneEnCours - $purgeYearsCampagneArchiver;
				// Récupération de la campagne à supprimer
				
				$listeCampagneASupprimer = $repoCampagne->getCampagnesASupprimerParTypeElectionAnneeDebut($campagne->getTypeElection(), $anneeDebutCampagneASupprrimer);
				
				if (!empty($listeCampagneASupprimer)) {
					foreach ($listeCampagneASupprimer as $campagneASupprimer) {
						// On supprime la campagne de plus de 10 ans (tous les éléments liés sont détruits en cascade)
						// Campagne -> Consolidations -> Résultats
						//                            -> Participations -> Prioritaires
						//          -> Etablissements -> Résultats
						//                            -> Participations
						$em->remove($campagneASupprimer);
					}
				}
			
				/* 
				 * Purge des établissements désactivés et plus rattachés à aucune campagne
				 */
				$repoRefEtab->purgeEtablissements();
				
				
				// Validation des données de la campagne (Résultats / Paricipations)
				// *** Résultats
				$repoEleEtab->valideEleEtabsCampagne($campagne);
								
				$campagne->setArchivee(1);
				$em->persist($campagne);
				$em->flush();
	    		
	    		$this->get('session')->getFlashBag()->set('info', 'La campagne a été archivée avec succès.');
	    		
			}
			catch(Exception $e)
			{
				$this->get('session')->getFlashBag()->set('erreur', 'La campagne n\'a pas pu être archivée.');
			}
			
		}
		return $this->redirect($this->generateUrl('EPLEAdminBundle_campagnes'));
	}
}
