<?php

namespace App\Model;
use Symfony\Component\Validator\ExecutionContextInterface;

use App\Entity\EleEtablissement;

class TdbZoneEtabModel {

	/**
	 * statutPv
	 * @var array
	 */
	private $statutPv;
	
	/**
	 * etatAvancement
	 * @var array
	 */
	private $etatAvancement;
	
	/**
	 * pvCarence
	 * @var 
	 */
	private $pvCarence;
	
	/**
	 * nvElect
	 * @var 
	 */
	private $nvElect;
	
	public function __construct($etatSaisie = array(EleEtablissement::ETAT_VALIDATION),
                               $etatAvancement = array(), $pvCarence, $nvElect) {
		
		$this->statutPv = $statutPv;
		$this->etatAvancement = $etatAvancement;
		$this->pvCarence = $pvCarence;
		$this->nvElect = $nvElect;
		
	}

    public function getStatutPv()
    {
        return $this->statutPv;
    }

    public function setStatutPv(array $statutPv)
    {
        return $this->statutPv = $statutPv;
    }

    public function getEtatAvancement()
    {
        return $this->etatAvancement;
    }

    public function setEtatAvancement(array $etatAvancement)
    {
       return $this->etatAvancement = $etatAvancement;
    }

    public function getPvCarence()
    {
        return $this->pvCarence;
    }

    public function setPvCarence($pvCarence)
    {
        return $this->pvCarence = $pvCarence;
    }

    public function getNvElect()
    {
        return $this->nvElect;
    }

    public function setNvElect($nvElect)
    {
        return $this->nvElect = $nvElect;
    }
	
	

		
}
