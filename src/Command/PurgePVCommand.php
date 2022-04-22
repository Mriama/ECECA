<?php

namespace App\Command;

use App\Entity\EleEtablissement;
use App\Entity\EleFichier;
use Psr\Log\LoggerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PurgePVCommand extends Command {

    public $logger;
    public $params;
    public $doctrine;

    public function __construct(LoggerInterface $purgeLogger, ParameterBagInterface $params, ManagerRegistry $doctrine)
    {
        $this->logger = $purgeLogger;
        $this->params = $params;
        $this->doctrine = $doctrine;
        parent::__construct();
    }

    protected function configure() {
        $this->setName("pv:purge")->setDescription("Purge les PVs obsolètes");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {


        // Récupération des paramètres
        $documentsDir = $this->params->get('documents_dir');
        $yearsToPurge = $this->params->get('purge_years');

        $this->logger->info("Debut purge PVs");

        $this->logger->info('Purge des PVs de plus de '.$yearsToPurge.' an(s) du répertoire '.$this->getRootDir().$documentsDir);

        $em = $this->doctrine->getManager();

        // Récupération des établissements dont les PVs sont obsolètes
        $listEleEtab = $em->getRepository(EleEtablissement::class)->findObsoletePVs($yearsToPurge);

        $this->logger->info(count($listEleEtab). ' PVs à purger');

        $nbEtabPurges = 0;

        foreach($listEleEtab as $eleEtab){

            // Suppression du fichier dans le système de fichiers
            unlink($this->getRootDir().$documentsDir.$eleEtab->getFichier()->getUrl());

            $eleEtab->setFichier(null);
            $em->persist($eleEtab);

            $nbEtabPurges++;
        }

        $nbFichiersNettoyes = 0;

        // Purge de la table des PVs
        $listEleFichier = $em->getRepository(EleFichier::class)->findObsolete($yearsToPurge);
        foreach($listEleFichier as $eleFichier){

            // Suppression du fichier dans le système de fichiers
            unlink($this->getRootDir().$documentsDir.$eleFichier->getUrl());

            // Suppression du fichier en base
            $em->remove($eleFichier);

            $nbFichiersNettoyes++;
        }

        $em->flush();

        $this->logger->info('Traitement terminé : '.$nbEtabPurges.' résultats dont le PV a été purgé, '.$nbFichiersNettoyes. ' fichiers nettoyés de la base.');

    }

    protected function getRootDir() {
        return __DIR__ . "/../../public/";
    }

}