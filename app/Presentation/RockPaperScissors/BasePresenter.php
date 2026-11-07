<?php

declare(strict_types=1);

namespace App\Presentation\RockPaperScissors;

use App\Model\RockPaperScissors\Enum\Hand;
use App\Model\RockPaperScissors\GameFacade;
use App\Model\RockPaperScissors\Value\DuelState;
use App\Model\RockPaperScissors\Value\GameRecord;
use App\Model\RockPaperScissors\Value\WaitingRoom;
use App\Model\Service\Messenger;
use App\Model\Service\Randomiser;
use App\Presentation\RockPaperScissors\TemplateData\GameTemplate;
use JetBrains\PhpStorm\NoReturn;
use Nette;
use Nette\DI\Attributes\Inject;
use Nette\Security\SimpleIdentity;

/**
 * @property GameTemplate $template
 */
final class BasePresenter extends Nette\Application\UI\Presenter
{
    #[Inject]
    public Randomiser $randomiser;

    #[Inject]
    public Messenger $messenger;

    #[Inject]
    public GameFacade $game;

    protected function startup(): void
    {
        parent::startup();

        if (! $this->getUser()->isLoggedIn()) {
            $this->getUser()->login(new SimpleIdentity(
                $this->randomiser->id(),
                ['player'],
                [
                    'username' => $this->randomiser->username(),
                ],
            ));
        }

        if (0 < $this->game->cleanRooms()) { // abandoned rooms (beacon lost) leave the lobby list too
            $this->refreshWaitingRoomList();
        }

        $this->me = $this->getUser();
    }

    private string $room;

    private Nette\Security\User $me;

    public function actionDefault(string $room = 'lobby'): void
    {
        $this->refreshWaitingRoomList();

        $this->room = $room;
        $this->template->newRoom = $this->randomiser->id();
    }

    public function actionGame(string $room): void
    {
        if (null !== $this->getSignal()) { // signals (play! via ajax or not, leave! via beacon) come through here too: only opening the page counts as an arrival
            return;
        }

        $this->room = $room;
        $myId = (string) $this->me->id;
        $this->template->pageId = $this->randomiser->id();
        $arrived = $this->game->join($room, $myId, $this->me->identity->username, $this->template->pageId);
        if (! $arrived) { // reload: already in the room, nothing new for the opponent
            return;
        }

        $opponent = $this->game->opponent($room, $myId);

        $this->messenger->toOthers($room, $myId, __DIR__ . '/template/joined.stream.latte', [
            'player' => $this->me->identity->username,
            'availableHands' => Hand::cases(),
            'state' => $this->game->duelState($room, $opponent->userId ?? ''), // as seen by the opponent
        ]);

        $this->refreshWaitingRoomList();
    }

    public function renderDefault(): void
    {
        $this->template->initData();
        $this->template->room = $this->room;

        // rendered before the leave beacon of the game page just left: never list my own room(s)
        $this->template->rooms = array_values(array_filter(
            $this->template->rooms,
            fn(WaitingRoom $waitingRoom) => $waitingRoom->userId !== (string) $this->me->id,
        ));
    }

    public function renderGame(): void
    {
        $this->template->initData();
        $this->template->room = $this->room;

        $this->template->duelState ??= $this->game->duelState($this->room, (string) $this->me->id); // already set by handlePlay in solo mode
    }

    public function formatTemplateClass(): ?string
    {
        return GameTemplate::class;
    }

    /**
     * called by sendBeacon when the page closes: leave the room and the opponent goes back to waiting
     */
    #[NoReturn]
    public function handleLeave(string $room, string $page): void
    {
        $myId = (string) $this->me->id;
        $other = $this->game->opponent($room, $myId);

        $this->game->leave($room, $myId, $page) && $other && $this->messenger->toOthers($room, $myId, __DIR__ . '/template/left.stream.latte', [
            'player' => $this->me->identity->username,
            'state' => $this->game->duelState($room, $other->userId), // as seen by the one who stays
        ]);

        $this->refreshWaitingRoomList();

        $this->terminate();
    }

    public function handlePlay(string $hand, string $room): void
    {
        $this->room = $room;
        $move = Hand::tryFrom($hand) ?? $this->error('Unknown hand');
        $me = $this->getUser();
        $myId = (string) $me->id;

        // one move per round: wait until the opponent has played the current round
        $myRound = $this->game->round($room, $myId);
        $opponent = $this->game->opponent($room, $myId);

        // solo while waiting for an opponent: the computer draws a hand, nothing is stored (no rounds or score to mix with the future duel)
        if (null === $opponent) {
            $hands = Hand::cases();
            $this->template->duelState = new DuelState('Computer', revealed: $hands[array_rand($hands)], mine: $move, solo: true);
            $this->redrawControl('opponent');
            $this->isAjax() || $this->redirect('this'); // shortcut: without JS, the redirect loses the solo result; it should go through the session
            return;
        }

        if ($myRound > ($opponent ? $this->game->round($room, $opponent->userId) : 0)) {
            $this->flashMessage('Your opponent hasn\'t played yet.', 'warning');
            $this->redrawControl('toasts');
            $this->isAjax() || $this->redirect('this');
            return;
        }

        $this->game->record(new GameRecord($room, $myId, $me->identity->username, $move->value, $myRound + 1));
        $this->game->join($room, $myId, $me->identity->username); // refreshes joinedAt: an active player is not stale for cleanRooms

        // to the others: "X played" + their opponent card from their side
        // (with no known opponent, '' = a spectator who has not played yet)
        $this->messenger->toOthers($room, $myId, __DIR__ . '/template/played.stream.latte', [
            'player' => $me->identity->username,
            'availableHands' => Hand::cases(),
            'state' => $this->game->duelState($room, $opponent->userId ?? ''),
        ]);

        // local: confirmation for me only + my opponent card (hand revealed if the round is complete)
        $this->flashMessage('Your move is in.', 'success');
        $this->redrawControl('toasts');
        $this->redrawControl('opponent');

        $this->isAjax() || $this->redirect('this'); // without JS: Post/Redirect/Get, the signal URL does not stay in the address bar
    }

    private function refreshWaitingRoomList(): void
    {
        $this->template->rooms = $this->game->waitingRooms();

        $this->messenger->toAll('lobby', __DIR__ . '/template/waitingRooms.stream.latte', [
            'rooms' => $this->template->rooms,
        ]);
    }
}
