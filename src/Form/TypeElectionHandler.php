<?php

namespace App\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

class TypeElectionHandler {
	protected $form;
	protected $request;
	protected $em;
	private $teDefaultValue = null;
	private $typeZoneDefaultValue = null;

	public function __construct(Form $form, Request $request, EntityManager $em){
		$this->form		= $form;
		$this->request	= $request;
		$this->em      	= $em;
	}

	public function process() {
		if( $this->request->getMethod() == 'POST' ) {
			
			$this->form->bind($this->request);
			
			if( $this->form->isValid() ) {
				$te_formArray = $this->form->getData();
				$this->onSuccess($te_formArray["typeElection"]);
				return true;					
			}
		}	
		return false;
	}
	
	public function processGestionContact() {
		if( $this->request->getMethod() == 'POST' ) {
				
			$this->form->bind($this->request);
				
			if( $this->form->isValid() ) {
				$formArray = $this->form->getData();
				$this->typeZoneDefaultValue = ( isset($formArray["typeZone"]) ) ? $formArray["typeZone"] : null;
				$this->onSuccess($formArray["typeElection"]);
				return true;
			}
		}
		return false;
	}

	public function getTeDefaultValue() {
		return $this->teDefaultValue;
	}
	
	public function getTypeZoneDefaultValue() {
		return $this->typeZoneDefaultValue;
	}
	
	public function onSuccess(\App\Entity\RefTypeElection $typeElectionForm) {
		$this->teDefaultValue = $this->em->getRepository('EPLEElectionBundle:RefTypeElection')->find($typeElectionForm->getId());
	}

}
