<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RefSousTypeElection
 *
 * @ORM\Table(name="ref_sous_type_election", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\RefSousTypeElectionRepository")
 */
class RefSousTypeElection
{
	
	const ID_TYP_ELECT_A_ATTE = 10;
	const ID_TYP_ELECT_SS = 11;
	const CODE_URL_A_ATTE = 'a_atte';
	const CODE_URL_SS = 'SS';
	
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=50)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255)
     */
    private $libelle;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RefTypeElection")
     * @ORM\JoinColumn(name="id_type_election", referencedColumnName="id", nullable=false)
     */
    private $typeElection;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param string $id
     * @return RefSousTypeElection
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return RefSousTypeElection
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set libelle
     *
     * @param string $libelle
     * @return RefSousTypeElection
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * Get libelle
     *
     * @return string
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     *
     * @return RefTypeElection
     */
    public function getTypeElection(){
        return $this->typeElection;
    }

    /**
     *
     * @param $typeElection
     * @return RefSousTypeElection
     */
    public function setTypeElection($typeElection){
        $this->typeElection = $typeElection;
        return $this;
    }

    /********************************************* LOGIQUE METIER ***************************************/

    public static function getIdRefSousTypeElectionByCodeUrl($codeUrl='') {
        $id = null;
        switch($codeUrl) {
            case self::CODE_URL_A_ATTE:
                $id = 10;
                break;
            case self::CODE_URL_SS:
                $id = 11;
                break;
        }
        return $id;
    }

    public static function getCodesUrls() {
        return array(
            10=>self::CODE_URL_A_ATTE,
            11=>self::CODE_URL_SS);
    }

    public function getCodeUrlById() {
        $urls = $this->getCodesUrls();
        return $urls[$this->id];
    }

}