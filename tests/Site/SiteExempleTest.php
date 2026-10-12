<?php

declare(strict_types=1);

namespace App\Tests\Site;

use App\Contenu\DepotContenu;
use App\Site\ChargeurSite;
use App\Site\Page;
use PHPUnit\Framework\TestCase;

/**
 * Le site d'exemple livré avec le générateur se charge et se présente comme attendu.
 */
final class SiteExempleTest extends TestCase
{
    public function testLeSiteDExempleSeCharge(): void
    {
        $projet = \dirname(__DIR__, 2);
        $site = (new ChargeurSite($projet.'/config/site.yaml', new DepotContenu($projet.'/content')))->charger();

        self::assertSame('http://127.0.0.1:8000', $site->url);
        self::assertNull($site->matomo, 'Le site d\'exemple ne charge aucun script de statistiques.');
        $slugs = array_map(static fn (Page $page): string => $page->slug, $site->toutesLesPages());
        sort($slugs);
        self::assertSame(['accessibilite', 'exemple-article', 'index', 'plan_du_site'], $slugs);
        self::assertSame('http://127.0.0.1:8000/exemple-article.html', $site->urlDe($site->page('exemple-article')));
        self::assertCount(1, $site->menu);
    }

    public function testLArticleDExempleMontreLaSignature(): void
    {
        $projet = \dirname(__DIR__, 2);
        $article = (new ChargeurSite($projet.'/config/site.yaml', new DepotContenu($projet.'/content')))->charger()->page('exemple-article');

        self::assertNotNull($article->source);
        self::assertSame('Ada Exemple', $article->source->auteur);
        self::assertNotNull($article->traduction);
        self::assertSame('Alex Exemple', $article->traduction->traducteur);
    }

    public function testLePlanNeListePasLaPagePlanDuSiteMaisLeSitemapOui(): void
    {
        $projet = \dirname(__DIR__, 2);
        $site = (new ChargeurSite($projet.'/config/site.yaml', new DepotContenu($projet.'/content')))->charger();
        $duPlan = array_merge(...array_map(
            static fn (array $section): array => array_map(static fn (Page $page): string => $page->slug, $section['pages']),
            $site->plan(),
        ));
        $toutes = array_map(static fn (Page $page): string => $page->slug, $site->toutesLesPages());

        self::assertNotContains('plan_du_site', $duPlan);
        self::assertContains('accessibilite', $duPlan);
        self::assertContains('plan_du_site', $toutes);
    }
}
