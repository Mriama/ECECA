<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EleAlerte
 *
 * @ORM\Table(name="ele_alerte", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\EleAlerteRepository")
 */
class EleAlerte {
	/**
	 *
	 * @var integer 
	 * 
	 * 		@ORM\Column(name="id", type="integer")
	 *      @ORM\Id
	 *      @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;
	
	/**
	 *
	 * @var App\Entity\EleEtablissement
	 * 		@ORM\ManyToOne(targetEntity="App\Entity\EleEtablissement")
	 *      @ORM\JoinColumn(name="id_ele_etablissement", referencedColumnName="id", onDelete="CASCADE")
	 */
	private $electionEtab;
	
	/**
     *
     * @var App\Entity\RefTypeAlerte
     * 		@ORM\ManyToOne(targetEntity="App\Entity\RefTypeAlerte")
     *      @ORM\JoinColumn(name="code_type_alerte", referencedColumnName="code")
     */
    private $typeAlerte;
    
	public function getId() {
		return $this->id;
	}
	
	public function setId($id) {
		$this->id = $id;
		return $this;
	}
	
	public function getElectionEtab() {
		return $this->electionEtab;
	}
	
	public function setElectionEtab(\App\Entity\EleEtablissement $electionEtab) {
		$this->electionEtab = $electionEtab;
		return $this;
	} 
	
	public function getTypeAlerte() {
		return $this->typeAlerte;
	} 
	
	public function setTypeAlerte(\App\Entity\RefTypeAlerte $typeAlerte) {
		$this->typeAlerte = $typeAlerte;
		return $this;
	}
	
}
