<?php

namespace App\Form;

use Symfony\Component\Httpfoundation\Response;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use App\Entity\RefEtablissement;
use App\Entity\RefCommune;

class EtablissementHandler {
	protected $form;
	protected $request;
	protected $em;

	public function __construct(Form $form, Request $request, EntityManager $em){
		$this->form		= $form;
		$this->request	= $request;
		$this->em      	= $em;
	}

	public function process($ajout) {
		
		if ($this->request->getMethod() == 'POST') {

			$this->form->bind($this->request);
			
			if ($this->form->isValid()) {
				
				$mon_etablissement = $this->form->getData();
				
				$mon_etablissement->getEtab()->setUai(strtoupper($mon_etablissement->getEtab()->getUai()));
				$commune = $mon_etablissement->getCommune();
				$libelle_commune = strtoupper($mon_etablissement->getEtab()->getCommune()->getLibelle());
				$cp_commune = $mon_etablissement->getEtab()->getCommune()->getCodePostal();
				$departement = $mon_etablissement->getEtab()->getCommune()->getDepartement();

				// lorsque l'on est en modification d'établissement on sépare l'entité commune de l'établissement 
				if (!$ajout) {
					$ancienne_commune = $mon_etablissement->getEtab()->getCommune();
					if (!empty($ancienne_commune)) {
						$this->em->detach($ancienne_commune);
					}
				}

				// on test le flag permettant de savoir si on est en creation de nouvelle commune ou en selection de commune existante
				if ($mon_etablissement->getFlagAddCommune() === 'true') {
					
					$commune_existante = $this->em->getRepository('EPLEElectionBundle:RefCommune')->findOneBy(array('libelle' => $libelle_commune, 'codePostal' => $cp_commune));
					
					if (empty($commune_existante)) {	
						$ma_nouvelle_commune = new RefCommune();
						$ma_nouvelle_commune->setLibelle($libelle_commune);
						$ma_nouvelle_commune->setCodePostal($cp_commune);
						$ma_nouvelle_commune->setDepartement($departement);

						$this->em->persist($ma_nouvelle_commune);
						
						$mon_etablissement->getEtab()->setCommune($ma_nouvelle_commune);
					}
					else {						
						$mon_etablissement->getEtab()->setCommune($commune_existante);
					}
				} else {
					// cas particulier dans le cas ou la commune selectionnée est la meme que celle associée à l'établissement
					if ($commune->getLibelle() != $libelle_commune || $commune->getCodePostal() != $cp_commune)
						$mon_etablissement->getEtab()->setCommune($commune);
					else {
						$this->em->persist($commune);
						$this->em->flush();
						$mon_etablissement->getEtab()->setCommune($commune);
					}	
				}
				
				$this->onSuccess($mon_etablissement->getEtab());
				return true;					
			} else {
				// lorsque que l'on est en création et que les données sont invalides on remet à 0 pour rester en création
				if ($ajout) {
					$mon_etablissement = $this->form->getData();
					$mon_etablissement->getEtab()->setUai('0');
				}
				return false;
			}
			
		}	
		return false;
	}
	
	public function onSuccess(\App\Entity\RefEtablissement $etablissementForm) {
		$this->em->persist($etablissementForm);
		$this->em->flush();
		
		// on efface toute les communes sans etablissment de la base
		$nbCommuneDelete = $this->em->getRepository('EPLEElectionBundle:RefCommune')->deleteCommuneSansEtab();
	}
}