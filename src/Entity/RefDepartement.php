<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RefDepartement
 *
 * @ORM\Table(name="ref_departement", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\RefDepartementRepository", readOnly=true)
 */
class RefDepartement {
	/**
	 *
	 * @var integer 
	 * 		@ORM\Column(name="numero", type="string", length=2)
	 *      @ORM\Id
	 */
	protected $numero;
	
	/**
	 *
	 * @var string 
	 * 		@ORM\Column(name="libelle", type="string", length=255, nullable=false)
	 */
	protected $libelle;
	
	/**
	 *
	 * @var App\Entity\RefAcademie 
	 * 		@ORM\ManyToOne(targetEntity="App\Entity\RefAcademie", inversedBy="departements")
	 *      @ORM\JoinColumn(name="academie", referencedColumnName="code")
	 */
	protected $academie;
	
	/**
	 * Set numero
	 *
	 * @param string $numero        	
	 * @return RefDepartement
	 */
	public function setNumero($numero){
		$this->numero = $numero;
		
		return $this;
	}
	
	/**
	 * Get Numero
	 *
	 * @return integer
	 */
	public function getNumero(){
		return $this->numero;
	}
	
	/**
	 * Get idZone = numero
	 *
	 * @return string
	 */
	public function getIdZone(){
		return $this->numero;
	}
	
	/**
	 * Set libelle
	 *
	 * @param string $libelle        	
	 * @return RefDepartement
	 */
	public function setLibelle($libelle){
		$this->libelle = $libelle;
		
		return $this;
	}
	
	/**
	 * Get libelle
	 *
	 * @return string
	 */
	public function getLibelle(){
		return $this->libelle;
	}
	
	/**
	 * Set academie
	 *
	 * @param App\Entity\RefAcademie $academie        	
	 */
	public function setAcademie(\App\Entity\RefAcademie $academie){
		$this->academie = $academie;
	}
	
	/**
	 * Get academie
	 *
	 * @return App\Entity\RefAcademie
	 */
	public function getAcademie(){
		return $this->academie;
	}
	
	/**
	 * Get nom de l'entité
	 *
	 * @return "RefDepartement"
	 */
	public static function getNameEntity(){
		return 'RefDepartement';
	}
}
