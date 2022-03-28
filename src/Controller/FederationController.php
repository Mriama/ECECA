<?php
namespace App\Controller;

use App\Entity\EleResultat;
use App\Entity\RefFederation;
use App\Entity\RefOrganisation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\BaseController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FederationController extends BaseController
{

    /**
     * @Route("federations", name="federations")
     */
    
    public function indexAction(ParameterBagInterface $parameters)
    {
        // if (false === $this->get('security.authorization_checker')->isGranted('ROLE_GEST_FEDE')) {
        //     throw new AccessDeniedException();
        // }
        $user = $this->getUser();
        $lstFederations = $this->getDoctrine()->getManager()
            ->getRepository(RefFederation::class)
            ->findBy(array(), array(
            "libelle" => "ASC"
        ));
        $mess_warning = $parameters->get('mess_warning');
        
        return $this->render('Federation/index.html.twig', array(
            'federations' => $lstFederations,
            'mess_warning' => $mess_warning
        ));
    }

    public function modifierFederationAction(Request $request, $federationId = 0)
    {
        if (false === $this->get('security.context')->isGranted('ROLE_GEST_FEDE')) {
            throw new AccessDeniedException();
        }
        
        $em = $this->getDoctrine()->getManager();
        
        if ($federationId == 0) {
            $f_defaultValues = new RefFederation();
        } else {
            $f_defaultValues = $em->getRepository(RefFederation::class)->find($federationId);
        }
        
        if ($f_defaultValues == null) {
            throw $this->createNotFoundException('La fédération n\'a pas été trouvée.');
        }
        
        $form = $this->createFormBuilder($f_defaultValues)
            ->add('libelle', 'text', array(
            'label' => '* Nom de la fédération',
            'required' => true,
            'trim' => true,
            'error_bubbling' => true
        ))
            ->getForm();
        
        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $federationEnCours = $form->getData();
                $em->persist($federationEnCours);
                $em->flush();
                
                $this->get('session')
                    ->getFlashBag()
                    ->set('info', 'Fédération sauvegardée.');
                return $this->redirect($this->generateUrl('EPLEAdminBundle_federations'));
            }
        }
        
        return $this->render('federation/edit.html.twig', array(
            'form' => $form->createView()
        ));
    }


	public function supprimerFederationAction($federationId) {
		if (false === $this->get('security.context')->isGranted('ROLE_GEST_FEDE')) {
			throw new AccessDeniedException();
		}

		$em = $this->getDoctrine()->getManager();
		$federation = $em->getRepository(RefFederation::class)->find($federationId);
		
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

		$this->get('session')->getFlashBag()->set('info', $messageInfo);
		
		return $this->redirect($this->generateUrl('EPLEAdminBundle_federations'));
	}
}
