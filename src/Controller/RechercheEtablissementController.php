<?php
namespace App\Controller;

use App\Entity\EleCampagne;
use App\Entity\RefEtablissement;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use \App\Entity\RefTypeElection;
use \App\Entity\RefProfil;


class RechercheEtablissementController extends AbstractController{

    private $request;
    private $doctrine;

    public function __construct(RequestStack $request, ManagerRegistry $doctrine) {
        $this->request = $request->getCurrentRequest();
        $this->doctrine = $doctrine;
    }

    public function indexAction(){
        //Verification de droit d'acces (Si ce Role est attribue)
        if (false === $this->isGranted('ROLE_RECH_UAI')) {
            throw new AccessDeniedException();
        }
        $form = $this->creationFormulaire();

        return $this->render('rechercheEtablissement/indexRechercheEtablissement.html.twig',
            array('form' => $form->createView()
            ));
    }


    public function affichageEtablissementsAction(){

        //Verification de droit d'acces (Si ce Role est attribue a profil du user connecte)
        if (false === $this->isGranted('ROLE_RECH_UAI')) {
            throw new AccessDeniedException();
        }

        $isDegesco = $this->getUser()->getProfil()->getCode() === RefProfil::CODE_PROFIL_DGESCO;

        /*Création du formulaire de recherche*/
        $form = $this->creationFormulaire();

        if($this->request->getMethod() == 'POST'){
            $form->handleRequest($this->request);
            if($form->isSubmitted() && $form->isValid()){
                $donneesTransmies = $form->getData();

                // old IE n'interprete pas la propriété required
                if (empty($donneesTransmies["uai"])) {
                    return $this->render('rechercheEtablissement/resultatRecherche.html.twig', array(
                        'form' 			=> $form->createView(),
                        'messageUaiVide' => true
                    ));
                }
            }
        }

        //Recherche etablissement
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $repository = $em->getRepository(RefEtablissement::class);
        $etablissementTrouve = $repository->findOneBy(array('uai' => $donneesTransmies));


        /*Verification de l'UAI saisie*/
        if(!$etablissementTrouve){
            return $this->render('rechercheEtablissement/resultatRecherche.html.twig', array(
                'form' => $form->createView()
            ));
        } else {
            $dansPerimetre = $user->isEtabInScopeForRechercheUAI($etablissementTrouve);
            if($dansPerimetre == true){

                return $this->render('rechercheEtablissement/resultatRecherche.html.twig', array(
                    'form' 			=> $form->createView(),
                    'etablissement' => $etablissementTrouve,
                    'canDisableEtab' => $isDegesco,
                ));

            } else {
                return $this->render('rechercheEtablissement/resultatRecherche.html.twig', array(
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
            ->add('uai', TextType::class, array(
                'label' => '*Numéro UAI/RNE',
                'required' => true,
                'trim' => true,
                'attr' => ['maxlength' => 8],
            ))->getForm();
        return $form;

    }

    public function ouvrirFermerEtablissementAction()
    {
        //Verification de droit d'acces (Si ce Role est attribue a profil du user connecte)
        if (false === $this->isGranted('ROLE_RECH_UAI')) {
            throw new AccessDeniedException();
        }

        $user = $this->getUser();
        if ($user->getProfil()->getCode() !== RefProfil::CODE_PROFIL_DGESCO) {
            throw new AccessDeniedException();
        }
        $em = $this->doctrine->getManager();
        $uai = null;

        if ($this->request->getMethod() == 'POST') {
            $uai = $this->request->get('uai');
        }

        $form = $this->creationFormulaire();
        $form->setData(array('uai' => $uai));
        $etablissementTrouve = $em->getRepository(RefEtablissement::class)->findOneBy(array('uai' => $uai));
        $inScope =  $user->isEtabInScopeForRechercheUAI($etablissementTrouve);

        if ($etablissementTrouve && $inScope) {
            //Fermeture etablissement
            if ($etablissementTrouve->getActif()) {
                $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne(RefTypeElection::ID_TYP_ELECT_PARENT);
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

        return $this->render('rechercheEtablissement/resultatRecherche.html.twig', array(
            'form' 			=> $form->createView(),
            'etablissement' => $etablissementTrouve,
            'dansPerimetre'  => $inScope,
            'canDisableEtab' => true,
        ));
    }

} //fin de la classe


?>