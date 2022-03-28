<?php
namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Form;
use App\Entity\RefUser;
use App\Utils\RefUserPerimetre;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class BaseController extends AbstractController{

    private $paginator;
    private $security;
    private $session;
    public function __construct(RefUserPerimetre $refUserPerimetre, Security $security,ManagerRegistry $em) {
        $this->refUserPerimetre = $refUserPerimetre;
        $this->security = $security;
        $this->em = $em;
    }

    public function getUser(){
        $user = $this->em->getRepository(RefUser::class)->find(133);
        $perimetre = $this->refUserPerimetre->setPerimetreForUser($user);
        $user->setPerimetre($perimetre);
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);
        $this->get('session')->set('_security_main', serialize($token));
        return $user;
    }
}



