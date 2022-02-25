<?php

namespace App\Model;
use Symfony\Component\Validator\ExecutionContextInterface;

use App\Entity\EleEtablissement;

class ResultatZoneEtabModel extends ZoneEtabModel {

	/**
	 * etatSaisie
	 * @var array
	 */
	private $etatSaisie;
	
	public function __construct(\App\Entity\RefTypeElection $typeElection=null,
								\App\Entity\RefTypeEtablissement $typeEtab=null,
								$etatSaisie = array(EleEtablissement::ETAT_VALIDATION),
								$choixEtab=false, 
								\App\Entity\RefCommune $commune=null, 
								\App\Entity\RefEtablissement $etablissement=null) {
		parent::__construct($typeEtab,
							$choixEtab,
							$commune,
							$etablissement
		);
		
		$this->etatSaisie = $etatSaisie;
	}
	
	public function setEtatSaisie($etatSaisie = array()) {
		$this->etatSaisie = $etatSaisie;
	}
	
	public function getEtatSaisie() {
		return $this->etatSaisie;
	}
		
}
