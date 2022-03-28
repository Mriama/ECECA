<?php

namespace App\Model;

class ContactModel {
	
	const TYPES_ZONES = "RefAcademie:Académie;RefDepartement:Département";

	/**
	 * libelle
	 * @var string
	 */
	private $libelle;

	/**
	 * departement
	 */
	private $departement;

	/**
	 * academie
	 * @var \App\Entity\RefAcademie
	 */
	private $academie;
	
	/**
	 * contact
	 * @var \App\Entity\RefContact
	 */
	private $contact;


	public function __construct($zone, $contact=null) {
		if (!empty($zone)) { $this->libelle = $zone->getLibelle(); };
		if ($zone instanceof \App\Entity\RefDepartement) {
			$this->departement = $zone;
			$this->academie = $zone->getAcademie();
		} else {
			$this->academie = $zone;
		}
		if(!empty($contact)) { $this->contact = $contact; }
	}


	public function setLibelle($libelle) {
		$this->libelle = $libelle;
	}
	
	public function getLibelle() {
		return $this->libelle;
	}

	public function getDepartement() {
		return $this->departement;
	}

	public function setDepartement(\App\Entity\RefDepartement $dept=null) {
		$this->departement = $dept;
	}

	public function setAcademie(\App\Entity\RefAcademie $academie) {
		$this->academie = $academie;
	}
 
	public function getAcademie() {
		return $this->academie;
	}
	
	public function setContact(\App\Entity\RefContact $contact) {
		$this->contact = $contact;
	}
	
	public function getContact() {
		return $this->contact;
	}

	public static function getChoixTypesZones($typeZone=null) {
		$f = function($t){
			return explode(':', $t);
		};
		$dataInArray = array_map($f, explode(';', self::TYPES_ZONES));
		$choixTypesZones[$dataInArray[0][0]] = $dataInArray[0][1];
		$choixTypesZones[$dataInArray[1][0]] = $dataInArray[1][1];
		return empty($typeZone) ? $choixTypesZones : $choixTypesZones[$typeZone];
	}
}
