<?php

namespace App\Security;

use App\Entity\RefEtablissement;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\RefUser;
use App\Entity\RefProfil;
use App\Utils\RefUserPerimetre;
use App\Entity\RefTypeElection;


class DatabaseUserProvider implements UserProviderInterface
{
    protected $em;
    private $refUserPerimetreService;
    private $session;

    public function __construct(EntityManagerInterface $em, RefUserPerimetre $refUserPerimetreService, SessionInterface $session) {
        $this->em = $em;
        $this->refUserPerimetreService = $refUserPerimetreService;
        $this->session = $session;
    }

    public function loadUserByUsername($username)
    {
        $user = $this->em->getRepository(RefUser::class)->findOneByLogin($username);
        if (null == $user) {
            // try to find him in RefEtablissement
            $etab = $this->em->getRepository(RefEtablissement::class)->findOneByUai($username);
            if (null != $etab) {
                $user = new RefUser();
                $user->setLogin($username);
                $user->setPassword($username);

                $profil = $this->em->getRepository(RefProfil::class)->findOneByCode(RefProfil::CODE_PROFIL_CE);
                $user->setProfil($profil);

                $lst_uai = array($etab->getUai());
                $this->session->set('lst_uai',$lst_uai);

            }
        }

        if (empty($user)) {
            throw new UserNotFoundException(sprintf('Username "%s" does not exist.', $username));
        } else {
            $lst_uai = $this->session->get('lst_uai');

            // type_elec s'obtient en fonction du role :
            //  ROLE_RES_GLO_PAR <=> ELECT_PARENT,  	ROLE_RES_GLO_PE <=> ELECT_ASS_ATE et ELECT_PEE
            $type_elec = array();
            if (in_array('ROLE_RES_GLO_PAR', $user->getRoles())) {
                array_push($type_elec, RefTypeElection::ID_TYP_ELECT_PARENT);
            }

            if (in_array('ROLE_RES_GLO_PE', $user->getRoles())) {
                array_push($type_elec, RefTypeElection::ID_TYP_ELECT_ASS_ATE);
                array_push($type_elec, RefTypeElection::ID_TYP_ELECT_PEE);
            }

            // Mise en session du type d'élection choisi
            $this->session->set('type_elec', $type_elec);

            $lst_numero_departement = $this->session->get('lst_numero_departement');

            $perimetre = $this->refUserPerimetreService->setPerimetreForUser($user, $lst_uai, $type_elec, $lst_numero_departement);
            $user->setPerimetre($perimetre);
        }

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof RefUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'App\Entity\RefUser';
    }
}