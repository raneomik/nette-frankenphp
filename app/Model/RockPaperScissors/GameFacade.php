<?php

namespace App\Model\RockPaperScissors;

use App\Model\RockPaperScissors\Enum\Hand;
use App\Model\RockPaperScissors\Value\DuelState;
use App\Model\RockPaperScissors\Value\GameRecord;
use App\Model\RockPaperScissors\Value\WaitingRoom;
use Nette;

final class GameFacade
{
    public function __construct(
        private Nette\Database\Explorer $database,
        private GameResolver $gameResolver,
    ) {
    }

    public function record(GameRecord $record): void
    {
        $this->database
            ->table('game_records')
            ->insert((array) $record);
    }

    /**
     * last round played by this player in the room (0 if they have not played yet)
     */
    public function round(string $room, string $userId): int
    {
        return (int) $this->database
            ->table('game_records')
            ->where('room', $room)
            ->where('userId', $userId)
            ->max('round');
    }

    /**
     * presence: (re)enters the room; the latest arrival becomes the opponent of the others
     * $pageId = the page that brought the player in (null = plain joinedAt refresh, the page stays the same)
     * @return bool the player was not already in the room (false on page reload)
     */
    public function join(
        string $room,
        string $userId,
        string $username,
        ?string $pageId = null
    ): bool {
        $present = 0 < $this->database
            ->table('room_players')
            ->where('room', $room)
            ->where('userId', $userId)
            ->count();

        $this->database->query(
            'INSERT INTO room_players (room, userId, username, joinedAt, pageId) VALUES (?, ?, ?, ?, ?)
             ON CONFLICT (room, userId) DO UPDATE SET username = excluded.username, joinedAt = excluded.joinedAt,
                pageId = COALESCE(excluded.pageId, room_players.pageId)',
            $room,
            $userId,
            $username,
            time(),
            $pageId,
        );

        return false === $present;
    }

    /**
     * shortcut: opponent = last other player who entered the room; a departure is not detected and 3+ players are not handled — switch to the Mercure subscriptions API for that
     */
    public function opponent(string $room, string $userId): ?Nette\Database\Table\ActiveRow
    {
        return $this->database
            ->table('room_players')
            ->where('room', $room)
            ->where('userId != ?', $userId)
            ->order('joinedAt DESC')
            ->limit(1)
            ->fetch();
    }

    /**
     * Duel state as seen by $viewerId: the opponent, whether they already played the current round, and their hand once the round is complete.
     */
    public function duelState(string $room, string $currentUserId): DuelState
    {
        $opponent = $this->opponent($room, $currentUserId);
        if (null === $opponent) {
            return new DuelState();
        }

        $me = $currentUserId;
        $them = $opponent->userId;

        /** @var array<int, array<string, Hand>> $rounds round => [userId => hand] */
        $rounds = [];
        foreach ($this->database
            ->table('game_records')
            ->where('room', $room)
            ->where('userId', [$me, $them])
         as $row) {
            $rounds[$row->round][$row->userId] = Hand::from($row->move);
        }

        return $this->gameResolver->resolveDuel(
            $rounds,
            $me,
            $opponent,
            $this->round($room, $me),
            $this->round($room, $them),
        );
    }

    /**
     * leaving from page $pageId; no effect if a more recent page brought the player back in
     * (on reload, the browser loads the new page before sending the old one's beacon)
     * @return bool the player actually left
     */
    public function leave(string $room, string $userId, string $pageId): bool
    {
        return 0 < $this->database->table('room_players')
            ->where('room', $room)->where('userId', $userId)->where('pageId', $pageId)
            ->delete();
    }

    /**
     * @return list<WaitingRoom>
     */
    public function waitingRooms(): array
    {
        return array_map(
            fn($row) => new WaitingRoom($row['room'], $row['username'], $row['userId']),
            array_values(
                $this->database
                    ->table('room_players')
                    ->select('room, MIN(username) AS username, MIN(userId) AS userId') // single player per room (HAVING): MIN() = that player
                    ->group('room')
                    ->having('COUNT(DISTINCT userId) = 1')
                    ->order('MIN(joinedAt)')
                    ->fetchAll()
            )
        );
    }

    /**
     * Removes stale presences: otherwise a player who left stays "waiting" forever.
     * The move history (game_records) is kept.
     * shortcut: "stale" = entered more than $ttl seconds ago (joinedAt is refreshed on page open and on each move: a player watching without playing for $ttl seconds is removed); replace with real disconnection via Mercure
     * @return int number of presences removed
     */
    public function cleanRooms(int $ttl = 3600): int
    {
        return $this->database
            ->table('room_players')
            ->where('joinedAt < ?', time() - $ttl)
            ->delete();
    }
}
