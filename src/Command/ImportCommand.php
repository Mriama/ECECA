<?php
namespace App\Command;

use App\Utils\ImportRamseseService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImportCommand extends Command
{
    public $logger;
    public $params;
    public $importRamseseService;

    public function __construct(LoggerInterface $importLogger, ParameterBagInterface $params, ImportRamseseService $importRamseseService)
    {
        $this->logger = $importLogger;
        $this->params = $params;
        $this->importRamseseService = $importRamseseService;
        parent::__construct();
    }

    /**
     *
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::configure()
     */

    protected function configure()
    {
        $this->setName("ramsese:import")->setDescription("Commande pour importer les fichiers communes et ramsese");
    }

    /**
     * Pour lancer la commande : php app/console ramsese:import --env=[int|dev]
     * Choisir l'environnement pour que doctrine lise le bon fichier de paramètres (parameters_dev.yml ou parameters_int.yml)
     * En production le MEN utilise toujours le fichier parameters.yml et remplace les valeurs ZZ_* à l'aide d'un script
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Récupération des paramètres
        $ftpServer = $this->params->get('ramsese_ftp_server');
        $ftpPort = $this->params->get('ramsese_ftp_port');
        $ftpUser = $this->params->get('ramsese_ftp_user');
        $ftpPassword = $this->params->get('ramsese_ftp_password');
        $ftpPath = $this->params->get('ramsese_ftp_path');

        // Patterns des fichiers
        $uploadDir = $this->params->get('ramsese_upload_dir');

        $this->logger->info("Informations de connexion : " . $ftpUser . ':' . $ftpPassword . '@' . $ftpServer . ':' . $ftpPort .'|'. $ftpPath);

        // connexion ftp
        $idConn = ftp_connect ($ftpServer, $ftpPort);

        if ($idConn && ftp_login($idConn, $ftpUser, $ftpPassword)) {
            ftp_pasv($idConn, true);

            // Tri des fichier dans le dossier d'exploitation
            $listeFichiers = ftp_nlist($idConn, $ftpPath);
            natsort($listeFichiers);
            $listeFichiers = array_reverse($listeFichiers, true);

            //Traitement des fichiers de la liste
            if (count($listeFichiers) > 0) {

                //////////////////////
                ///     EVOL 16 Fichier des communes
                /////////////////////
                $arrayFichierCommunes = array();
                //vérification de l'existence d'un fichier communes

                // Patterns des fichiers
                $refComPattern = $this->params->get('communes_refcom_pattern');
                foreach ($listeFichiers as $key => $fichier) {
                    $url = $this->getRootDir() . $uploadDir . basename($fichier);
                    if (strpos(basename($url), $refComPattern) === 0) {
                        array_push($arrayFichierCommunes, $fichier);
                        // copie du fichier des communes dans le repertoire de traitement
                        $this->logger->info('Téléchargement du fichier des communes' . $fichier . ' dans le répertoire ' . $this->getRootDir() . $uploadDir);
                        if (ftp_get($idConn, $url, $fichier, FTP_BINARY)) {
                            $this->logger->info("Le fichier des communes: " . $fichier . " est recupéré dans le répertoire de traitement");
                            // supprimer le fichier des communes sur le serveur
                            if (ftp_delete($idConn, $fichier)) {
                                $this->logger->info("Le fichier des communes: " . $fichier . " est supprimé du répertoire de dépot du serveur");
                            } else {
                                $this->logger->error("Probleme lors de la suppression du fichier des communes dans le répertoire de dépot du serveur : " . $fichier);
                            }
                            // Appel du service d'import des communes
                            $this->getApplication()->getKernel()->getContainer()->get('import_commune_service')->import($url);
                        } else {
                            $this->logger->error("Probleme lors de la recuperation du fichier : " . basename($url));
                        }
                    }
                }
                if(sizeof($arrayFichierCommunes) == 0){
                    $this->logger->info("Aucun fichier des communes n’était présent : l’import des communes n’a pas pu être réalisé");
                }

                //////////////////////
                ///     FIN EVOL 16
                /////////////////////

                $this->logger->info("Traitement de : " . count($listeFichiers) . " fichier(s)");

                foreach ($listeFichiers as $fichier) {
                    if(!in_array($fichier, $arrayFichierCommunes)){
                        // copie du fichier dans le repertoire de traitement
                        $this->logger->info('Téléchargement du fichier ' . $fichier . ' dans le répertoire ' . $this->getRootDir() . $uploadDir);
                        $url = $this->getRootDir() . $uploadDir . basename($fichier);
                        if (ftp_get($idConn, $url, $fichier, FTP_BINARY)) {
                            $this->logger->info("Le fichier : " . $fichier . " est recupéré dans le répertoire de traitement");

                            // supprimer le fichier sur le serveur
                            if (ftp_delete($idConn, $fichier)) {
                                $this->logger->info("Le fichier : " . $fichier . " est supprimé du répertoire de dépot du serveur");
                            } else {
                                $this->logger->error("Probleme lors de la suppression du fichier dans le répertoire de dépot du serveur : " . $fichier);
                            }

                            // Appel du service d'import
                            $this->importRamseseService->import($url);

                        } else {
                            $this->logger->error("Probleme lors de la recuperation du fichier : " . basename($url));
                        }
                    }
                }

            } else {
                $this->logger->info("Aucun fichier à traiter");
            }
            ftp_close($idConn);
        }else{
            $this->logger->error("Erreur de connexion au serveur FTP !");
        }
    }

    protected function getRootDir()
    {
        return __DIR__ . "/../../public/";
    }
}