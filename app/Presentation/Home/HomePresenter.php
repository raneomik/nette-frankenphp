<?php

declare(strict_types=1);

namespace App\Presentation\Home;

use App\Presentation\Accessory\Localized;
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
	) {
    }

    protected function startup(): void
    {
        parent::startup();

        $this->authorization->setCookie(['test-topic']);
    }

    public function actionDefault(): void
    {
        $request = $this->getHttpRequest();
        if ($request->getUrl()->getPath() !== $request->getUrl()->getBasePath()) {
            return;
        }

        $this->getHttpResponse()->addHeader('Vary', 'Accept-Language, Cookie');

        $preferred = $this->preferredLocale();
        if (null === $request->getHeader('Referer')
            && $preferred !== $this->translator->getLocale()
        ) {
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
