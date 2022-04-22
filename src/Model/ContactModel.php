<?php

namespace App\Model;

use App\Entity\RefAcademie;
use App\Entity\RefContact;
use App\Entity\RefDepartement;

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
     * @var RefAcademie
     */
    private $academie;

    /**
     * contact
     * @var RefContact
     */
    private $contact;


    public function __construct($zone, $contact=null) {
        if (!empty($zone)) { $this->libelle = $zone->getLibelle(); }
        if ($zone instanceof RefDepartement) {
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

    public function setDepartement(RefDepartement $dept=null) {
        $this->departement = $dept;
    }

    public function setAcademie(RefAcademie $academie) {
        $this->academie = $academie;
    }

    public function getAcademie() {
        return $this->academie;
    }

    public function setContact(RefContact $contact) {
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
        $choixTypesZones[$dataInArray[0][1]] = $dataInArray[0][0];
        $choixTypesZones[$dataInArray[1][1]] = $dataInArray[1][0];
        return empty($typeZone) ? $choixTypesZones : $choixTypesZones[$typeZone];
    }
}
