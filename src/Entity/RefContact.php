<?php

namespace App\Entity;

use App\Utils\EpleUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * RefContact
 *
 * @ORM\Table(name="ref_contact", options={"collate"="utf8_general_ci"}, uniqueConstraints={@ORM\UniqueConstraint(name="contactUnique", columns={"id_type_election", "id_zone"})})
 * @ORM\Entity(repositoryClass="App\Repository\RefContactRepository")
 */
class RefContact
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="id_zone", type="string", length=3)
     */
    private $idZone;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=50, nullable=true)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="prenom", type="string", length=50, nullable=true)
     */
    private $prenom;

    /**
     * @var string
     *
     * @ORM\Column(name="email_1", type="string", length=255)
     */
    private $email1;

    /**
     * @var string
     *
     * @ORM\Column(name="email_2", type="string", length=255, nullable=true)
     */
    private $email2;

    /**
     * @var string
     *
     * @ORM\Column(name="telephone", type="string", length=20)
     */
    private $telephone;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RefTypeElection")
     * @ORM\JoinColumn(name="id_type_election", referencedColumnName="id", nullable=false)
     */
    private $typeElection;


    /**
     * Constructeur de base
     * @param string $typeElect
     */
    public function __construct($typeElect = null) {
        $this->id = 0;
        $this->email2 = '';
        $this->telephone = '';
        if (!empty($typeElect)) { $this->typeElection = $typeElect; }
    }

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
     * Set idZone
     *
     * @param string $idZone
     */
    public function setIdZone($idZone) {
        $this->idZone = $idZone;
    }

    /**
     * Get idZone
     *
     * @return string
     */
    public function getIdZone() {
        return $this->idZone;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($nom)
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom()
    {
        return $this->prenom;
    }

    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;
        return $this;
    }

    /**
     * Set email1
     *
     * @param string $email1
     * @return RefContact
     */
    public function setEmail1($email1)
    {
        $this->email1 = $email1;

        return $this;
    }

    /**
     * Get email1
     *
     * @return string
     */
    public function getEmail1()
    {
        return $this->email1;
    }

    /**
     * Set email2
     *
     * @param string $email2
     * @return RefContact
     */
    public function setEmail2($email2)
    {
        $this->email2 = $email2;

        return $this;
    }

    /**
     * Get email2
     *
     * @return string
     */
    public function getEmail2()
    {
        return $this->email2;
    }

    /**
     * Set telephone
     *
     * @param string $telephone
     * @return RefContact
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * Get telephone
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Set typeElection
     *
     * @param RefTypeElection $typeElection
     */
    public function setTypeElection(
        RefTypeElection $typeElection) {
        $this->typeElection = $typeElection;
    }

    /**
     * Get typeElection
     *
     * @return string
     */
    public function getTypeElection() {
        return $this->typeElection;
    }

    /********************************************** LOGIQUE METIER *********************************************/

    public function isEmail1Valid(ExecutionContextInterface $context) {
        if ($this->typeElection == null || $this->typeElection->getCode() == 3) {
            if ((!empty($this->email1) && !EpleUtils::isEmailValid($this->email1))
                || (!empty($this->email2) && !EpleUtils::isEmailValid($this->email2))) {

                $context->addViolation('L\'adresse électronique 0 est invalide. Le format correct est de type : XXXX@XXXX.XX , X étant un caractère alphanumérique, avec un seul caractère « @ » et ne doit pas contenir de caractères spéciaux.', array($this->email1), null);
            }
        } else {
            if (!empty($this->email1) && !EpleUtils::isEmailValid($this->email1)) {
                $context->addViolation('L\'adresse électronique 0 est invalide. Le format correct est de type : XXXX@XXXX.XX , X étant un caractère alphanumérique, avec un seul caractère « @ » et ne doit pas contenir de caractères spéciaux.', array($this->email1), null);            }
        }

    }

    public function isEmail2Valid(ExecutionContextInterface $context) {
        if ($this->typeElection != null && $this->typeElection->getCode() != 3) {
            if ((!empty($this->email2) && !EpleUtils::isEmailValid($this->email2))) {
                $context->addViolation('L\'adresse électronique 0 est invalide. Le format correct est de type : XXXX@XXXX.XX , X étant un caractère alphanumérique, avec un seul caractère « @ » et ne doit pas contenir de caractères spéciaux.', array($this->email2), null);
            }
        } else {
            if ((!empty($this->email1) && !EpleUtils::isEmailValid($this->email1)) || (!empty($this->email2) && !EpleUtils::isEmailValid($this->email2))) {
                $context->addViolation('L\'adresse électronique 0 est invalide. Le format correct est de type : XXXX@XXXX.XX , X étant un caractère alphanumérique, avec un seul caractère « @ » et ne doit pas contenir de caractères spéciaux.', array($this->email2), null);
            }
        }
    }

    public function isTelephoneValid(ExecutionContextInterface $context) {
        //Vérifie chaine de type +etc. ou 0etc. séparé par - ou / ou . ou espace
        // Mais +0etc. est interdite
        $regExpTelToutType = '#^(0|\+[0-9]{0,2}[-. \/]*)[1-68]([-. \/]*[0-9]{2}){4}$#';
        /*anomalie 0122047*/
        if ((!empty($this->email1) or !empty($this->email2)) and $this->telephone != "" and !preg_match($regExpTelToutType, $this->telephone) ) {
            $context->addViolation(
                'Le numéro de téléphone %saisie% est invalide. Il doit contenir au moins 10 chiffres. Les autres caractères autorisés sont : l\'espace, le point, le tiret (-), la barre oblique (/) et le plus (+).',
                array('%saisie%' => $this->telephone), null);
        }
    }


}
