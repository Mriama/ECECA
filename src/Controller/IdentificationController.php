<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Psr\Log\LoggerInterface;
use App\Utils\ClearTrustService;


/**** cleartrust ***/
use Monolog\Logger;

class IdentificationController extends AbstractController {
	
	// Type d'élection
	const ECECA = 'ECECA';
	
	const IEN_FONCT_ADM = 'IEN1D';
	const CE_FONCT_ADM = 'DIR';
	
	const ADMIN_LOGIN = 'DGESCO';
	const IEN_LOGIN = 'IEN';
	const CE_LOGIN = 'CE';
	const DE_LOGIN = 'DE';
	const DSDEN_LOGIN = 'DSDEN';
	
	const FR_EDU_RES_DEL = '|/redirectionhub';
	const UAI_LENGTH = 8;


	
	
	/**
	 * @Route("/integ_ececa", name="login")
	 * @Method("POST")
	 */
	public function loginAction(Request $request, LoggerInterface $logger, ClearTrustService $clearTrustService) {
		//dd($request);
		$logger->info('IdentificationController.loginAction - DEBUT');

		$tblSSO = array();
		try
		{
			$logger->info('IdentificationController.loginAction - Appel du service Cleartrust');
			$tblSSO = $clearTrustService->login($request);
		}
		catch (\Exception $e)
		{
			$logger->error('IdentificationController.loginAction - KO :' . $e->getMessage());

			// Redirection vers la page d'accueil avec un message d'erreur
			// retourné par le composant de sécurité interne
			return $this->render('identification/index.html.twig', array('messageKO' => $e->getMessage()));
		}

		$utilisateur = $tblSSO[0];

		// Mise en place du token d'identification
		$token = new UsernamePasswordToken($utilisateur, $utilisateur->getPassword(), 'secured_area');
		$request->getSession()->set('_security_secured_area',  serialize($token));

		//Mise en place de la liste des établissements du périmètre en session
		$request->getSession()->set('lst_uai',$tblSSO[1]);

		//Mise en place de la liste des établissements du périmètre en session
		$request->getSession()->set('lst_numero_departement', $tblSSO[5]);

		// Mise en place de l'url de retour en session
		$url_retour = $tblSSO[4];
		if ( null == $url_retour || 0 == strcmp('', $url_retour))
		{
			$url_retour = $this->container->getParameter("default_url_return");
		}
		$request->getSession()->set('url_retour', $url_retour);

		// Mise en session du type d'élection choisi
		$request->getSession()->set('type_elec', $tblSSO[2]);

		// Mise en session du mail de l'utilisateur
		$request->getSession()->set('cte_mail', $tblSSO[3]);

		$logger->info('IdentificationController.loginAction - FIN');

		// Point d'entrée de l'application
		// YME - 167304
		return $this->redirect($this->generateUrl('EPLEElectionBundle_homepage'));

	}
	
	public function logoutAction(\Symfony\Component\HttpFoundation\Request $request) {
		// Efface le token, détruit la session et redirige vers l'url de retour pour une identification cleartrust
		$logger = $this->get("logger");
		$logger->info("session logout :" . $request->getSession()->getId());
		$url_retour = $request->getSession()->get('url_retour');
		$logger->info("url de retour :" . $url_retour);
		$this->get('security.context')->setToken(null);
		$request->getSession()->invalidate();
		return $this->redirect($url_retour);
	}
}
