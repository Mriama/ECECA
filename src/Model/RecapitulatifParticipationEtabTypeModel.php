<?php
namespace App\Model;

use App\Entity\EleCampagne;
use App\Entity\RefTypeElection;
use App\Entity\RefTypeEtablissement;

class RecapitulatifParticipationEtabTypeModel{

    /**
     * campagne
     * @var EleCampagne
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
     * @var RefTypeEtablissement
     *
     */
    private $typeEtablissement;

    /**
     * typeElection
     * @var RefTypeElection
     *
     */
    private $typeElection;

    public function __construct(
        RefTypeElection $typeElection=null,
        EleCampagne $campagne=null,
                                    $niveau=null,
        RefTypeEtablissement $typeEtab=null
    ) {

        if(!empty($typeElection)) $this->typeElection = $typeElection;
        if(!empty($campagne)) $this->campagne = $campagne;
        if(!empty($niveau)) $this->niveau = $niveau;
        if(!empty($typeEtab)) $this->typeEtablissement = $typeEtab;


    }
    public function getCampagne(){
        return $this->campagne;
    }
    public function setCampagne(EleCampagne $campagne=null){
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
    public function setTypeEtablissement(RefTypeEtablissement $typeEtablissement=null){
        $this->typeEtablissement = $typeEtablissement;
        return $this;
    }
    public function getTypeElection(){
        return $this->typeElection;
    }
    public function setTypeElection(RefTypeElection $typeElection){
        $this->typeElection = $typeElection;

        print_r($typeElection);
        return $this;
    }





}