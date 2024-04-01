<?php

namespace App\Seeder;

use PDO;

interface Seed
{
    public function run(PDO $connection): void;
}