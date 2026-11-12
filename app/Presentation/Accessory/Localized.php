<?php

declare(strict_types=1);

namespace App\Presentation\Accessory;

use Contributte\Translation\LocalesResolvers\Header;
use Contributte\Translation\Translator;
use Nette\Application\Attributes\Persistent;
use Nette\DI\Attributes\Inject;

/**
 * The language is part of the URL (RouterFactory): persistent, so every link keeps it, across the presenters using this trait.
 */
trait Localized
{
    #[Persistent]
    public ?string $locale = null;

    #[Inject]
    public Translator $translator;

    /**
     * remembers the language last shown in this browser, for preferredLocale()
     */
    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->getHttpResponse()->setCookie('locale', $this->translator->getLocale(), '1 year');
    }

    /**
     * where a locale-less link (invite, push) lands: the language last shown in this browser, else Accept-Language
     */
    private function preferredLocale(): string
    {
        $seen = $this->getHttpRequest()->getCookie('locale');

        return in_array($seen, $this->translator->getLocalesWhitelist() ?? [], true)
            ? $seen
            : new Header($this->getHttpRequest())->resolve($this->translator) ?? $this->translator->getDefaultLocale();
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function translate(
        string $id,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null,
    ): string {
        // count first: the array-first signature of contributte rejects a null domain
        return $this->translator->translate($id, null, $parameters, $domain, $locale);
    }
}
