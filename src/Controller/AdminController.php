<?php

namespace App\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AdminController extends AbstractController {

	/**
	 * @Route("/", name="HOMEPAGE")
	 */
	public function indexAction() {
		// if (false === $this->isGranted('ROLE_GEST_CAMP') &&
		// 	false === $this->isGranted('ROLE_GEST_CONTACT') &&
		// 	false === $this->isGranted('ROLE_GEST_ETAB') &&
		// 	false === $this->isGranted('ROLE_GEST_FEDE') &&
		// 	false === $this->isGranted('ROLE_GEST_ORG')) {
		// 	throw new AccessDeniedException();
		// }
		return $this->render('layout.html.twig', []);
	}

}
