<?php


namespace App\Components;

use App\Models\Tournament\Interfaces\TournamentInterface;

class Utils
{

    /**
     * Пишем параметры раунда в лог
     * @param TournamentInterface
     */
    public static function log(TournamentInterface $tournament)
    {
        $logsPath = ROOT . DIRECTORY_SEPARATOR . 'tournament_logs';
        if (!is_dir($logsPath)) {
            mkdir($logsPath);
        }
        $filePath = $logsPath . DIRECTORY_SEPARATOR . date('y-m-d') . '_tournament_log.txt';

        $date = date('Y-m-d H:i:s');

        $data = '';
        $data .= "$date {$tournament->name}" . "<br>";
        $data .= "Раунд {$tournament->current_round}" . "<br><br>";
        $data .= "Текущее распределение команд:" . "<br><br>";

        foreach ($tournament->getToss() ?? [] as $pair) {
            $data .= $pair[0] . '-' . $pair[1] . "<br>";
        }
        $data .= "<br>";
        foreach ($tournament->getPlayers() as $player) {
            $data .= $player . "<br>";
        }
        $data .= "______________________________________" . "<br>";


        file_put_contents($filePath, $data . "<br>", FILE_APPEND | LOCK_EX);
    }

    private static function trimValue($value)
    {
        if (is_array($value)) {
            array_filter($value, 'self::trimValue');
            return $value;
        }

        $value = strval($value);
        return trim($value);
    }

    public static function getUserValues(array $postData): array
    {
        $values = [];
        $data = [];
        foreach ($postData as $key => $value) {
            $value = strval($value);
            $data[$key] = trim($value);
        }
        $values['username'] = isset($data['username']) ? strval($data['username']) : '';
        $values['name'] = isset($data['name']) ? strval($data['name']) : '';
        $values['email'] = isset($data['email']) ? strval($data['email']) : '';
        $values['password'] = isset($data['password']) ? strval($data['password']) : '';

        return $values;
    }

    public static function getTournamentValues(array $postData, int $ownerId): array
    {
        $values = array_filter($postData, 'self::trimValue');

        $result = [
            'name' => $values['t_name'] ?? '',
            'date' => $values['t_date'] ?? '',
            'prize_pool' => $values['t_prize_pool'] ?? '',
            'owner_id' => $ownerId,
            'type' => (int)$values['t_type'],
            'players' => $values['players'] ?? '',
        ];

        if (isset($values['t_regions'])) {
            $result += ['regions' => explode(',', $values['t_regions'])];
        }

        return $result;
    }

    /**
     * Estimates count of rounds till the end
     * @param array $players with amount of lives
     * @return int
     */
    public static function estimateRounds(array $players): int
    {
        $p = array_filter($players);

        for ($round = 0; count($p) >= 10; $round++) {
            shuffle($p);
            $out = array_splice($p, 0, (count($p) - count($p) % 10) / 2);
            array_walk($out, function (&$e) {
                $e--;
            });
            $p = array_merge($p, array_filter($out));
        }
        return $round;
    }

    public static function getDotaTeamNames()
    {
        $teamNames = array_map('str_getcsv', file(ROOT . '/../Heroes of Dota.csv'));
        $teamNames = array_column($teamNames, 0);
        shuffle($teamNames);
        return $teamNames;
    }

    public static function responseFail()
    {
        $query = http_build_query(['notify' => 'fail']);
        http_response_code(500);
        header("Location: /tournament?$query");
        exit;
    }

    public static function responseSuccess()
    {
        http_response_code(302);
        $query = http_build_query(['notify' => 'success']);
        header("Location: /tournament?$query");
        exit;
    }
}