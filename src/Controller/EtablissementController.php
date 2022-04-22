<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Utils\ImportRamseseService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Request;


class EtablissementController extends AbstractController
{
    public function importRamseseAction(Request $request, ImportRamseseService $importService)
    {
        $user = $this->getUser();
        if (! $user->canImportRamsese()) {
            throw new AccessDeniedException();
        }

        $uploadDir = $this->getParameter('ramsese_upload_dir');

        $form = $this->createFormBuilder()
            ->add('fichier', FileType::class, array(
                'required' => true,
                'label' => 'Fichier',
            ))
            ->getForm();

        $params = array();

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // Récupération de l'objet fichier
                $fichier = $form->getData();

                // Stocke dans le répertoire temporaire
                $fichier->move($this->getRootDir() . $uploadDir, $fichier->getClientOriginalName());
                $url = $uploadDir . $fichier->getClientOriginalName();

                // Appel du service d'import
                $params = $importService->import($url);

            }
        }
        $params['form'] = $form->createView();
        return $this->render('etablissement/importFichier.html.twig', $params);
    }

    /**
     *
     * @return string
     */
    protected function getRootDir()
    {
        return __DIR__ . '/../../public/';
    }
}