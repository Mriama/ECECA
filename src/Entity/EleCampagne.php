<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\ExecutionContextInterface;
use Symfony\Component\Finder\Comparator\DateComparator;
use Symfony\Component\Validator\Constraints\Date;

/**
 * EleCampagne
 *
 * @ORM\Table(name="ele_campagne", options={"collate"="utf8_general_ci"}, uniqueConstraints={@ORM\UniqueConstraint(name="campagneUnique", columns={"id_type_election", "annee_debut", "annee_fin"})})
 * @ORM\Entity(repositoryClass="App\Repository\EleCampagneRepository")
 */
class EleCampagne {
	/**
	 *
	 * @var integer @ORM\Column(name="id", type="integer")
	 *      @ORM\Id
	 *      @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;
	
	/**
	 *
	 * @var \DateTime @ORM\Column(name="annee_debut", type="integer")
	 */
	private $anneeDebut;
	
	/**
	 *
	 * @var \DateTime @ORM\Column(name="annee_fin", type="integer")
	 */
	private $anneeFin;
	
	/**
	 *
	 * @var \DateTime @ORM\Column(name="date_debut_saisie", type="date")
	 */
	private $dateDebutSaisie;
	
	/**
	 *
	 * @var \DateTime @ORM\Column(name="date_fin_saisie", type="date")
	 */
	private $dateFinSaisie;
	
	/**
	 *
	 * @var \DateTime @ORM\Column(name="date_debut_consultation", type="date", nullable=true)
	 */
	private $dateDebutConsultation;
	
	/**
	 *
	 * @var \DateTime @ORM\Column(name="date_fin_consultation", type="date", nullable=true)
	 */
	private $dateFinConsultation;
	
	/**
	 *
	 * @var \DateTime @ORM\Column(name="date_debut_validation", type="date", nullable=true)
	 */
	private $dateDebutValidation;
	
	/**
	 *
	 * @var \DateTime @ORM\Column(name="date_fin_validation", type="date", nullable=true)
	 */
	private $dateFinValidation;
	
	/**
	 *
	 * @var boolean @ORM\Column(name="archivee", type="boolean")
	 */
	private $archivee;
	
	/**
	 *
	 * @var boolean @ORM\Column(name="fermee", type="boolean")
	 */
	private $fermee;
	
	/**
	 *
	 * @var boolean @ORM\Column(name="post_editable", type="boolean", nullable=true)
	 */
	private $postEditable;
	
	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\RefTypeElection")
	 * @ORM\JoinColumn(name="id_type_election", referencedColumnName="id", nullable=false)
	 */
	private $typeElection;
	
	/**
	 *
	 * @var ArrayCollection $consolidations
	 */
	private $consolidations;
	
	/**
	 *
	 * @var ArrayCollection $electionsEtabs
	 */
	private $electionsEtabs;
	
	/**
	 * Main constructor
	 *
	 * @param \App\Entity\RefTypeElection $RefTypeElection        	
	 */
	public function __construct(\App\Entity\RefTypeElection $RefTypeElection) {
		$this->id = 0;
		$this->archivee = false;
		$this->fermee = false;
		$this->typeElection = $RefTypeElection;
		$this->consolidations = new \Doctrine\Common\Collections\ArrayCollection ();
		$this->electionsEtabs = new \Doctrine\Common\Collections\ArrayCollection ();
	}
	
	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * Set Id
	 * 
	 * @param unknown $id        	
	 */
	public function setId($id) {
		$this->id = $id;
	}
	
	/**
	 * Set anneeDebut
	 *
	 * @param \DateTime $anneeDebut        	
	 * @return EleCampagne
	 */
	public function setAnneeDebut($anneeDebut) {
		$this->anneeDebut = $anneeDebut;
		
		return $this;
	}
	
	/**
	 * Get anneeDebut
	 *
	 * @return \DateTime
	 */
	public function getAnneeDebut() {
		return $this->anneeDebut;
	}
	
	/**
	 * Set anneeFin
	 *
	 * @param \DateTime $anneeFin        	
	 * @return EleCampagne
	 */
	public function setAnneeFin($anneeFin) {
		$this->anneeFin = $anneeFin;
		
		return $this;
	}
	
	/**
	 * Get anneeFin
	 *
	 * @return \DateTime
	 */
	public function getAnneeFin() {
		return $this->anneeFin;
	}
	
	/**
	 * Set dateDebutSaisie
	 *
	 * @param \DateTime $dateDebutSaisie        	
	 * @return EleCampagne
	 */
	public function setDateDebutSaisie($dateDebutSaisie) {
		$this->dateDebutSaisie = $dateDebutSaisie;
		
		return $this;
	}
	
	/**
	 * Get dateDebutSaisie
	 *
	 * @return \DateTime
	 */
	public function getDateDebutSaisie() 	// TODO : ask Armelle si on ne peut pas mieux faire si date 0 au lieu de null en bdd
	{
		return ($this->dateDebutSaisie != null and $this->dateDebutSaisie->getTimestamp () !== false) ? $this->dateDebutSaisie : null;
	}
	
	/**
	 * Set dateFinSaisie
	 *
	 * @param \DateTime $dateFinSaisie        	
	 * @return EleCampagne
	 */
	public function setDateFinSaisie($dateFinSaisie) {
		$this->dateFinSaisie = $dateFinSaisie;
		
		return $this;
	}
	
	/**
	 * Get dateFinSaisie
	 *
	 * @return \DateTime
	 */
	public function getDateFinSaisie() 	// TODO : ask Armelle si on ne peut pas mieux faire si date 0 au lieu de null en bdd
	{
		return ($this->dateFinSaisie != null and $this->dateFinSaisie->getTimestamp () !== false) ? $this->dateFinSaisie : null;
	}
	
	/**
	 * Set dateDebutConsultation
	 *
	 * @param \DateTime $dateDebutConsultation        	
	 * @return EleCampagne
	 */
	public function setDateDebutConsultation($dateDebutConsultation) {
		$this->dateDebutConsultation = $dateDebutConsultation;
		
		return $this;
	}
	
	/**
	 * Get dateDebutConsultation
	 *
	 * @return \DateTime
	 */
	public function getDateDebutConsultation() {
		return $this->dateDebutConsultation;
	}
	
	/**
	 * Set dateFinConsultation
	 *
	 * @param \DateTime $dateFinConsultation        	
	 * @return EleCampagne
	 */
	public function setDateFinConsultation($dateFinConsultation) {
		$this->dateFinConsultation = $dateFinConsultation;
		
		return $this;
	}
	
	/**
	 * Get dateFinConsultation
	 *
	 * @return \DateTime
	 */
	public function getDateFinConsultation() {
		return $this->dateFinConsultation;
	}
	
	/**
	 * Set dateDebutValidation
	 *
	 * @param \DateTime $dateDebutValidation        	
	 * @return EleCampagne
	 */
	public function setDateDebutValidation($dateDebutValidation) {
		$this->dateDebutValidation = $dateDebutValidation;
		
		return $this;
	}
	
	/**
	 * Get dateDebutValidation
	 *
	 * @return \DateTime
	 */
	public function getDateDebutValidation() {
		return $this->dateDebutValidation;
	}
	
	/**
	 * Set dateFinValidation
	 *
	 * @param \DateTime $dateFinValidation        	
	 * @return EleCampagne
	 */
	public function setDateFinValidation($dateFinValidation) {
		$this->dateFinValidation = $dateFinValidation;
		
		return $this;
	}
	
	/**
	 * Get dateFinValidation
	 *
	 * @return \DateTime
	 */
	public function getDateFinValidation() {
		return $this->dateFinValidation;
	}
	
	/**
	 * Set archivee
	 *
	 * @param boolean $archivee        	
	 * @return EleCampagne
	 */
	public function setArchivee($archivee) {
		$this->archivee = $archivee;
		
		return $this;
	}
	
	/**
	 * Get archivee
	 *
	 * @return boolean
	 */
	public function getArchivee() {
		return $this->archivee;
	}
	
	/**
	 * Set fermee
	 *
	 * @param boolean $fermee        	
	 * @return EleCampagne
	 */
	public function setFermee($fermee) {
		$this->fermee = $fermee;
		
		return $this;
	}
	
	/**
	 * Get fermee
	 *
	 * @return boolean
	 */
	public function getFermee() {
		return $this->fermee;
	}
	
	/**
	 */
	public function getPostEditable() {
		return $this->postEditable;
	}
	
	/**
	 *
	 * @param unknown $postEditable        	
	 */
	public function setPostEditable($postEditable) {
		$this->postEditable = $postEditable;
		return $this;
	}
	
	/**
	 * Set typeElection
	 *
	 * @param App\Entity\RefTypeElection $typeElection        	
	 */
	public function setTypeElection(\App\Entity\RefTypeElection $typeElection) {
		$this->typeElection = $typeElection;
	}
	
	/**
	 * Get typeElection
	 *
	 * @return App\Entity\RefTypeElection
	 */
	public function getTypeElection() {
		return $this->typeElection;
	}
	
	/**
	 * Get consolidations
	 *
	 * @return Doctrine\Common\Collections\Collection
	 */
	public function getConsolidations() {
		return $this->consolidations;
	}
	
	/**
	 * Set consolidations
	 *
	 * @param
	 *        	array of \App\Entity\EleConsolidation $consolidations
	 */
	public function setConsolidations($consolidations) {
		$this->consolidations = $consolidations;
	}
	
	/**
	 * Get electionsEtabs
	 *
	 * @return Doctrine\Common\Collections\Collection
	 */
	public function getElectionsEtabs() {
		return $this->electionsEtabs;
	}
	
	/**
	 * Set electionsEtabs
	 *
	 * @param
	 *        	array of \App\Entity\EleEtablissement $electionsEtabs
	 */
	public function setElectionsEtabs($electionsEtabs) {
		$this->electionsEtabs = $electionsEtabs;
	}
	
	/**
	 * *********************************** LOGIQUE METIER **********************************************
	 */
	
	/**
	 * Tests de possibilité d'archivage
	 */
	public function isArchivable() {
		if ($this->archivee) {
			// RG_CAMP_16 : Une campagne déjà archivée ne peut pas être archivée de nouveau
			return false;
		} else {
			// RG_CAMP_15 : Une campagne peut être archivée dès que les 3 périodes sont terminées
			return $this->isClosed ();
		}
	}
	
	/**
	 * Tests de validité des dates de la campagne
	 */
	public function isCampagneValid(ExecutionContextInterface $context) {
		// Test 1 : Toutes les dates doivent être comprises entre le 01/01 et le 31/12 de l'année de début de la Campagne
		// Test 2 : La date de fin de saisie des données doit être supérieure à la date de début de la saisie
		// Test 3 : La date de début de la validation des données doit être supérieure à la date de fin de la saisie
		// Test 4 : La date de fin de la validation des données doit être supérieure à la date de début de la validation
		// Test 5 ; La date de fin de la validation des données doit être supérieure à la date de fin de la saisie -> CE TEST NE SERT PLUS A RIEN
		// Test 6 : La date de début de la consultation des données doit être supérieure à la date de fin de la validation -> YME 013E SUPPRIME
		// Test 7 : La date de fin de la consultation des données doit être supérieure à la date de début de la consultation -> YME 013E SUPPRIME
		
		// On ne procède à la validation que sur les campagnes non archivées
		// afin de pouvoir conserver les anciennes campagnes (dates de validation inexistantes)
		if (! $this->archivee) {
			$this->isValidDateDebutSaisie ( $context );
			$this->isValidDateFinSaisie ( $context );
			$this->isValidDateDebutValidation ( $context );
			$this->isValidDateFinValidation ( $context );
			// BBL 014E Re ouvertude d'une campagne en validation
			if ($this->fermee) $this->isValidDateFinReValidation ( $context );
			
			/*
			 * $is_Election_Parents = (($this->typeElection->getId() == \App\Entity\RefTypeElection::ID_TYP_ELECT_PARENT) ? true : false); if($is_Election_Parents){ $this->isValidDateDebutConsultation($context); $this->isValidDateFinConsultation($context); }
			 */
		}
	}
	
	/*
	 * Test de validité d'une date de la campagne comprise entre le 1er Janvier et le 31 Décembre de l'année de début de la campagne
	 */
	public function isValidDate(\DateTime $date = null) {
		if (empty ( $date ) or ($date < new \DateTime ( $this->anneeDebut . '-01-01' )) || ($date > new \DateTime ( $this->anneeDebut . '-12-31' ))) {
			return false;
		}
		return true;
	}
	
	/*
	 * La date de début de saisie doit être comprise entre le 1er Janvier et le 31 Décembre de l'année de début de la campagne
	 */
	public function isValidDateDebutSaisie(ExecutionContextInterface $context) {
		// Test 1 (Date de début de la saisie)
		if (! $this->isValidDate ( $this->dateDebutSaisie )) {
			$context->addViolationAt ( 'dateDebutSaisie', "La date de début de saisie doit être comprise entre le 01/01/" . $this->anneeDebut . " et le 31/12/" . $this->anneeDebut, array (), null );
		}
	}
	
	/*
	 * La date de fin de saisie doit être comprise entre le 1er Janvier et le 31 Décembre de l'année de début de la campagne et elle doit être supérieure à la date de début de saisie
	 */
	public function isValidDateFinSaisie(ExecutionContextInterface $context) {
		// Test 1 (Date de Fin de la saisie)
		if (! $this->isValidDate ( $this->dateFinSaisie )) {
			$context->addViolationAt ( 'dateFinSaisie', "La date de fin de saisie doit être comprise entre le 01/01/" . $this->anneeDebut . " et le 31/12/" . $this->anneeDebut, array (), null );
		}
		
		// Test 2
		if ($this->dateFinSaisie < $this->dateDebutSaisie) {
			$context->addViolationAt ( 'dateFinSaisie', "La date de fin de saisie doit être supérieure à la date de début de saisie", array (), null );
		}
	}
	
	/*
	 * La date de début de validation doit être comprise entre le 1er Janvier et le 31 Décembre de l'année de début de la campagne et elle doit être supérieure à la date de fin de saisie
	 */
	public function isValidDateDebutValidation(ExecutionContextInterface $context) {
		// Test 1 (Date de début de la validation)
		if (! $this->isValidDate ( $this->dateDebutValidation )) {
			$context->addViolationAt ( 'dateDebutValidation', "La date de début de validation doit être comprise entre le 01/01/" . $this->anneeDebut . " et le 31/12/" . $this->anneeDebut, array (), null );
		}
		
		// Test 3
		if ($this->dateDebutValidation <= $this->dateFinSaisie) {
			$context->addViolationAt ( 'datedebutValidation', "La date de début de validation doit être supérieure à la date de fin de saisie", array (), null );
		}
	}
	
	/*
	 * La date de fin de validation doit être comprise entre le 1er Janvier et le 31 Décembre de l'année de début de la campagne elle doit être supérieure à la date de début de validation //et supérieure à la date de fin de saisie -> ne sert à rien
	 */
	public function isValidDateFinValidation(ExecutionContextInterface $context) {
		// Test 1 (Date de fin de la validation)
		if (! $this->isValidDate ( $this->dateFinValidation )) {
			$context->addViolationAt ( 'dateFinValidation', "La date de fin de validation doit être comprise entre le 01/01/" . $this->anneeDebut . " et le 31/12/" . $this->anneeDebut, array (), null );
		}
		
		// Test 4
		if ($this->dateFinValidation <= $this->dateDebutValidation) {
			$context->addViolationAt ( 'dateFinValidation', "La date de fin de validation doit être supérieure à la date de début de validation", array (), null );
		}
		
		// Test 5
		/*
		 * if($this->dateFinValidation <= $this->dateFinSaisie){ $context->addViolationAt('dateFinValidation', "La date de fin de validation doit être supérieure à la date de fin de saisie", array(), null); }
		 */
	}
	
	/*
	 * BBL 014E Re ouvertude d'une campagne en validation
	 * La date de fin de re validation doit être doit être supérieure ou égale à la date du jour
	 */
	public function isValidDateFinReValidation(ExecutionContextInterface $context) {
		// Test 1
		$today = new \DateTime ();
		$today->setTime ( 0, 0, 0 );
		if ($this->isValidDate ( $this->dateFinValidation ) && $this->dateFinValidation > $this->dateDebutValidation && $this->dateFinValidation < $today) {
			$context->addViolationAt ( 'dateFinValidation', "La date de fin de validation doit être supérieure ou égale à la date du jour", array (), null );
		}
	}
	
	/*
	 * La date de début de consultation doit être comprise entre le 1er Janvier et le 31 Décembre de l'année de début de la campagne et elle doit être supérieure à la date de fin de validation
	 */
	/*
	public function isValidDateDebutConsultation(ExecutionContextInterface $context){
		if (!empty($this->dateDebutConsultation)) {
			// Test 1 (Date de début de la consultation)
			if(!$this->isValidDate($this->dateDebutConsultation)){
				$context->addViolationAt('dateDebutConsultation', "La date de début de consultation doit être comprise entre le 01/01/" . $this->anneeDebut . " et le 31/12/" . $this->anneeDebut, array(), null);
			}
			
			// Test 6
			if($this->dateDebutConsultation <= $this->dateFinValidation){
				$context->addViolationAt('dateDebutConsultation', "La date de début de consultation doit être supérieure à la date de fin de validation", array(), null);
			}
		}
	}*/
	
	/*
	 * La date de fin de consultation doit être comprise entre le 1er Janvier et le 31 Décembre de l'année de début de la campagne et elle doit être supérieure à la date de début de consultation
	 */
	/*
	public function isValidDateFinConsultation(ExecutionContextInterface $context){
		if (!empty($this->dateFinConsultation) && empty($this->dateDebutConsultation)) {
			$context->addViolationAt('dateFinConsultation', "La date de début de consultation doit être renseignée avec la date de fin de consultation", array(), null);
		}
		
		if (!empty($this->dateFinConsultation) && !empty($this->dateDebutConsultation)) {
			// Test 1 (Date de fin de la consultation)
			if(!$this->isValidDate($this->dateFinConsultation)){
				$context->addViolationAt('dateFinConsultation', "La date de fin de consultation doit être comprise entre le 01/01/" . $this->anneeDebut . " et le 31/12/" . $this->anneeDebut, array(), null);
			}
			
			// Test 7
			if($this->dateFinConsultation <= $this->dateDebutConsultation){
				$context->addViolationAt('dateFinConsultation', "La date de fin de consultation doit être supérieure à la date de début de consultation", array(), null);
			}
		}
	}*/
	
	/*
	 * Fonction permettant d'afficher les années de debut et de fin d'une campagne
	 */
	public function getAnneesDebFinCampagne() {
		return $this->anneeDebut . ' - ' . $this->anneeFin;
	}
	
	/**
	 * La campagne est-elle ouverte à la saisie ?
	 *
	 * @return boolean
	 */
	public function isOpenSaisie($academies, $joursCalendaires, $academie_selectionnee = null, $joursCalendairesIen = null) {
		if (null == $this->dateDebutSaisie) {
			return false;
		}
		
		$today = new \DateTime ();
		$today->setTime ( 0, 0, 0 );
		
		$academies_decalage = array (
				RefAcademie::CODE_ACA_MAYOTTE,
				RefAcademie::CODE_ACA_REUNION 
		);
		
		// YME 013E EX_003
		// YME #145940
		$decalage = true;
		if (is_array ( $academies ) && ! empty ( $academies )) {
			// Cas particulier sur plusieurs académies
			// Si le tableau contient d'autres académies il n'y a pas de décalage
			foreach ( $academies as $academie ) {
				if (! in_array ( $academie->getCode (), $academies_decalage )) {
					$decalage = false;
					break;
				}
			}
		}
		
		if (null != $academie_selectionnee && in_array ( $academie_selectionnee->getCode (), $academies_decalage )) {
			$decalage = true;
		}
		

		if (! $decalage) {
			// 014E changement de calendrier de saisie de tirage au sort pour l'IEN $joursCalendairesIen
			if ($joursCalendairesIen != null) {
				$dateFinSaisieIen = new \DateTime ();
				$dateFinSaisieIen->setDate ( $this->dateFinSaisie->format ( 'Y' ), $this->dateFinSaisie->format ( 'm' ), $this->dateFinSaisie->format ( 'd' ) );
				$dateFinSaisieIen->setTime ( 0, 0, 0 );
				$dateFinSaisieIen->add( new \DateInterval ( 'P' . $joursCalendairesIen . 'D' ) );
		
				return ($this->dateDebutSaisie <= $today && $dateFinSaisieIen >= $today);
			} else {
				return ($this->dateDebutSaisie <= $today && $this->dateFinSaisie >= $today);
			}
		} else {
			$dateDebutDecalee = new \DateTime ();
			$dateDebutDecalee->setDate ( $this->dateDebutSaisie->format ( 'Y' ), $this->dateDebutSaisie->format ( 'm' ), $this->dateDebutSaisie->format ( 'd' ) );
			$dateDebutDecalee->setTime ( 0, 0, 0 );
			$dateDebutDecalee->sub ( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );

			$dateFinDecalee = new \DateTime ();
			$dateFinDecalee->setDate ( $this->dateFinSaisie->format ( 'Y' ), $this->dateFinSaisie->format ( 'm' ), $this->dateFinSaisie->format ( 'd' ) );
			$dateFinDecalee->setTime ( 0, 0, 0 );
			$dateFinDecalee->sub ( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );
			
			
			// 014E changement de calendrier de saisie de tirage au sort pour l'IEN $joursCalendairesIen
			if ($joursCalendairesIen != null) {
				$dateFinIenDecalee = new \DateTime ();
				$dateFinIenDecalee->setDate ( $dateFinDecalee->format ( 'Y' ), $dateFinDecalee->format ( 'm' ), $dateFinDecalee->format ( 'd' ) );
				$dateFinIenDecalee->setTime ( 0, 0, 0 );
				$dateFinIenDecalee->add ( new \DateInterval ( 'P' . $joursCalendairesIen . 'D' ) );
				return ($dateDebutDecalee <= $today && $dateFinIenDecalee >= $today);
			} else {
				return ($dateDebutDecalee <= $today && $dateFinDecalee >= $today);
			}
		}
	}
	
	/**
	 * La campagne est-elle ouverte à la validation ?
	 *
	 * @return boolean
	 */
	public function isOpenValidation($academies, $joursCalendaires) {
		if (null == $this->dateDebutValidation) {
			return false;
		}
		
		$today = new \DateTime ();
		$today->setTime ( 0, 0, 0 );
		
		$academies_decalage = array (
				RefAcademie::CODE_ACA_MAYOTTE,
				RefAcademie::CODE_ACA_REUNION 
		);
		
		// YME 013E EX_003
		// YME #145940
		$decalage = true;
		if (is_array ( $academies ) && ! empty ( $academies )) {
			// Cas particulier sur plusieurs académies
			// Si le tableau contient d'autres académies il n'y a pas de décalage
			foreach ( $academies as $academie ) {
				if (! in_array ( $academie->getCode (), $academies_decalage )) {
					$decalage = false;
					break;
				}
			}
		}
		
		if (! $decalage) {
			return ($this->dateDebutValidation <= $today && $this->dateFinValidation >= $today);
		} else {
			$dateDebutDecalee = new \DateTime ();
			$dateDebutDecalee->setDate ( $this->dateDebutValidation->format ( 'Y' ), $this->dateDebutValidation->format ( 'm' ), $this->dateDebutValidation->format ( 'd' ) );
			$dateDebutDecalee->setTime ( 0, 0, 0 );
			$dateDebutDecalee->sub ( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );
			
			$dateFinDecalee = new \DateTime ();
			$dateFinDecalee->setDate ( $this->dateFinValidation->format ( 'Y' ), $this->dateFinValidation->format ( 'm' ), $this->dateFinValidation->format ( 'd' ) );
			$dateFinDecalee->setTime ( 0, 0, 0 );
			$dateFinDecalee->sub ( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );
			
			return ($dateDebutDecalee <= $today && $dateFinDecalee >= $today);
		}
	}
	
	/**
	 * La campagne est-elle ouverte à la consultation ?
	 *
	 * @return boolean
	 */
	/*
	 * YME 013E SUPPRIME public function isOpenConsultation(){ if(null == $this->dateDebutConsultation){ return false; } $today = new \DateTime(); $today->setTime(0, 0, 0); return ($this->dateDebutConsultation <= $today && $this->dateFinConsultation >= $today); }
	 */
	
	/**
	 * La campagne est-elle finie ?
	 *
	 * @return boolean
	 */
	public function isFinished() {
		if (null == $this->dateDebutValidation) {
			return true;
		}
		
		$today = new \DateTime ();
		$today->setTime ( 0, 0, 0 );
		return ($this->dateFinValidation < $today);
	}
	
	/**
	 * La campagne est-elle fermée ? (i.e not en cours)
	 * RG_CAMP_03 : Une campagne est en cours quand au moins une date de fin de ses 3 périodes
	 * (saisie / validation / consultation (pour PARENTS uniquement)) est supérieure à la date du jour.
	 */
	public function isClosed() {
			$isClosedSaisie = false;
			$isClosedValidation = false;
			// $isClosedConsultation = (empty($this->dateDebutConsultation) && empty($this->dateFinConsultation));
			
			$today = new \DateTime ();
			$today->setTime ( 0, 0, 0 );
			
			if (! empty ( $this->dateFinSaisie ) && $this->dateFinSaisie < $today) {
				$isClosedSaisie = true;
			}
			
			if (! empty ( $this->dateFinValidation ) && $this->dateFinValidation < $today) {
				$isClosedValidation = true;
			}
			
			// BBL: la date de consultation n'est plus prise en compte pour parents d'élèves RG_CAMP_03 version 013E
			/*
			 * $is_Election_Parents = (($this->typeElection->getId() == \App\Entity\RefTypeElection::ID_TYP_ELECT_PARENT) ? true : false); if ($is_Election_Parents) { if (!empty($this->dateFinConsultation) && $this->dateFinConsultation < $today) { $isClosedConsultation = true; } var_dump($isClosedConsultation);die(); return ($isClosedSaisie && $isClosedValidation && $isClosedConsultation); } else { return ($isClosedSaisie && $isClosedValidation); }
			 */
			return ($isClosedSaisie && $isClosedValidation);
	}
	
	/**
	 * Indique si des résultats peuvent encore être saisis pendant la période de validation
	 */
	public function isPostEditable() {
		return $this->postEditable;
	}

    /**
     * indique si on est en période P2bis (X jours après la fin des saisies)
     * @param null $joursCalendairesIen
     * @return bool
     */
	public function isP2Bis($joursCalendairesIen = null, $joursCalendaires = null, $academie = null)
    {
        $academies_decalage = array (
            RefAcademie::CODE_ACA_MAYOTTE,
            RefAcademie::CODE_ACA_REUNION
        );

        $decalage = ($academie != null && in_array($academie->getCode(), $academies_decalage));

        $today = new \DateTime ();
        $today->setTime ( 0, 0, 0 );

        $dateDebutP2bis = new \DateTime ();
        $dateDebutP2bis->setDate ( $this->dateFinSaisie->format ( 'Y' ), $this->dateFinSaisie->format ( 'm' ), $this->dateFinSaisie->format ( 'd' ) );
        $dateDebutP2bis->setTime ( 0, 0, 0 );

        $dateFinP2bis = new \DateTime ();
        $dateFinP2bis->setDate ( $this->dateFinSaisie->format ( 'Y' ), $this->dateFinSaisie->format ( 'm' ), $this->dateFinSaisie->format ( 'd' ) );
        $dateFinP2bis->setTime ( 0, 0, 0 );

        if ($joursCalendairesIen != null) {
            $dateFinP2bis->add( new \DateInterval ( 'P' . $joursCalendairesIen . 'D' ) );
        }

        if($decalage && $joursCalendaires != null ) {
            $dateDebutP2bis->sub( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );
            $dateFinP2bis->sub( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );
        }
        return ($dateDebutP2bis < $today && $today <= $dateFinP2bis);
    }

    /**
     * indique si on est hors péeriode de la saisie IEN
     * @param null $joursCalendairesIen
     * @return bool
     */
    public function isP2Ter($joursCalendairesIen = null, $joursCalendaires = null, $academie = null)
    {
        $academies_decalage = array (
            RefAcademie::CODE_ACA_MAYOTTE,
            RefAcademie::CODE_ACA_REUNION
        );

        $decalage = ($academie != null && in_array($academie->getCode(), $academies_decalage));

        $today = new \DateTime ();
        $today->setTime ( 0, 0, 0 );

        $dateFinSaisieIen = new \DateTime ();
        $dateFinSaisieIen->setDate ( $this->dateFinSaisie->format ( 'Y' ), $this->dateFinSaisie->format ( 'm' ), $this->dateFinSaisie->format ( 'd' ) );
        $dateFinSaisieIen->setTime ( 0, 0, 0 );

        $dateFinValidation = new \DateTime ();
        $dateFinValidation->setDate ( $this->dateFinValidation->format ( 'Y' ), $this->dateFinValidation->format ( 'm' ), $this->dateFinValidation->format ( 'd' ) );
        $dateFinValidation->setTime ( 0, 0, 0 );
        if ($joursCalendairesIen != null) {
            $dateFinSaisieIen->add( new \DateInterval ( 'P' . $joursCalendairesIen . 'D' ) );
        }
        if($decalage && $joursCalendaires != null ) {
            $dateFinSaisieIen->sub( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );
            $dateFinValidation->sub( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );
        }
        return ($dateFinSaisieIen < $today && $today <= $dateFinValidation);
    }
}
