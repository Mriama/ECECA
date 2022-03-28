<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * EleEtablissement
 *
 * @ORM\Table(name="ele_etablissement", options={"collate"="utf8_general_ci"}, uniqueConstraints={@ORM\UniqueConstraint(name="eleEtablissementUnique", columns={"uai","id_campagne","id_sous_type_election"})})
 * @ORM\Entity(repositoryClass="App\Repository\EleEtablissementRepository")
 */
class EleEtablissement {
	
	/**
	 * 3 états de la validation
	 */
	const ETAT_SAISIE = 'S';
	const ETAT_TRANSMISSION = 'T';
	const ETAT_VALIDATION = 'V';
	const ETAT_NONEFF = 'N';
	// Si aucun etat de validation n'est requis
	const ETAT_TOUS = 'O';

	const ETAT_TIRAGE_AU_SORT_IEN = 1; //Tirage au sort effectué par l'IEN
	const ETAT_TIRAGE_AU_SORT_RECTORAT_DSDEN = 2; //Tirage au sort effectué par le DSDEN/Rectorat

	
	/**
	 *
	 * @var integer 
	 * 		@ORM\Column(name="id", type="integer")
	 *      @ORM\Id
	 *      @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;
	
	/**
	 *
	 * @var string 
	 * 		@ORM\Column(name="validation", type="string", length=1)
	 */
	private $validation;
	
	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\EleCampagne")
	 * @ORM\JoinColumn(name="id_campagne", referencedColumnName="id", onDelete="CASCADE")
	 */
	private $campagne;
	
	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\RefEtablissement")
	 * @ORM\JoinColumn(name="uai", referencedColumnName="uai")
	 */
	private $etablissement;
	
	/**
	 * @ORM\OneToOne(targetEntity="App\Entity\EleParticipation", cascade={"persist"})
	 * @ORM\JoinColumn(name="id_participation", referencedColumnName="id", onDelete="CASCADE")
	 */
	private $participation;
	
	/**
	 *
	 * @var ArrayCollection $resultats
	 */
	private $resultats;
	
	/**
	 * 
	 * @var ArrayCollection $resultatsDetailles
	 */
	private $resultatsDetailles;
	
	
	/**
	 * @ORM\OneToOne(targetEntity="App\Entity\EleFichier", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(name="id_fichier", referencedColumnName="id", nullable=true)
	 */
	private $fichier;
	
	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\RefSousTypeElection")
	 * @ORM\JoinColumn(name="id_sous_type_election", referencedColumnName="id", nullable=true)
	 */
	private $sousTypeElection;
	
	/**
	 *
	 * @var integer
	 * 		@ORM\Column(name="ind_tirage_sort", type="integer")
	 */
	private $indTirageSort;
	
	/**
	 *
	 * @var integer
	 * 		@ORM\Column(name="ind_carence", type="integer")
	 */
	private $indCarence;
	
	/**
	 *
	 * @var integer
	 * 		@ORM\Column(name="ind_deficit", type="integer")
	 */
	private $indDeficit;
	
	/**
	 * Constructeur par défaut
	 */
	public function __construct(){
		$this->resultats = new \Doctrine\Common\Collections\ArrayCollection();
		$this->resultatsDetailles = new \Doctrine\Common\Collections\ArrayCollection();
		$this->validation = self::ETAT_SAISIE;
	}
	
	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId(){
		return $this->id;
	}
	
	/**
	 * Set Id
	 * @param unknown $id
	 */
	public function setId($id){
		$this->id = $id;
	}
	
	/**
	 * Set validation
	 *
	 * @param string $validation        	
	 * @return EleEtablissement
	 */
	public function setValidation($validation){
		$this->validation = $validation;
		
		return $this;
	}
	
	/**
	 * Get validation
	 *
	 * @return string
	 */
	public function getValidation(){
		return $this->validation;
	}
	
	/**
	 * Set campagne
	 *
	 * @param App\Entity\EleCampagne $campagne        	
	 */
	public function setCampagne(\App\Entity\EleCampagne $campagne){
		$this->campagne = $campagne;
	}
	
	/**
	 * Get campagne
	 *
	 * @return App\Entity\EleCampagne
	 */
	public function getCampagne(){
		return $this->campagne;
	}
	
	/**
	 * Set etablissement
	 *
	 * @param App\Entity\RefEtablissement $etablissement        	
	 */
	public function setEtablissement(\App\Entity\RefEtablissement $etablissement){
		$this->etablissement = $etablissement;
	}
	
	/**
	 * Get etablissement
	 *
	 * @return App\Entity\RefEtablissement
	 */
	public function getEtablissement(){
		return $this->etablissement;
	}
	
	/**
	 * Set participation
	 *
	 * @param App\Entity\EleParticipation $participation        	
	 */
	public function setParticipation( \App\Entity\EleParticipation $participation){
		$this->participation = $participation;
	}
	
	/**
	 * Get participation
	 *
	 * @return App\Entity\EleParticipation
	 */
	public function getParticipation(){
		return $this->participation;
	}
	
	/**
	 * Add resultat
	 *
	 * @param App\Entity\EleResultat $resultat        	
	 */
	public function addResultat(\App\Entity\EleResultat $resultat){
		$this->resultats[] = $resultat;
	}
	
	/**
	 * Remove resultat
	 *
	 * @param App\Entity\EleResultat $resultat        	
	 */
	public function removeResultat(\App\Entity\EleResultat $resultat){
		$this->resultats->removeElement($resultat);
	}
	
	/**
	 * Get resultats
	 *
	 * @return Doctrine\Common\Collections\Collection
	 */
	public function getResultats(){
		return $this->resultats;
	}
	
	/**
	 * Set resultats
	 *
	 * @param array of \App\Entity\EleResultat $resultats
	 */
	public function setResultats($resultats) {
		$this->resultats = $resultats;
	}
	
	/**
	 * Add resultatDetail
	 *
	 * @param App\Entity\EleResultatDetail $resultat        	
	 */
	public function addResultatDetail(\App\Entity\EleResultatDetail $resultat){
		$this->resultatsDetailles[] = $resultat;
	}
	
	/**
	 * Get resultats détaillés
	 * 
	 * @return Doctrine\Common\Collections\Collection
	 */
	public function getResultatsDetailles()
	{
	    return $this->resultatsDetailles;
	}
	
	/**
	 * 
	 * @param unknown $resultatsDetailles
	 */
	public function setResultatsDetailles($resultatsDetailles)
	{
	    $this->resultatsDetailles = $resultatsDetailles;
	    return $this;
	}
	
	/**
	 *
	 * @param \App\Entity\EleFichier $fichier        	
	 */
	public function setFichier(\App\Entity\EleFichier $fichier = null){
		$this->fichier = $fichier;
		
		return $this;
	}
	
	/**
	 *
	 * @return \App\Entity\EleFichier
	 */
	public function getFichier(){
		return $this->fichier;
	}
	
	/**
	 * 
	 */
	public function getSousTypeElection(){
		return $this->sousTypeElection;
	}
	
	/**
	 * 
	 * @param unknown $sousTypeElection
	 * @return \App\Entity\EleEtablissement
	 */
	public function setSousTypeElection($sousTypeElection){
		$this->sousTypeElection = $sousTypeElection;
		return $this;
	}
	
	public function getIndTirageSort(){
		return $this->indTirageSort;
	}
	
	public function setIndTirageSort($indTirageSort){
		$this->indTirageSort = $indTirageSort;
		return $this;
	}
	
	public function getIndCarence(){
		return $this->indCarence;
	}
	
	public function setIndCarence($indCarence){
		$this->indCarence = $indCarence;
		return $this;
	}
	
	public function getIndDeficit(){
		return $this->indDeficit;
	}
	
	public function setIndDeficit($indDeficit){
		$this->indDeficit = $indDeficit;
		return $this;
	}
	
	/**
	 * ********************************** LOGIQUE METIER ************************************
	 */
	
	/**
	 * Libellé des états des saisies en français
	 *
	 * @param
	 *        	array : tableau de codes ETAT
	 * @return String Utilisé pour le rappel d'affichage des résultats
	 */
	public static function getLibellesEtatsSaisie($etatSaisie){
		$string = '';
		if(in_array(self::ETAT_SAISIE, $etatSaisie)){
			$string .= 'enregistrés, ';
		}
		if(in_array(self::ETAT_TRANSMISSION, $etatSaisie)){
			$string .= 'transmis, ';
		}
		if(in_array(self::ETAT_VALIDATION, $etatSaisie)){
			$string .= 'validés, ';
		}
		
		if($string != '')
			$string = substr($string, 0, -2);
		return $string;
	}
		
	/**
	 * Return if saisi or not
	 *
	 * @return boolean
	 */
	public function isSaisi(){
		return $this->validation == self::ETAT_SAISIE;
	}
	
	/**
	 * Return if transmis or not
	 *
	 * @return boolean
	 */
	public function isTransmis(){
		return $this->validation == self::ETAT_TRANSMISSION;
	}
	
	/**
	 * Return if valide or not
	 *
	 * @return boolean
	 */
	public function isValide(){
		return $this->validation == self::ETAT_VALIDATION;
	}
	
	
	
	/**
	 * *************** Données Calculées ****************************
	 */
	
	/**
	 * Get nbVoixTotal = somme(nbVoix)
	 *
	 * @return integer
	 */
	public function getNbVoixTotal(){
		$nbVoixTotal = 0;
		foreach($this->resultats as $resultat){
			$nbVoixTotal = $nbVoixTotal + $resultat->getNbVoix();
		}
		return $nbVoixTotal;
	}
	
	/**
	 * Get nbSiegesTotal = somme(nbSieges)
	 *
	 * @return integer
	 */
	public function getNbSiegesTotal(){
		$nbSiegesTotal = 0;
		foreach($this->resultats as $resultat){
			$nbSiegesTotal = $nbSiegesTotal + min($resultat->getNbSieges(), $resultat->getNbCandidats()) + $resultat->getNbSiegesSort();
		}
		return $nbSiegesTotal;
	}
	public function isParticipationValid(ExecutionContextInterface $context){
		if($this->participation != null){
			if($this->participation->getNbInscrits() < 0){
				$context->addViolation('Le nombre d\'inscrits ne peut pas être négatif', array(
						$this->participation->getNbInscrits() 
				), null);
			}
			if($this->participation->getNbVotants() < 0){
				$context->addViolation('Le nombre de votants ne peut pas être négatif', array(
						$this->participation->getNbVotants() 
				), null);
			}
			if($this->participation->getNbExprimes() < 0){
				$context->addViolation('Le nombre de vote exprimés ne peut pas être négatif', array(
						$this->participation->getNbExprimes() 
				), null);
			}
			// commenté defect#169 Saisie des résultats : message d'erreur inadapté
// 			if($this->participation->getNbSiegesPourvoir() <= 0){
// 				$context->addViolation('Le nombre de sièges à pourvoir ne peut pas être négatif ou null', array(
// 						$this->participation->getNbSiegesPourvoir() 
// 				), null);
// 			}
			if($this->participation->getNbSiegesPourvus() < 0){
				$context->addViolation('Le nombre de sièges pourvus ne peut pas être négatif', array(
						$this->participation->getNbSiegesPourvus() 
				), null);
			}
			
			if($this->participation->getNbInscrits() < $this->participation->getNbVotants()){
				$context->addViolation('Le nombre de votants ne peut pas être supérieur au nombre d\'inscrits', array(
						$this->participation 
				), null);
			}
			if($this->participation->getNbVotants() < $this->participation->getNbExprimes()){
				$context->addViolation('Le nombre de votes exprimés ne peut pas être supérieur au nombre de votants', array(
						$this->participation 
				), null);
			}
			if($this->participation->getNbSiegesPourvoir() < $this->participation->getNbSiegesPourvus()){
				$context->addViolation('Le nombre de sièges pourvus ne peut pas être supérieur au nombre de sièges à pourvoir', array(
						$this->participation 
				), null);
			}
		}
	}
	public function isResultatsValid(ExecutionContextInterface $context){
		if($this->resultats != null){
			$nbVoixTotal = 0;
			$nbSiegesTotal = 0;
			foreach($this->resultats as $resultat){
				if($resultat->getNbVoix() < 0)
					$context->addViolation('Le nombre de voix ne peut pas être négatif.', array(
							$resultat->getNbVoix() 
					), null);
				if($resultat->getNbSieges() < 0)
					$context->addViolation('Le nombre de sièges ne peut pas être négatif.', array(
							$resultat->getNbSieges() 
					), null);
				if($resultat->getNbSiegesSort() < 0)
					$context->addViolation('Le nombre d\'inscrits ne peut pas être négatif.', array(
							$resultat->getNbSiegesSort() 
					), null);
				$nbVoixTotal += $resultat->getNbVoix();
				$nbSiegesTotal += $resultat->getNbSieges() + $resultat->getNbSiegesSort();
			}
			
			// si on a des données dans la répartition des voix on vérifie les données
			if($nbVoixTotal != 0 && $nbSiegesTotal != 0){
				
				if($nbVoixTotal > $this->participation->getNbExprimes()){
					$context->addViolation('La somme des suffrages ne peut pas être supérieur au nombre de voix exprimés.', array(
							$this->resultats 
					), null);
				}
				if($nbSiegesTotal > $this->participation->getNbSiegesPourvoir()){
					$context->addViolation('Le nombre de sièges total ne peut pas être supérieur au nombre de sièges à pourvoir.', array(
							$this->resultats 
					), null);
				}
				// RG_SAISIE_6 : Le nombre de sièges pourvus doit être inférieur ou égal à la somme des sièges attribués
				// ECT : nbSiegesPourvus = min (nb total candidats titulaires, nbSiegesDistribues)
				if($this->participation->getNbSiegesPourvus() > $nbSiegesTotal){
					$context->addViolation('Le nombre de sièges pourvus doit être inférieur ou égal à la somme des sièges attribués.', array(
							$this->resultats 
					), null);
				}
			}
		}
	}

	/**
	 * En attente de nouvelles élections ?
	 * @return boolean
	 */
	public function isEnAttenteDeNouvellesElections() {
		// Etab 2nd degré
		$isEnAttente = ($this->etablissement->getTypeEtablissement()->getDegre() == 2);
		
		// Election RP
		if ($isEnAttente) {
			$isEnAttente = ($this->campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $this->campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PEE);
		}
		
		// Resultat transmis
		if ($isEnAttente) {
			$isEnAttente = ($this->validation == self::ETAT_TRANSMISSION);
		}
		
		// Cas de déficit de candidats
		if ($isEnAttente) {
			$isEnAttente = ($this->indDeficit == 1);
		}
		
		return $isEnAttente;
	}
	
}
