<?php

namespace App\Model;
use Symfony\Component\Validator\ExecutionContextInterface;

class EtablissementModel {

	/**
	 * etab
	 * @var \App\Entity\RefEtablissement
	 *
	 */
	private $etab;
	
	/**
	 * commune
	 * @var \App\Entity\RefCommune
	 *
	 */
	private $commune;
	
	private $flagAddCommune;

	public function __construct($etab) {
		$this->etab = $etab; 
		$this->flagAddCommune = 'false';
	}

	public function setEtab(\App\Entity\RefEtablissement $etab) {
		$this->etab = $etab;
	}
	
	public function getEtab() {
		return $this->etab;
	}
	
	public function setCommune(\App\Entity\RefCommune $commune) {
		$this->commune = $commune;
	}
	
	public function getCommune() {
		return $this->commune;
	}
	
	public function setFlagAddCommune($val) {
		$this->flagAddCommune = $val;
	}
	
	public function getFlagAddCommune() {
		return $this->flagAddCommune;
	}
	
	public function isCommuneValid(ExecutionContextInterface $context) {

		if ($this->flagAddCommune === 'true' && $this->etab->getCommune()->getLibelle() == null) {
			$context->addViolation('Veuillez saisir un libellé pour la création d\'une nouvelle commune.', array($this->commune), null);
		}
		if ($this->flagAddCommune === 'false' && $this->commune == null) {
			$context->addViolation('Veuillez choisir une commune ou créer une nouvelle commune.', array($this->commune), null);
		}
	}
	
}
