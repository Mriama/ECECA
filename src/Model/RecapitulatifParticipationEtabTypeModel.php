<?php
namespace App\Model;
use Symfony\Component\Validator\ExecutionContextInterface;

class RecapitulatifParticipationEtabTypeModel{
	
	/**
	 * campagne
	 * @var \App\Entity\EleCampagne
	 *
	 */
	private $campagne;

	/**
	 * niveau
	 *@var string
	 */
	private $niveau;
	
	/**
	 * typeEtab
	 * @var \App\Entity\RefTypeEtablissement
	 *
	 */
	private $typeEtablissement;
	
	/**
	 * typeElection
	 * @var \App\Entity\RefTypeElection
	 *
	 */
	private $typeElection;
	
	public function __construct(
			\App\Entity\RefTypeElection $typeElection=null,
			\App\Entity\EleCampagne $campagne=null,
			$niveau=null,
			\App\Entity\RefTypeEtablissement $typeEtab=null
			) {
		
		if(!empty($typeElection)) $this->typeElection = $typeElection;
		if(!empty($campagne)) $this->campagne = $campagne;
		if(!empty($niveau)) $this->niveau = $niveau;
		if(!empty($typeEtab)) $this->typeEtablissement = $typeEtab;
		
	
	}
	public function getCampagne(){
		return $this->campagne;
	}
	public function setCampagne(\App\Entity\EleCampagne $campagne=null){
		$this->campagne = $campagne;
		return $this;
	}
	
	public function getNiveau(){
		return $this->niveau;
	}
	public function setNiveau($niveau){
		$this->niveau = $niveau;
		return $this;
	}
	public function getTypeEtablissement(){
		return $this->typeEtablissement;
	}
	public function setTypeEtablissement(\App\Entity\RefTypeEtablissement $typeEtablissement=null){
		$this->typeEtablissement = $typeEtablissement;
		return $this;
	}
	public function getTypeElection(){
		return $this->typeElection;
	}
	public function setTypeElection(\App\Entity\RefTypeElection $typeElection){
		$this->typeElection = $typeElection;
		
		print_r($typeElection);
		return $this;
	}
	
	
	
	
	
}