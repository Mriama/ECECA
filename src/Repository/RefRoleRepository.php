<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

class RefRoleRepository extends EntityRepository {

	function findSymfonyRolesStartWith($debutNomRole) {
		$qb = $this->createQueryBuilder('r');
		$qb->where("r.role like :debutNomRole")
			->setParameter('debutNomRole', $debutNomRole.'%');

		$r = $qb->getQuery()->getResult();
		return $this->getRolesSF2($r);
	}
	
	private function getRolesSF2($roles) {
		$rolesSF2 = array();
		if(!empty($roles)) {
			$getRoleOfRefRole = function($r) { return $r->getRole();};
			$rolesSF2 = array_map($getRoleOfRefRole, $roles);
		}
		return $rolesSF2;
	}

}