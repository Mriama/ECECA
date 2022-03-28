<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\BrowserKit\Response;
use Doctrine\Tests\DBAL\Types\VarDateTimeTest;

use Symfony\Component\Security\Core\SecurityContext;
use \App\Entity\RefUser;
use \App\Entity\RefTypeElection;
use \App\Entity\RefProfil;


class RechercheEtablissementController extends AbstractController{

    //Creation du formulaire de recherche
    public function indexAction(){
        //Verification de droit d'acces (Si ce Role est attribue)
        if (false === $this->get('security.context')->isGranted('ROLE_RECH_UAI')) {
            throw new AccessDeniedException();
        }

        $form = $this->creationFormulaire();

        return $this->render('EPLEAdminBundle:RechercheEtablissement:indexRechercheEtablissement.html.twig',
            array('form' => $form->createView()
            ));

    }


    public function affichageEtablissementsAction(Request $request){

        //Verification de droit d'acces (Si ce Role est attribue a profil du user connecte)
        if (false === $this->get('security.context')->isGranted('ROLE_RECH_UAI')) {
            throw new AccessDeniedException();
        }

        $isDegesco = $this->get('security.context')->getToken()->getUser()->getProfil()->getCode() === RefProfil::CODE_PROFIL_DGESCO;

        /*Création du formulaire de recherche*/
        $form = $this->creationFormulaire();

        if($request->getMethod() == 'POST'){
            $form->bind($request);
            if($form->isValid()){
                $donneesTransmies = $form->getData();

                // old IE n'interprete pas la propriété required
                if (empty($donneesTransmies["uai"])) {
                    return $this->render('EPLEAdminBundle:RechercheEtablissement:resultatRecherche.html.twig', array(
                        'form' 			=> $form->createView(),
                        'messageUaiVide' => true
                    ));
                }
            }
        }

        //Recherche etablissement
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('EPLEElectionBundle:RefEtablissement');
        $etablissementTrouve = $repository->findOneBy(array('uai' => $donneesTransmies));


        /*Verification de l'UAI saisie*/
        if(!$etablissementTrouve){
            return $this->render('EPLEAdminBundle:RechercheEtablissement:resultatRecherche.html.twig', array(
                'form' => $form->createView()
            ));
        } else {
            $dansPerimetre = $user->isEtabInScopeForRechercheUAI($etablissementTrouve);
            if($dansPerimetre == true){

                return $this->render('EPLEAdminBundle:RechercheEtablissement:resultatRecherche.html.twig', array(
                    'form' 			=> $form->createView(),
                    'etablissement' => $etablissementTrouve,
                    'canDisableEtab' => $isDegesco,
                ));

            } else {
                return $this->render('EPLEAdminBundle:RechercheEtablissement:resultatRecherche.html.twig', array(
                    'form' 		    => $form->createView(),
                    'etablissement' => $etablissementTrouve,
                    'dansPerimetre'  => $dansPerimetre,
                    'canDisableEtab' => $isDegesco,
                ));
            }
        }
    }

    public function creationFormulaire(){
        $form = $this->createFormBuilder()
            ->add('uai', 'text', array(
                'label' => '*Numéro UAI/RNE',
                'required' => true,
                'trim' => true,
                'max_length' => '8'
            ))->getForm();
        return $form;

    }

    public function ouvrirFermerEtablissementAction(Request $request)
    {
        //Verification de droit d'acces (Si ce Role est attribue a profil du user connecte)
        if (false === $this->get('security.context')->isGranted('ROLE_RECH_UAI')) {
            throw new AccessDeniedException();
        }

        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->getProfil()->getCode() !== RefProfil::CODE_PROFIL_DGESCO) {
            throw new AccessDeniedException();
        }
        $em = $this->getDoctrine()->getManager();
        $uai = null;

        if ($request->getMethod() == 'POST') {
            $uai = $request->get('uai');
        }

        $form = $this->creationFormulaire();
        $form->setData(array('uai' => $uai));
        $etablissementTrouve = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findOneBy(array('uai' => $uai));
        $inScope =  $user->isEtabInScopeForRechercheUAI($etablissementTrouve);

        if ($etablissementTrouve && $inScope) {
            //Fermeture etablissement
            if ($etablissementTrouve->getActif()) {
                $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne(RefTypeElection::ID_TYP_ELECT_PARENT);
                $etablissementTrouve->setActif(false);
                $etablissementTrouve->setDateFermeture(new \DateTime($campagne->getAnneeDebut() . "-08-31"));
            }

            //Réouverture établissement
            else {
                $etablissementTrouve->setActif(true);
                $etablissementTrouve->setDateFermeture(null);
            }
            $em->persist($etablissementTrouve);
            $em->flush();
        }

        return $this->render('EPLEAdminBundle:RechercheEtablissement:resultatRecherche.html.twig', array(
            'form' 			=> $form->createView(),
            'etablissement' => $etablissementTrouve,
            'dansPerimetre'  => $inScope,
            'canDisableEtab' => true,
        ));
    }

} //fin de la classe


?>