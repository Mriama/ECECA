<?php
namespace App\Controller;

use App\Entity\EleResultat;
use App\Entity\RefFederation;
use App\Entity\RefOrganisation;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class FederationController extends AbstractController
{
    private $request;
    private $doctrine;

    public function __construct(RequestStack $request, ManagerRegistry $doctrine) {
        $this->request = $request->getCurrentRequest();
        $this->doctrine = $doctrine;
    }

    public function indexAction()
    {
        if (false === $this->isGranted('ROLE_GEST_FEDE')) {
            throw new AccessDeniedException();
        }
        $lstFederations = $this->doctrine->getManager()
            ->getRepository(RefFederation::class)
            ->findBy(array(), array(
                "libelle" => "ASC"
            ));
        $mess_warning = $this->getParameter('mess_warning');

        return $this->render('federation/index.html.twig', array(
            'federations' => $lstFederations,
            'mess_warning' => $mess_warning
        ));
    }

    public function modifierFederationAction($federationId = 0)
    {
        if (false === $this->isGranted('ROLE_GEST_FEDE')) {
            throw new AccessDeniedException();
        }

        $em = $this->doctrine->getManager();

        if ($federationId == 0) {
            $f_defaultValues = new RefFederation();
        } else {
            $f_defaultValues = $em->getRepository(RefFederation::class)->find($federationId);
        }

        if ($f_defaultValues == null) {
            throw $this->createNotFoundException('La fédération n\'a pas été trouvée.');
        }

        $form = $this->createFormBuilder($f_defaultValues)
            ->add('libelle', TextType::class, array(
                'label' => '* Nom de la fédération',
                'required' => true,
                'trim' => true,
                'error_bubbling' => true
            ))
            ->getForm();

        if ($this->request->getMethod() == 'POST') {
            $form->handleRequest($this->request);
            if ($form->isSubmitted() && $form->isValid()) {
                $federationEnCours = $form->getData();
                $em->persist($federationEnCours);
                $em->flush();

                $this->request->getSession()
                    ->getFlashBag()
                    ->set('info', 'Fédération sauvegardée.');
                return $this->redirect($this->generateUrl('ECECA_federations'));
            }
        }

        return $this->render('federation/edit.html.twig', array(
            'form' => $form->createView()
        ));
    }


    public function supprimerFederationAction($federationId) {
        if (false === $this->isGranted('ROLE_GEST_FEDE')) {
            throw new AccessDeniedException();
        }

        $em = $this->doctrine->getManager();
        $federation = $federationId != null ? $em->getRepository(RefFederation::class)->find($federationId) : null;

        if (null == $federation) {
            throw $this->createNotFoundException('La fédération n\'a pas été trouvée.');
        }

        // Récupération de la liste des organisations rattachées à la fédération
        $liste_organisations = $em->getRepository(RefOrganisation::class)->findBy(array('federation' => $federation->getId()));

        //Indique si l'on peut supprimer la fédération ou pas
        $canSupprimer = true;
        foreach($liste_organisations as $org){
            // Recherche de résultats
            if (count($em->getRepository(EleResultat::class)->findBy(array('organisation' => $org->getId()))) > 0){
                $canSupprimer = false;
                break;
            }
        }

        // La fédération peut être supprimmée
        if($canSupprimer){
            foreach($liste_organisations as $org){
                $org->setFederation(null);
                $em->persist($org);
                $reinitOrgs[] = $org->getLibelle();
            }

            $em->remove($federation);
            $em->flush();

            $messageInfo = 'Fédération supprimée.';
            if (isset($reinitOrgs)) {
                $messageInfo .= '<br />Réinitialisation de la fédération des organisations suivantes :<br />'. implode(', ', $reinitOrgs);
            }

        } else{
            $messageInfo = 'Suppresion impossible: des résultats ont été saisis pour une organisation de cette fédération';
        }

        $this->request->getSession()->getFlashBag()->set('info', $messageInfo);

        return $this->redirect($this->generateUrl('ECECA_federations'));
    }
}
