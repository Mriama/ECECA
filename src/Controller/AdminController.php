<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AdminController extends AbstractController {
	public function indexAction() {
		if (false === $this->container->get('security.authorization_checker')->isGranted('ROLE_GEST_CAMP') &&
			false === $this->container->get('security.authorization_checker')->isGranted('ROLE_GEST_CONTACT') &&
			false === $this->container->get('security.authorization_checker')->isGranted('ROLE_GEST_ETAB') &&
			false === $this->container->get('security.authorization_checker')->isGranted('ROLE_GEST_FEDE') &&
			false === $this->container->get('security.authorization_checker')->isGranted('ROLE_GEST_ORG')) {
			throw new AccessDeniedException();
		}
		return $this->render('layout.html.twig', []);
	}

}
