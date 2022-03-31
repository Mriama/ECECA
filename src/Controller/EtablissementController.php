<?php
namespace App\Controller;

use App\Form\EtablissementType;
use App\Model\EtablissementModel;
use App\Form\EtablissementHandler;
use App\Controller\BaseController;
use Psr\Log\LoggerInterface;
use App\Utils\ImportRamseseService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Request;


class EtablissementController extends BaseController
{

    /**
      * @Route("importRamsese", name="import_ramesese")
      */
    public function importRamseseAction(ParameterBagInterface $parameters,Request $request,LoggerInterface $logger,ImportRamseseService $importService)
    {
        $user = $this->getUser();
        if (! $user->canImportRamsese()) {
            throw new AccessDeniedException();
        }
        
        $uploadDir = $parameters->get('ramsese_upload_dir');
        //$logger->get("import_logger");
        
        $em = $this->getDoctrine()->getManager();
        
        $form = $this->createFormBuilder()
            ->add('fichier', FileType::class, array(
            'required' => true,
            'label' => 'Fichier',
            'mapped' => false
        ))
            ->getForm();
        
        $params = array();

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // Récupération de l'objet fichier
                $fichier = $form['fichier']->getData();
                
                // Stocke dans le répertoire temporaire
                $fichier->move($this->getRootDir() . $uploadDir, $fichier->getClientOriginalName());
                $url = $uploadDir . $fichier->getClientOriginalName();
                
                // Appel du service d'import
                $params = $importService->import($url);
                
            }
        }
        $params['form'] = $form->createView();
        return $this->render('Etablissement/importFichier.html.twig', $params);
    }

    /**
     *
     * @return string
     */
    protected function getRootDir()
    {
        return __DIR__ . '/../../../../web/';
    }
}