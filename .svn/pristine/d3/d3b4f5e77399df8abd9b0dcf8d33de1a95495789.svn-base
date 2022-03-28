<?php

namespace App\Model;
use Symfony\Component\Validator\ExecutionContextInterface;

class ZoneEtabModel {
	
	/**
	 * academie
	 * @var \App\Entity\RefAcademie
	 *
	 */
	private $academie;
	
	/**
	 * departement
	 * @var \App\Entity\RefDepartement
	 *
	 */
	private $departement;
	
	/**
	 * typeEtablissement
	 * @var \App\Entity\RefTypeEtablissement
	 *
	 */
	private $typeEtablissement;

	/**
	 * choix_etab
	 * @var boolean
	 *
	 */
	private $choixEtab;
	
	/**
	 * commune
	 * @var \App\Entity\RefCommune
	 *
	 */
	private $commune;
	
	/**
	 * etablissement
	 * @var \App\Entity\RefEtablissement
	 *
	 */
	private $etablissement;
	

	public function __construct(\App\Entity\RefTypeEtablissement $typeEtab=null, 
								$choixEtab=false, 
								\App\Entity\RefCommune $commune=null, 
								\App\Entity\RefEtablissement $etablissement=null) {
		if(!empty($typeEtab)) $this->typeEtablissement = $typeEtab;
		if(!empty($choixEtab)) $this->choixEtab = $choixEtab;
		if(!empty($commune)) $this->commune = $commune;
		if(!empty($etablissement)) $this->etablissement = $etablissement;
		
	}
	
	public function setAcademie(\App\Entity\RefAcademie $academie=null) {
		$this->academie = $academie;
	}
	
	public function getAcademie() {
		return $this->academie;
	}
	
	public function setDepartement(\App\Entity\RefDepartement $departement=null) {
		$this->departement = $departement;
	}
	
	public function getDepartement() {
		return $this->departement;
	}
	
	public function setTypeEtablissement(\App\Entity\RefTypeEtablissement $typeEtab=null) {
		$this->typeEtablissement = $typeEtab;
	}
	
	public function getTypeEtablissement() {
		return $this->typeEtablissement;
	}
	
	public function setChoixEtab($choixEtab=false) {
		$this->choixEtab = $choixEtab;
	}
	
	public function getchoixEtab() {
		return $this->choixEtab;
	}

	public function setEtablissement(\App\Entity\RefEtablissement $etab=null) {
		$this->etablissement = $etab;
	}
	
	public function getEtablissement() {
		return $this->etablissement;
	}
	
	public function setCommune(\App\Entity\RefCommune $commune=null) {
		$this->commune = $commune;
	}
	
	public function getCommune() {
		return $this->commune;
	}
	
	public function isEtablissementValid(ExecutionContextInterface $context) {		
		if ($this->choixEtab == true && $this->etablissement == null) {
			$context->addViolation('Veuillez sélectionner un établissement dans le cas où vous effectuez une recherche par établissement.', array($this->etablissement), null);
		}
	}
	
}
