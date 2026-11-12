<?php

declare(strict_types=1);

namespace App\Presentation\Home;

use App\Presentation\Accessory\Localized;
use Contributte\Translation\LocalesResolvers\Header;
use Nette;
use Raneomik\NetteMercure\BroadcasterInterface;
use Raneomik\NetteMercure\Core\Publish\Latte\TurboStreamAction;
use Raneomik\NetteMercure\Core\Subscribe\AuthorizationInterface;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    use Localized;

	public function __construct(
	    private readonly BroadcasterInterface $broadcaster,
	    private readonly AuthorizationInterface $authorization,
	    private readonly Header $browserLocale,
	) {
    }

    protected function startup(): void
    {
        parent::startup();

        $this->authorization->setCookie(['test-topic']);
    }

    public function actionDefault(): void
    {
        // direct arrival on "/" (typed, bookmark): go to the browser's language; search engines send no Accept-Language and stay here
        // shortcut: "no Referer" tells an arrival from a click on the site (language menu, logo), which must stay; remember an explicit choice in a cookie if that proves too coarse
        $request = $this->getHttpRequest();
        if (
            $request->getUrl()->getPath() !== $request->getUrl()->getBasePath()
            || null !== $request->getHeader('Referer')
        ) {
            return;
        }

        $this->getHttpResponse()->addHeader('Vary', 'Accept-Language');
        $preferred = $this->browserLocale->resolve($this->translator);
        if (null !== $preferred && $preferred !== $this->translator->getDefaultLocale()) {
            $this->redirect('this', [
                'locale' => $preferred,
            ]);
        }
    }

	public function handleDispatch(string $type = 'plain'): void
	{
        if ('turbo' === $type) {
            $this->broadcaster->broadcast(
                topics: ['turbo-test-topic'],
                data: 'home.helloTurbo',
                options: [
                    'template' => 'test.stream.latte',
                    'target' => 'toast-container',
                    'action' => TurboStreamAction::Append,
                ],
            );

            return;
        }

        $this->broadcaster->broadcast(
            topics: ['test-topic'],
            data: 'home.hello',
            template: 'test.latte',
        );
    }
}
