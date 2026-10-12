<?php

declare(strict_types=1);

namespace App\Command;

use App\Controle\ControleurAccessibilite;
use App\Generateur\GenerateurSite;
use Azepo\RgaaCheck\Console\CheckCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'site:build', description: 'Génère le site statique dans le dossier dist/, puis contrôle son accessibilité')]
final class SiteBuildCommand extends Command
{
    public function __construct(
        private readonly GenerateurSite $generateur,
        private readonly ControleurAccessibilite $controleur,
        #[Autowire('%app.dossier_dist%')]
        private readonly string $dossierDist,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('brouillons', null, InputOption::VALUE_NONE, 'Générer aussi les contenus non publiés, pour les prévisualiser');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $avecBrouillons = (bool) $input->getOption('brouillons');
        $fichiers = $this->generateur->generer($avecBrouillons);

        $io->listing($fichiers);
        if ($avecBrouillons) {
            $io->warning('Brouillons inclus : ce dossier ne doit pas être mis en ligne.');
        }
        $io->writeln(sprintf('%d fichier(s) généré(s).', \count($fichiers)));

        // Une page qui enfreint une règle d'accessibilité fait échouer la génération.
        $rapport = $this->controleur->controler($this->dossierDist);
        CheckCommand::render($io, $rapport);
        if (!$rapport->passes()) {
            $io->error('Le site généré ne doit pas être mis en ligne : corriger les erreurs ci-dessus.');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
