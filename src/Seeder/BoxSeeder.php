<?php

namespace App\Seeder;

use PDO;

class BoxSeeder implements Seed
{
    public function run(PDO $connection): void
    {
        $query = $connection->prepare('SELECT id FROM subscribers');
        $query->execute();
        $subscribers = $query->fetchAll(PDO::FETCH_ASSOC);
        $subscriber1 = $subscribers[0];
        $subscriber2 = $subscribers[1];

        $statement = $connection->prepare(
            "INSERT INTO boxes (subscriber_id, name, prayer_zone) VALUES
            ({$subscriber1['id']}, 'Orchard Tower', 'WLY01'),
            ({$subscriber1['id']}, 'United Square', 'SWK02'),
            ({$subscriber2['id']}, 'Thompson Plaza', 'JHR01'),
            ({$subscriber2['id']}, 'Peranakan Place', 'KDH01'),
            ({$subscriber2['id']}, 'Marina Boulevard', 'MLK01')"
        );

        $statement->execute();
    }
}