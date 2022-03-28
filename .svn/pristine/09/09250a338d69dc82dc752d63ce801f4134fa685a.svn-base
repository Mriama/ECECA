<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class OrganisationController extends AbstractController {

	public function indexAction(\Symfony\Component\HttpFoundation\Request $request) {
		if (false === $this->get('security.context')->isGranted('ROLE_GEST_ORG')) {
			throw new AccessDeniedException();
		}
		
		$em = $this->getDoctrine()->getManager();
		
		$typeElectIdSession = $request->getSession()->get('typeElectIdSession');
		
		$te_defaultValue = $em->getRepository('EPLEElectionBundle:RefTypeElection')->find($typeElectIdSession);
		if (empty($te_defaultValue)) {
			$te_defaultValue = $em->getRepository('EPLEElectionBundle:RefTypeElection')->find(1);
		}
		
		
		$form = $this->createForm(new \App\Form\TypeElectionType(), (empty($te_defaultValue)? null : array('typeElection' => $te_defaultValue)) );
		$formHandler = new \App\Form\TypeElectionHandler($form, $request, $em);
		
		if ($formHandler->process()) {
			$te_defaultValue = $formHandler->getTeDefaultValue();
		}
		$params['form'] =  $form->createView();
						
		if ($te_defaultValue !=null) {
			$params['organisations'] = $em->getRepository('EPLEElectionBundle:RefOrganisation')->findOrganisationsByRefTypeElection($te_defaultValue->getId());
		} else {
			$this->get('session')->getFlashBag()->set('info', 'Aucune organisation proposée car il n\'existe pas de type d\'élection');
			$params['organisations'] = array();
		}
		
		if ($te_defaultValue!=null) { $this->get('session')->set('typeElectIdSession', $te_defaultValue->getId()); }
		$params['isTypeElectionParent'] = ($te_defaultValue!=null and 
												$te_defaultValue->getId()==\App\Entity\RefTypeElection::ID_TYP_ELECT_PARENT) 
													? true : false;
		$params['mess_warning']= $this->container->getParameter('mess_warning');
		
		return $this->render('EPLEAdminBundle:Organisation:index.html.twig', $params);
	}

	public function modifierOrganisationAction(\Symfony\Component\HttpFoundation\Request $request, $organisationId = 0) {
		if (false === $this->get('security.context')->isGranted('ROLE_GEST_ORG')) {
			throw new AccessDeniedException();
		}
		
		$em = $this->getDoctrine()->getManager();
		$typeElectIdSession = $this->get('session')->get('typeElectIdSession');
		$te_defaultValue = $em->getRepository('EPLEElectionBundle:RefTypeElection')->find($typeElectIdSession);
		
		if ($te_defaultValue == null) {
			$messageErreur_te = 'L\'ajout ou la modification d\'une organisation n\'est possible';
			$messageErreur_te .= ' qu\'après la sélection d\'un type d\'élection sur l\'écran de gestion des organisations.';
			throw $this->createNotFoundException($messageErreur_te);
		}

		if ($organisationId == 0) {
			$o_defaultValues = new \App\Entity\RefOrganisation($te_defaultValue);
		} else {
			$o_defaultValues = $em->getRepository('EPLEElectionBundle:RefOrganisation')->find($organisationId);
		}
		
		if ($o_defaultValues == null) {
			throw $this->createNotFoundException('L\'organisation n\'a pas été trouvée.');
		}
		
		$isReadOnlyFederation = false;
		if( ($o_defaultValues->getTypeElection()!=null) 
				and ($o_defaultValues->getTypeElection()->getId()==\App\Entity\RefTypeElection::ID_TYP_ELECT_PARENT))  {
			$isReadOnlyFederation = true;
		}
		
		$form = $this->createFormBuilder($o_defaultValues)
						->add('libelle', 'text', array(
								'label'  => '* Nom de l\'organisation',
								'required' => true,
								'trim' => true,
								'error_bubbling' => true))
						->add('federation', 'entity', array(
								'label' => 'Fédération',
								'multiple' => false,
								'class' => 'EPLEElectionBundle:RefFederation',
								'query_builder' => function(\Doctrine\ORM\EntityRepository $er) {
														return $er->createQueryBuilder('f')->orderBy('f.libelle', 'ASC');
													},
								'required' => false,
								'empty_value' => 'Aucune',
								'read_only' => $isReadOnlyFederation,		
								'property' => 'libelle'))
						->add('ordre', 'integer', array(
								'label'  => "* Ordre d'affichage",
								'required' => true,
								'trim' => true,
								'error_bubbling' => true,
								'invalid_message' => 'L\'ordre doit être un entier positif.'))
						->add('obsolete', 'checkbox', array(
								'label' => 'Organisation obsolète', 
								'required' => false))
						->getForm();
		
		if ($request->getMethod() == 'POST') {
			$form->bind($request);
			if ($form->isValid()) {
				$organisationEnCours = $form->getData();
				$em->persist($organisationEnCours);
				$em->flush();
		
				$this->get('session')->getFlashBag()->set('info', 'Organisation sauvegardée.');
				$this->get('session')->set('typeElectIdSession', $organisationEnCours->getTypeElection()->getId());
				return $this->redirect($this->generateUrl('EPLEAdminBundle_organisations'));
			}
		}
		$this->get('session')->set('typeElectIdSession', ( ($o_defaultValues->getTypeElection()==null) ? null : $o_defaultValues->getTypeElection()->getId() ));
		return $this->render('EPLEAdminBundle:Organisation:edit.html.twig', array('form' => $form->createView()));
	}

}
