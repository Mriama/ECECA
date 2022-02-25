<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController {

	public function indexAction() {
		if (false === $this->get('security.context')->isGranted('ROLE_GEST_CAMP') and
			false === $this->get('security.context')->isGranted('ROLE_GEST_CONTACT') and
			false === $this->get('security.context')->isGranted('ROLE_GEST_ETAB') and
			false === $this->get('security.context')->isGranted('ROLE_GEST_FEDE') and
			false === $this->get('security.context')->isGranted('ROLE_GEST_ORG')) {
			throw new AccessDeniedException();
		}
		return $this->render('EPLEAdminBundle::layout.html.twig', array());
	}

}
