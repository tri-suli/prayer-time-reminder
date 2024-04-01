<?php

namespace App\Seeder;

use PDO;

class SongsSeeder implements Seed
{
    public function run(PDO $connection): void
    {
        $query = $connection->prepare("
            SELECT
                s.id AS subscriber_id,
                b.id AS box_id
            FROM boxes AS b
            JOIN subscribers AS s ON b.subscriber_id = s.id
            WHERE b.prayer_zone = 'WLY01' and s.name = 'The Café'
        ");
        $query->execute();
        $musicBox = $query->fetch(PDO::FETCH_ASSOC);

        $statement = $connection->prepare(
            "INSERT INTO songs (title, subscriber_id, box_id, prayer_zone, prayer_time_date, prayer_time_seq, prayer_time) VALUES
            ('Subuh (03-09)', {$musicBox['subscriber_id']}, {$musicBox['box_id']}, 'WLY01', '2024-03-09', 1, '06:14'),
            ('Zohor (03-09)', {$musicBox['subscriber_id']}, {$musicBox['box_id']}, 'WLY01', '2024-03-09', 2, '13:26'),
            ('Asar (03-09)', {$musicBox['subscriber_id']}, {$musicBox['box_id']}, 'WLY01', '2024-03-09', 3, '16:38'),
            ('Maghrib (03-09)', {$musicBox['subscriber_id']}, {$musicBox['box_id']}, 'WLY01', '2024-03-09', 4, '19:28'),
            ('Isyak (03-09)', {$musicBox['subscriber_id']}, {$musicBox['box_id']}, 'WLY01', '2024-03-09', 5, '20:37')"
        );

        $statement->execute();
    }
}