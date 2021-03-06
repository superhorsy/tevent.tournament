<?php

namespace App\Models\Tournament;

use App\Components\TDG;
use App\Models\Tournament\Interfaces\TournamentInterface;
use PDO;

class TournamentTDG extends TDG
{
    public function saveTournament(TournamentInterface $tournament): ?int
    {
        $values = [
            'name' => $tournament->name,
            'date' => $tournament->date,
            'owner_id' => $tournament->owner_id,
            'prize_pool' => $tournament->prize_pool,
            'type' => $tournament->type,
        ];
        if (isset($tournament->regions)) {
            $values = array_merge($values, ['regions' => json_encode($tournament->regions, JSON_UNESCAPED_UNICODE)]);
        }

        $this->insertValues($values);
        $id = $this->connection->lastInsertId();
        return $id ?: null;
    }

    public function updateTournament(TournamentInterface $tournament): void
    {
        $values = [
            'name' => $tournament->name,
            'date' => $tournament->date,
            'owner_id' => $tournament->owner_id,
            'status' => $tournament->status,
            'current_round' => $tournament->current_round,
            'round_count' => $tournament->round_count,
            'toss' => json_encode($tournament->getToss(), JSON_UNESCAPED_UNICODE),
            'prize_pool' => $tournament->prize_pool,
            'type' => $tournament->type,
            'is_shown' => $tournament->is_shown,
        ];

        if (method_exists($tournament, 'getRegions')) {
            $values = array_merge($values, ['regions' => json_encode($tournament->regions, JSON_UNESCAPED_UNICODE)]);
        }

        $this->updateValues($values, $tournament->getId());
    }

    /**
     * @param int $ownerID
     * @return array|null TournamentInterface[]
     */
    public function getTournamentsByUser(int $ownerID): ?array
    {
        $tournaments = $this->connection->query("SELECT id, type FROM `tournament` WHERE `owner_id` = '$ownerID'")
            ->fetchAll(PDO::FETCH_ASSOC);
        $tournaments_array = array();
        foreach ($tournaments as $tournament) {
            if (in_array((int)$tournament['type'], TournamentFactory::TOURNAMENT_TYPE)) {
                $tournaments_array[] = $this->getObj((int)$tournament['id'], (int)$tournament['type']);
            }
        }
        return $tournaments_array ? $tournaments_array : null;
    }

    public function getTournamentById(int $tournamentID, int $type = null): ?TournamentInterface
    {
        $stmt = $this->connection->prepare("SELECT id, type FROM `tournament` WHERE `id` = ?");
        $stmt->bindValue(1, $tournamentID, PDO::PARAM_INT);
        $stmt->execute();
        $tournament = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$tournament) return null;

        if (!$type) {
            if (in_array((int)$tournament[0]['type'], TournamentFactory::TOURNAMENT_TYPE)) {
                $tournament = $this->getObj((int)$tournament[0]['id'], (int)$tournament[0]['type']);
            }
        } else {
            $tournament = $this->getObj((int)$tournament[0]['id'], (int)$type);
        }
        return $tournament;
    }

    public function deleteTournamentById(string $tournamentID): bool
    {
        $stmt = $this->connection->prepare("DELETE FROM `{$this->table}` WHERE `id` = ?");
        $stmt->bindValue(1, $tournamentID, PDO::PARAM_INT);
        return $stmt->execute() ? true : false;
    }

    protected function getObj($id, $type): TournamentInterface
    {
        return $this->connection->query("SELECT * FROM `tournament` WHERE `id` = {$id}")
            ->fetchObject(TournamentFactory::TOURNAMENT_CLASS[(int)$type]);
    }

}