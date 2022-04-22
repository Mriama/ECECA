<?php

namespace App\Controller;

use App\Entity\RefAcademie;
use App\Entity\RefContact;
use App\Form\ContactType;
use App\Form\TypeElectionHandler;
use App\Model\ContactModel;
use App\Entity\RefDepartement;
use App\Entity\RefTypeElection;
use App\Form\TypeElectionType;
use App\Utils\EpleUtils;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController {

    private $request;
    private $doctrine;

    public function __construct(RequestStack $request, ManagerRegistry $doctrine) {
        $this->request = $request->getCurrentRequest();
        $this->doctrine = $doctrine;
    }

    public function indexAction() {
        if (false === $this->isGranted('ROLE_GEST_CONTACT')) {
            throw new AccessDeniedException();
        }
        $em = $this->doctrine->getManager();
        if($this->request->getSession()->get('typeElectIdSession') != null){
            $te_defaultValue = $em->getRepository(RefTypeElection::class)->find($this->request->getSession()->get('typeElectIdSession'));
        }else{
            $te_defaultValue = null;
        }

        if (empty($te_defaultValue)) { $te_defaultValue = $em->getRepository(RefTypeElection::class)->find(1); }

        $tz_defaultValue = "";
        $tz_defaultValue_session = $this->request->getSession()->getFlashBag()->get('typeZoneSession');
        if ($tz_defaultValue_session != null && is_array($tz_defaultValue_session)) {
            $tz_defaultValue = $tz_defaultValue_session[0];
        } else {
            $tz_defaultValue = $tz_defaultValue_session;
        }
        if (empty($tz_defaultValue)) {
            $tz_defaultValue = RefAcademie::getNameEntity();
        }

        $form = $this->createForm(TypeElectionType::class, array('typeElection' => $te_defaultValue, 'typeZone'=> $tz_defaultValue) );

        $formHandler = new TypeElectionHandler($form, $this->request, $em);
        if ($formHandler->processGestionContact()) {
            $te_defaultValue = $formHandler->getTeDefaultValue();
            $tz_defaultValue = $formHandler->getTypeZoneDefaultValue();
        }

        $params['form'] =  $form->createView();

        if ($te_defaultValue !=null) {
            /* en fonction des droits utilisateurs :
             * 			- Si administrateur, tous les enregistrements de ref_contact
             * 			- Si une académie, les enregistrements de ref_contact tel que ref_contact.id_zone in liste (
             * 								ref_academie.code,
             * 								tous les ref_departement.numero tel que :
             * 									ref_departement.academie = ref_academie.code de l'utilisateur connecté
             * 							 )
             * 			- Si une département, les enregistrements de ref_contact tel que ref_contact.id_zone in liste (
             * 								tous les ref_departement.numero = ref_departement.numero de l'utilisateur connecté
             * 							 )
             */

            $params['modelContacts'] = $em->getRepository(RefContact::class)->findContactModelsByTypeZoneTypeElection($tz_defaultValue, $te_defaultValue);
        } else {
            $this->request->getSession()->getFlashBag()->set('info', 'Aucun contact proposé car il n\'existe pas de type d\'élection');
            $params['modelContacts'] = array();
        }

        if($tz_defaultValue == RefAcademie::getNameEntity()) { //Académie
            $zoneSansContact = $em->getRepository(RefAcademie::class)
                ->findRefAcademieSansContactByRefTypeElection($te_defaultValue);
        } else { //Département
            $zoneSansContact = $em->getRepository(RefDepartement::class)
                ->findRefDepartementSansContactByRefTypeElection($te_defaultValue);
        }

        $params['isZoneSansContact'] = empty($zoneSansContact) ? false : true;

        if ($te_defaultValue!=null) { $this->request->getSession()->set('typeElectIdSession', $te_defaultValue->getId()); }
        $this->request->getSession()->getFlashBag()->set('typeZoneSession', $tz_defaultValue);
        $params['mess_warning']= $this->getParameter('mess_warning');

        return $this->render('contact/index.html.twig', $params);
    }


    public function modifierContactAction($contactId = 0) {
        if (false === $this->isGranted('ROLE_GEST_CONTACT')) {
            throw new AccessDeniedException();
        }

        $em = $this->doctrine->getManager();

        if($this->request->getSession()->get('typeElectIdSession') != null)
            $te_defaultValue = $em->getRepository(RefTypeElection::class)->find($this->request->getSession()->get('typeElectIdSession'));
        else
            $te_defaultValue = null;

        if ($te_defaultValue == null) {
            $messageErreur_te = 'L\'ajout ou la modification d\' un contact est possible';
            $messageErreur_te .= ' après la sélection d\'un type d\'élection sur l\'écran de gestion des contacts.';
            throw $this->createNotFoundException($messageErreur_te);
        }

        if ($contactId == 0) {
            $c_defaultValues = new RefContact($te_defaultValue);
            $typeZoneSession = $this->request->getSession()->getFlashBag()->get('typeZoneSession');
            if (RefAcademie::getNameEntity() === $typeZoneSession[0]) {
                $zone = $em->getRepository(RefAcademie::class)->findOneBy(array(), array('libelle'=>'ASC'));
            } else {
                $zone = $em->getRepository(RefDepartement::class)->findOneBy(array(), array('libelle'=>'ASC'));
            }
        } else {
            $c_defaultValues = $em->getRepository(RefContact::class)->find($contactId);
        }

        if ($c_defaultValues == null) {
            throw $this->createNotFoundException('Le contact n\'a pas été trouvé.');
        }

        if ($contactId != 0) { $zone = EpleUtils::getZone($em, $c_defaultValues->getIdZone()); }

        if( !($zone instanceof RefAcademie or $zone instanceof RefDepartement) ) {
            throw $this->createNotFoundException('Le contact n\'a pas été trouvé car la zone (académie ou département) est inconnue ('. $c_defaultValues->getIdZone() .').');
        }

        $mContact = new ContactModel($zone, $c_defaultValues);

        $form = $this->createForm(ContactType::class, $mContact);

        if ($this->request->getMethod() == 'POST') {
            $form->handleRequest($this->request);
            if ($form->isSubmitted() && $form->isValid()) {
                $mContactEnCours = $form->getData();

                $contactEnCours = $mContactEnCours->getContact();
                if($mContactEnCours->getDepartement() == null) {
                    $contactEnCours->setIdZone($mContactEnCours->getAcademie()->getCode());
                } else {
                    $contactEnCours->setIdZone($mContactEnCours->getDepartement()->getNumero());
                }

                $em->persist($contactEnCours);
                $em->flush();

                $this->request->getSession()->getFlashBag()->set('info', 'Contact sauvegardé.');
                $this->request->getSession()->set('typeElectIdSession', $contactEnCours->getTypeElection()->getId());
                $this->request->getSession()->getFlashBag()->set('typeZoneSession', ( $mContactEnCours->getDepartement()==null ) ? RefAcademie::getNameEntity() : RefDepartement::getNameEntity());
                return $this->redirect($this->generateUrl('ECECA_contacts'));
            }
        }
        $this->request->getSession()->set('typeElectIdSession', ( ($c_defaultValues->getTypeElection()==null) ? null : $c_defaultValues->getTypeElection()->getId() ));
        $this->request->getSession()->getFlashBag()->set('typeZoneSession', ( $mContact->getDepartement()==null ) ? RefAcademie::getNameEntity() : RefDepartement::getNameEntity());
        return $this->render('contact/edit.html.twig', array('form' => $form->createView()));
    }
}
