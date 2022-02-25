<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\SecurityContext;

use App\Entity\RefUser;
use App\Entity\RefProfil;
use App\Utils\RefUserPerimetre;

/**** cleartrust ***/
use App\Entity\RefTypeElection;
use Monolog\Logger;


class DatabaseUserProvider implements UserProviderInterface
{
    protected $doctrine;
    private $refUserPerimetreService;
    private $session;
    private $container;
    
    // Type d'élection
    const ECECA = 'ECECA';
    
    const IEN_FONCT_ADM = 'IEN1D';
    const CE_FONCT_ADM = 'DIR';
    
    const ADMIN_LOGIN = 'DGESCO';
    const IEN_LOGIN = 'IEN';
    const CE_LOGIN = 'CE';
    const DE_LOGIN = 'DE';
	const DSDEN_LOGIN = 'DSDEN';
    
    const FR_EDU_RES_DEL = '|/redirectionhub';
    const UAI_LENGTH = 8;
    
    public function __construct($doctrine, $refUserPerimetreService, $session, $container) {
        $this->doctrine = $doctrine;
        $this->refUserPerimetreService = $refUserPerimetreService;
        $this->session = $session;
        $this->container = $container;
    }
    
    public function loadUserByUsername($username)
    {		
        $user = $this->doctrine->getRepository('EPLEElectionBundle:RefUser')->findOneByLogin($username);
        if (null == $user) {
            // try to find him in RefEtablissement
            $etab = $this->doctrine->getRepository('EPLEElectionBundle:RefEtablissement')->findOneByUai($username);
            if (null != $etab) {
                $user = new RefUser();
                $user->setLogin($username);
                $user->setPassword($username);
                
                $profil = $this->doctrine->getRepository('EPLEElectionBundle:RefProfil')->findOneByCode(RefProfil::CODE_PROFIL_CE);
                $user->setProfil($profil);
                
                $lst_uai = array($etab->getUai());
                $this->session->set('lst_uai',$lst_uai);
                
            }
        }
        
        if (empty($user)) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
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