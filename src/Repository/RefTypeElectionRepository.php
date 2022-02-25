<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use App\Entity\RefTypeElection;

class RefTypeElectionRepository extends EntityRepository {
    
    public function getTypesElections() {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.id NOT LIKE :typeElect');
        $qb->setParameter('typeElect', RefTypeElection::ID_TYP_ELECT_PARENT);
        $listeRefTypeElection = $qb->getQuery()->getResult();
        $array = array();
        foreach ($listeRefTypeElection as $refTe) {
            $array[$refTe->getId()] = $refTe->getCode();
        }
        return $array;
    }
    
   // retourne le/les type election selon si pe ou rp 
   public function findTypeElectionByCode ($code){
      $query = $this->createQueryBuilder('te');
       if ($code == RefTypeElection::CODE_PE) {
           $query->where('te.id =:id');
           $query->setParameter('id', RefTypeElection::ID_TYP_ELECT_PARENT);
       }elseif ($code == RefTypeElection::CODE_RP){
           $query->where('te.id in ('. RefTypeElection::ID_TYP_ELECT_ASS_ATE.','.RefTypeElection::ID_TYP_ELECT_PEE. ')');
       }
       return $query->getQuery()->getResult();
       
   }
   
}