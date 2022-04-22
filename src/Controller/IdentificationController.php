<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use App\Utils\ClearTrustService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class IdentificationController extends AbstractController {

    public function loginAction(Request $request,
                                TokenStorageInterface $tokenStorage,
                                AuthenticationManagerInterface $authenticationManager,
                                LoggerInterface $connexionLogger,
                                ClearTrustService $clearTrustService,
                                ParameterBagInterface $params) {
        $connexionLogger->info('IdentificationController.loginAction - DEBUT');

        $tblSSO = array();
        try
        {
            $connexionLogger->info('IdentificationController.loginAction - Appel du service Cleartrust');

            $tblSSO = $clearTrustService->login($request);
        }
        catch (\Exception $e)
        {
            $connexionLogger->error('IdentificationController.loginAction - KO :' . $e->getMessage());

            // Redirection vers la page d'accueil avec un message d'erreur
            // retourné par le composant de sécurité interne
            return $this->render('identification/index.html.twig', array('messageKO' => $e->getMessage()));
        }

        $user = $tblSSO[0];

        // Mise en place du token d'identification
        //Handle getting or creating the user entity likely with a posted form
        // The third parameter "main" can change according to the name of your firewall in security.yml
        $token = new UsernamePasswordToken($user,'main', $user->getRoles());
        $authenticatedToken = $authenticationManager->authenticate($token);
        $tokenStorage->setToken($authenticatedToken);
        $request->getSession()->set('_security_main', serialize($token));

        //Mise en place de la liste des établissements du périmètre en session
        $request->getSession()->set('lst_uai',$tblSSO[1]);
        //Mise en place de la liste des établissements du périmètre en session
        $request->getSession()->set('lst_numero_departement', $tblSSO[5]);

        // Mise en place de l'url de retour en session
        $url_retour = $tblSSO[4];
        if ( null == $url_retour || 0 == strcmp('', $url_retour))
        {
            $url_retour = $params->get("default_url_return");
        }
        //dd('test');
        $request->getSession()->set('url_retour', $url_retour);
        // Mise en session du type d'élection choisi
        $request->getSession()->set('type_elec', $tblSSO[2]);

        // Mise en session du mail de l'utilisateur
        $request->getSession()->set('cte_mail', $tblSSO[3]);

        $connexionLogger->info('IdentificationController.loginAction - FIN');
        // Point d'entrée de l'application
        // YME - 167304
        return $this->redirectToRoute('ECECA_homepage');

    }

    public function logoutAction(Request $request, TokenStorageInterface $tokenStorage, LoggerInterface $connexionLogger) {
        // Efface le token, détruit la session et redirige vers l'url de retour pour une identification cleartrust
        $connexionLogger->info("session logout :" . $request->getSession()->getId());
        $url_retour = $request->getSession()->get('url_retour');
        $connexionLogger->info("url de retour :" . $url_retour);
        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();
        return $this->redirect($url_retour);
    }
}
