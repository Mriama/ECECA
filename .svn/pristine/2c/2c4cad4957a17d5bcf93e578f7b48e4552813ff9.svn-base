<?php

namespace App\Model;
use Symfony\Component\Validator\ExecutionContextInterface;

class CampagneZoneEtabModel extends ZoneEtabModel{

	/**
	 * campagne
	 * @var \App\Entity\EleCampagne
	 *
	 */
	private $campagne;
	
	/**
	 * typeElection
	 * @var \App\Entity\RefTypeElection
	 *
	 */
	private $typeElection;
	
	public function __construct(\App\Entity\RefTypeElection $typeElection=null,
								\App\Entity\RefTypeEtablissement $typeEtab=null, 
								$choixEtab=false, 
								\App\Entity\RefCommune $commune=null, 
								\App\Entity\RefEtablissement $etablissement=null) {
		parent::__construct($typeEtab,
							$choixEtab,
							$commune,
							$etablissement
							);
		if(!empty($typeElection)) $this->typeElection = $typeElection;
	}
	
	public function setCampagne(\App\Entity\EleCampagne $campagne=null) {
		$this->campagne = $campagne;
	}
	
	public function getCampagne() {
		return $this->campagne;
	}
	
	public function setTypeElection(\App\Entity\RefTypeElection $typeElection=null) {
		$this->typeElection = $typeElection;
	}
	
	public function getTypeElection() {
		return $this->typeElection;
	}
	
}
