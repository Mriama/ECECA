<?php
namespace App\Controller;

use App\Form\EtablissementType;
use App\Model\EtablissementModel;
use App\Form\EtablissementHandler;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class EtablissementController extends AbstractController
{

    /**
      * @Route("import-ramesese", name="import_ramesese")
      */
    public function importRamseseAction(\Symfony\Component\HttpFoundation\Request $request)
    {
        $user = $this->getUser();
        if (! $user->canImportRamsese()) {
            throw new AccessDeniedException();
        }
        
        $uploadDir = $this->getParameter('ramsese_upload_dir');
        $logger = $this->get("import_logger");
        
        $em = $this->getDoctrine()->getManager();
        
        $form = $this->createFormBuilder()
            ->add('fichier', 'file', array(
            'required' => true,
            'label' => 'Fichier',
            'mapped' => false
        ))
            ->getForm();
        
        $params = array();

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {

                // Récupération de l'objet fichier
                $fichier = $form['fichier']->getData();
                
                // Stocke dans le répertoire temporaire
                $fichier->move($this->getRootDir() . $uploadDir, $fichier->getClientOriginalName());
                $url = $uploadDir . $fichier->getClientOriginalName();
                
                // Appel du service d'import
                $params = $this->get('import_ramsese_service')->import($url);
                
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