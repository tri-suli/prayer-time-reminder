<?php

namespace App;

use DateTime;
use PDO;
use PDOException;
use PDOStatement;
use App\Seeder\BoxSeeder;
use App\Seeder\SongsSeeder;
use App\Seeder\SubscriberSeeder;

class Database
{
    private PDO $conn;

    public function __construct(string $dsn, string $user, string $password)
    {
        $this->connect($dsn, $user, $password);
    }

    public function getSubscribers(int $id = null): array
    {
        $query = "SELECT id, name FROM subscribers";

        if ($id) {
            $query .= " WHERE id = :id LIMIT 1";
            $query = $this->conn->prepare($query);
            $query->bindValue(':id', $id, PDO::PARAM_INT);
        } else {
            $query = $this->conn->prepare($query);
        }

        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMusicBoxes(int $id = null): array
    {
        $query = "
            SELECT
                s.id AS subscriber_id,
                s.name AS subscriber_name,
                b.id AS box_id,
                b.name AS box_name,
                b.prayer_zone
            FROM boxes AS b
            JOIN subscribers AS s ON b.subscriber_id = s.id
        ";

        if ($id) {
            $query .= " WHERE s.id = :id";
            $query = $this->conn->prepare($query);
            $query->bindValue(':id', $id, PDO::PARAM_INT);
        } else {
            $query = $this->conn->prepare($query);
        }

        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNextPrayerTime(string $time): array
    {
        // Prepare the query to get the next prayer time for all zones
        $query = $this->conn->prepare("
            SELECT id, prayer_zone, title, prayer_time_date, prayer_time_seq, prayer_time
            FROM songs
            WHERE 
                    (prayer_time_date = CURDATE() AND prayer_time >= '$time') 
                    OR prayer_time_date > CURDATE() and deleted_at IS NULL
            ORDER BY prayer_time_date, prayer_time
            LIMIT 1
        ");

        // Execute the query
        $query->execute();

        // Fetch all the results
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getNextPrayerTimeByZones(array $zones): array
    {
        // Build the placeholder string for the zones
        $placeholders = implode(',', array_fill(0, count($zones), '?'));

        // Prepare the query to get the next prayer time for all zones
        $query = $this->conn->prepare("
            SELECT id, prayer_zone, title, prayer_time_date, prayer_time_seq, prayer_time
            FROM (
                SELECT *,
                ROW_NUMBER() OVER (PARTITION BY prayer_zone ORDER BY 
                    CASE
                        WHEN prayer_time_date = CURDATE() THEN 0
                        ELSE 1
                    END,
                    prayer_time_date ASC,
                    prayer_time ASC,
                    prayer_time_seq ASC) AS rn
                FROM songs
                WHERE 
                    (prayer_time_date = CURDATE() AND prayer_time >= CURTIME()) 
                    OR 
                    prayer_time_date > CURDATE()
                    AND prayer_zone IN ($placeholders)
            ) AS ranked
            WHERE rn = 1 AND prayer_zone IN ($placeholders) AND deleted_at IS NULL
            ORDER BY prayer_zone ASC
        ");

        // Bind values for prayer zones
        foreach ($zones as $index => $zone) {
            $query->bindValue($index + 1, $zone); // Index starts from 1
            $query->bindValue($index + count($zones) + 1, $zone); // Bind for the IN clause
        }

        // Execute the query
        $query->execute();

        // Fetch all the results
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markPrayerTimeNotified(int $id): bool
    {
        $query = $this->conn->prepare(
            "UPDATE songs SET deleted_at = CURRENT_TIMESTAMP() WHERE id = :id"
        );

        $query->bindValue(':id', $id, PDO::PARAM_INT);

        return $query->execute();
    }

    public function getLatestSong(string $prayerZone)
    {
        $query = $this->conn->prepare("
            SELECT prayer_time_seq
            FROM songs
            WHERE prayer_zone = '$prayerZone'
            ORDER BY prayer_time_date DESC, prayer_time_seq DESC
            LIMIT 1
        ");
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function storeSongs(array $box, array $values): bool
    {
        $prayerTimes = [];
        foreach ($values as $value) {
            $sequence = intval($box['latest_seq'])+1;
            $date = new DateTime($value['date']);
            unset($value['date']);
            unset($value['hijri']);
            unset($value['day']);

            foreach ($value as $prayerName => $time) {
                $month = $date->format('m');
                $day = $date->format('d');
                $title = ucfirst($prayerName).sprintf(' (%s-%s)', $month, $day);
                $record = [
                    $box['subscriber_id'],
                    $box['box_id'],
                    "'$title'",
                    "'{$box['prayer_zone']}'",
                    "'{$date->format('Y-m-d')}'",
                    $sequence++,
                    "'$time'"
                ];

                $existingPrayerTime = $this->conn->prepare("
                    SELECT *
                    FROM songs
                    where subscriber_id = {$box['subscriber_id']}
                        AND box_id = {$box['box_id']}
                        AND prayer_zone = '{$box['prayer_zone']}'
                        AND title = '$title'
                        AND prayer_time = '$time'
                    LIMIT 1
                ");
                $existingPrayerTime->execute();
                $existingPrayerTime = $existingPrayerTime->fetch(PDO::FETCH_ASSOC);

                if (!$existingPrayerTime) {
                    $prayerTimes[] = sprintf('(%s)', implode(', ', $record));
                }
            }
        }

        if (! empty($prayerTimes)) {
            $query = $this->conn->prepare(
                sprintf("INSERT INTO songs (
                       subscriber_id, box_id, title, prayer_zone, prayer_time_date, prayer_time_seq, prayer_time
                       ) VALUES %s", implode(', ', $prayerTimes))
            );

            return $query->execute();
        }

        return false;
    }

    public function migrate(string $operation = null): void
    {
        if ($operation === 'refresh') {
            $statements = [
                $this->conn->prepare("DROP TABLE IF EXISTS songs"),
                $this->conn->prepare("DROP TABLE IF EXISTS boxes"),
                $this->conn->prepare("DROP TABLE IF EXISTS subscribers"),
            ];

            foreach ($statements as $statement) {
                $statement->execute();
            }

            $this->migrate();
        }

        if ($operation === 'rollback') {
            foreach (['songs', 'boxes', 'subscribers'] as $table) {
                $query = $this->conn->prepare("DELETE FROM $table");
                $query->execute();
            }

            echo "All records was rollback.".PHP_EOL;
            echo "Please re-run the seeder, thanks.".PHP_EOL;
            return;
        }

        $migrations = [
            'migrating subscribers table' => $this->prepareSubscribersTable(),
            'migrating boxes table' => $this->prepareBoxTable(),
            'migrating songs table' => $this->prepareSongsTable()
        ];

        foreach ($migrations as $message => $statement) {
            $result = $statement->execute();
            echo $message.PHP_EOL;
        }
    }

    public function seed(): void
    {
        $seeders = [
            new SubscriberSeeder(),
            new BoxSeeder(),
            new SongsSeeder()
        ];

        foreach ($seeders as $seeder) {
            $seeder->run($this->conn);
        }
    }

    private function connect(string $dsn, string $user, string $password): void
    {
        try {
            $this->conn = new PDO($dsn, $user, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            print($e->getMessage()).PHP_EOL;
            die();
        }
    }

    private function prepareSubscribersTable(): PDOStatement|false
    {
        return $this->conn->prepare("CREATE TABLE IF NOT EXISTS subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(125) NOT NULL
        )");
    }

    private function prepareBoxTable(): PDOStatement|false
    {
        return $this->conn->prepare("CREATE TABLE IF NOT EXISTS boxes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subscriber_id INT,
            name VARCHAR(125) NOT NULL,
            prayer_zone VARCHAR(10) NOT NULL,
            FOREIGN KEY (subscriber_id) REFERENCES subscribers (id)
        )");
    }

    private function prepareSongsTable(): PDOStatement|false
    {
        return $this->conn->prepare("CREATE TABLE IF NOT EXISTS songs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subscriber_id INT,
            box_id INT,
            title VARCHAR(150) NOT NULL,
            prayer_zone VARCHAR(10) NOT NULL,
            prayer_time_date DATE NOT NULL,
            prayer_time_seq INT NOT NULL,
            prayer_time TIME NOT NULL,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (subscriber_id) REFERENCES subscribers (id),
            FOREIGN KEY (box_id) REFERENCES boxes (id)
        )");
    }
}
