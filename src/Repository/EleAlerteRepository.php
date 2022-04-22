<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Entity\RefCommune;
use App\Entity\EleAlerte;
use App\Entity\EleEtablissement;
use App\Entity\RefEtablissement;
use App\Entity\RefTypeElection;
use App\Entity\RefSousTypeElection;
use App\Utils\EpleUtils;


class EleAlerteRepository extends EntityRepository{

    /*
     * Fonction permettant d'avoir le type d'alerte pour un etablissement pour une campagne donnée
     */

    public function findAlerteByUaiCampagne($uai, $campagne, $sousTypeElection = null){

        $query = $this->createQueryBuilder('a')

            ->select('IDENTITY(a.typeAlerte) as typeAlerte')
            ->join('a.electionEtab', 'eleEtab')
            ->join('eleEtab.etablissement', 'e');

        //Ajout de l'expression à la requête
        $query->Where('e.uai = :uai');
        $query->andWhere('eleEtab.validation = :transmis');
        $query->andWhere('eleEtab.campagne = :campagne');
        $query->orderBy('a.id','DESC');

        $query->setParameter('uai', $uai);
        $query->setParameter('transmis', EleEtablissement::ETAT_TRANSMISSION);
        $query->setParameter('campagne', $campagne);

        // Si on a un sousTypeElection defini on fait la distinction des alertes car ils ont la meme campagne
        if(null != $sousTypeElection && $sousTypeElection instanceof RefSousTypeElection){
            $query->andWhere('eleEtab.sousTypeElection = :sousTypeElection');
            $query->setParameter('sousTypeElection', $sousTypeElection);
        }

        return $query->getQuery()->getResult();

    }

}
?>