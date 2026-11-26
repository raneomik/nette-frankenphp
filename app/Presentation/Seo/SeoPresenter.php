<?php

declare(strict_types=1);

namespace App\Presentation\Seo;

use Contributte\Translation\Translator;
use Nette;

/**
 * robots.txt and sitemap.xml, generated so that the URLs follow the router and the host
 * (a static file would carry the production host into dev and every other deployment)
 */
final class SeoPresenter extends Nette\Application\UI\Presenter
{
    /**
     * the indexable pages; game rooms are noindex (random, short-lived addresses)
     */
    private const array Pages = [':Home:default', ':RockPaperScissors:Base:default'];

    public function __construct(
        private readonly Translator $translator,
    ) {
        parent::__construct();
    }

    public function renderSitemap(): void
    {
        $this->template->pages = self::Pages;
        $this->template->locales = $this->translator->getLocalesWhitelist() ?? [$this->translator->getDefaultLocale()];
    }
}
