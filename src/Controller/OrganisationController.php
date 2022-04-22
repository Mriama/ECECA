<?php

namespace App\Controller;

use App\Entity\RefFederation;
use App\Form\TypeElectionType;
use App\Entity\RefOrganisation;
use App\Entity\RefTypeElection;
use App\Form\TypeElectionHandler;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class OrganisationController extends AbstractController {

    private $request;
    private $doctrine;

    public function __construct(RequestStack $request, ManagerRegistry $doctrine) {
        $this->request = $request->getCurrentRequest();
        $this->doctrine = $doctrine;
    }

    public function indexAction() {
        if (false === $this->isGranted('ROLE_GEST_ORG')) {
            throw new AccessDeniedException();
        }
        $em = $this->doctrine->getManager();

        $typeElectIdSession = $this->request->getSession()->get('typeElectIdSession');
        if($typeElectIdSession != null){
            $te_defaultValue = $em->getRepository(RefTypeElection::class)->find($typeElectIdSession);
        }else{
            $te_defaultValue = $em->getRepository(RefTypeElection::class)->find(1);
        }

        $form = $this->createForm(TypeElectionType::class, (empty($te_defaultValue)? null : array('typeElection' => $te_defaultValue)) );
        $formHandler = new TypeElectionHandler($form, $this->request, $em);

        if ($formHandler->process()) {
            $te_defaultValue = $formHandler->getTeDefaultValue();
        }
        $params['form'] =  $form->createView();

        if ($te_defaultValue !=null) {
            $params['organisations'] = $em->getRepository(RefOrganisation::class)->findOrganisationsByRefTypeElection($te_defaultValue->getId());
        } else {
            $this->request->getSession()->getFlashBag()->set('info', 'Aucune organisation proposée car il n\'existe pas de type d\'élection');
            $params['organisations'] = array();
        }

        if ($te_defaultValue!=null) { $this->request->getSession()->set('typeElectIdSession', $te_defaultValue->getId()); }
        $params['isTypeElectionParent'] = $te_defaultValue!=null && $te_defaultValue->getId()== RefTypeElection::ID_TYP_ELECT_PARENT;
        $params['mess_warning']= $this->getParameter('mess_warning');

        return $this->render('organisation/index.html.twig', $params);
    }

    public function modifierOrganisationAction($organisationId = 0) {
        if (false === $this->isGranted('ROLE_GEST_ORG')) {
            throw new AccessDeniedException();
        }

        $em = $this->doctrine->getManager();
        $typeElectIdSession = $this->request->getSession()->get('typeElectIdSession');
        $te_defaultValue = $typeElectIdSession != null ? $em->getRepository(RefTypeElection::class)->find($typeElectIdSession) : null;

        if ($te_defaultValue == null) {
            $messageErreur_te = 'L\'ajout ou la modification d\'une organisation n\'est possible';
            $messageErreur_te .= ' qu\'après la sélection d\'un type d\'élection sur l\'écran de gestion des organisations.';
            throw $this->createNotFoundException($messageErreur_te);
        }

        if ($organisationId == 0) {
            $o_defaultValues = new RefOrganisation($te_defaultValue);
        } else {
            $o_defaultValues = $em->getRepository(RefOrganisation::class)->find($organisationId);
        }

        if ($o_defaultValues == null) {
            throw $this->createNotFoundException('L\'organisation n\'a pas été trouvée.');
        }

        $isReadOnlyFederation = false;
        if( ($o_defaultValues->getTypeElection()!=null)
            and ($o_defaultValues->getTypeElection()->getId()== RefTypeElection::ID_TYP_ELECT_PARENT))  {
            $isReadOnlyFederation = true;
        }

        $form = $this->createFormBuilder($o_defaultValues)
            ->add('libelle', TextType::class, array(
                'label'  => '* Nom de l\'organisation',
                'required' => true,
                'trim' => true,
                'error_bubbling' => true))
            ->add('federation', EntityType::class, array(
                'label' => 'Fédération',
                'multiple' => false,
                'class' => RefFederation::class,
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('f')->orderBy('f.libelle', 'ASC');
                },
                'required' => false,
                'placeholder' => 'Aucune',
                'disabled' => $isReadOnlyFederation,
                'choice_label' => 'libelle'))
            ->add('ordre', IntegerType::class, array(
                'label'  => "* Ordre d'affichage",
                'required' => true,
                'trim' => true,
                'error_bubbling' => true,
                'invalid_message' => 'L\'ordre doit être un entier positif.'))
            ->add('obsolete', CheckboxType::class, array(
                'label' => 'Organisation obsolète',
                'required' => false))
            ->getForm();

        if ($this->request->getMethod() == 'POST') {
            $form->handleRequest($this->request);
            if ($form->isSubmitted() && $form->isValid()) {
                $organisationEnCours = $form->getData();
                $em->persist($organisationEnCours);
                $em->flush();

                $this->request->getSession()->getFlashBag()->set('info', 'Organisation sauvegardée.');
                $this->request->getSession()->set('typeElectIdSession', $organisationEnCours->getTypeElection()->getId());
                return $this->redirect($this->generateUrl('ECECA_organisations'));
            }
        }
        $this->request->getSession()->set('typeElectIdSession', ( ($o_defaultValues->getTypeElection()==null) ? null : $o_defaultValues->getTypeElection()->getId() ));
        return $this->render('organisation/edit.html.twig', array('form' => $form->createView()));
    }
}
