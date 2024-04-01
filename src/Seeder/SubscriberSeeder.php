<?php

namespace App\Seeder;

use PDO;

class SubscriberSeeder implements Seed
{
    public function run(PDO $connection): void
    {
        $statement = $connection->prepare(
            "INSERT INTO subscribers (name) VALUES ('The Café'), ('My Restaurant')"
        );

        $statement->execute();
    }
}