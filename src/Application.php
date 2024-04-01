<?php

declare(strict_types=1);

namespace App;

use Dotenv\Dotenv;
use Exception;

class Application
{
    public readonly string $baseDir;
    public readonly PrayerGenerator $generator;
    private Database $db;

    public function __construct(string $baseDir = null)
    {
        if (is_null($baseDir)) {
            $this->baseDir = dirname(__DIR__);
        } else {
            $this->baseDir = $baseDir;
        }

        $this->loadEnvironment($this->baseDir);

        $this->db = new Database($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
        $this->generator = new PrayerGenerator($this->db);

    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        $this->renderMenu();

        while (true) {
            echo PHP_EOL."run: ";
            $input = trim(fgets(STDIN));
            $inputs = explode(' ', $input);

            if (count($inputs) === 1 && in_array($inputs[0], ['e', 'q', 'exit', 'quit'])) {
                echo "Good Bye!".PHP_EOL;
                break;
            } else if ($inputs[0] === 'subs') {
                $id = null;
                $withBoxes = false;

                if (str_contains($input, 'boxes')) {
                    $withBoxes = true;
                }

                if (str_contains($input, '--id=')) {
                    if ($withBoxes) {
                        $id = str_replace('subs boxes --id=', '', $input);
                    } else {
                        $id = str_replace('--id=', '', $inputs[1]);
                    }
                }

                if ($withBoxes) {
                    $subscribers = $this->db->getMusicBoxes(intval($id));
                    $this->generator->showSubscribersWithBoxes($subscribers);
                } else {
                    $subscribers = $this->db->getSubscribers(intval($id));
                    $this->generator->showSubscribers($subscribers);
                }
            } else if ($inputs[0] === 'notify') {
                if (str_contains($input, '--zones=')) {
                    $params = str_replace('notify --zones=', '', $input);
                    $zones = array_values(
                        array_filter(
                            array_map(fn (string $zone) => rtrim(ltrim($zone)), explode(',', $params)),
                            fn (string $zone) => ! empty($zone)
                        )
                    );
                    $songs = $this->db->getNextPrayerTimeByZones($zones);
                    $this->generator->notify($songs);
                } else {
                    $this->generator->notify([]);
                }
            } else if ($input === 'songs:update') {
                $result = $this->generator->updatePrayerTimes();
                if (! empty($result)) {
                    foreach ($result as $prayerTime) {
                        echo sprintf('%s prayer time was added to zone %s', $prayerTime['total'], $prayerTime['zone']).PHP_EOL;
                    }
                } else {
                    echo "All prayer times already updated.".PHP_EOL;
                }
            } else if (str_contains($input, 'migrate')) {
                echo "Applying migrations!".PHP_EOL;
                $this->db->migrate(str_replace('migrate:', '', $input));
                echo "Applied migrations!".PHP_EOL;
            } else if ($input === 'seed') {
                $this->db->seed();
            }
        }
    }

    private function renderMenu(): void
    {
        echo '|-------------------------------------------------------------------------------------|'.PHP_EOL;
        echo "| Welcome to Prayer Time Generator!                                                   |".PHP_EOL;
        echo "| Please read below commands to interact with the app:                                |".PHP_EOL;
        echo '|-------------------------------------------------------------------------------------|'.PHP_EOL;
        echo "| App:                                                                                |".PHP_EOL;
        echo "|   [subs [--id=SUB_ID]]            Display subscriber record.                        |".PHP_EOL;
        echo "|   [subs boxes [--id=SUB_ID]]      Display subscriber list with music boxes.         |".PHP_EOL;
        echo "|   [songs [--id=SUB_ID]]           Display subscriber songs with music boxes.        |".PHP_EOL;
        echo "|   [songs:update]                  Update prayer times boxes.                        |".PHP_EOL;
        echo "|   [notify [--zones=PRAYER_ZONE]]  Display prayer time message base on prayer zones. |".PHP_EOL;
        echo "|   [e|q|exit|quit]                 Close the application.                            |".PHP_EOL;
        echo "| Database:                                                                           |".PHP_EOL;
        echo "|   [migrate]                       Run database migration.                           |".PHP_EOL;
        echo "|   [seed]                          Generate database seeders.                        |".PHP_EOL;
        echo "|   [migrate:refresh]               Re-generate database tables.                      |".PHP_EOL;
        echo "|   [migrate:rollback]              Refresh database tables records.                  |".PHP_EOL;
        echo '|-------------------------------------------------------------------------------------|'.PHP_EOL;
    }

    private function loadEnvironment(string $baseDir): void
    {
        $dotenv = Dotenv::createImmutable($baseDir);
        $dotenv->load();
    }
}