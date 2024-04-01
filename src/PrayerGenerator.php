<?php

namespace App;

use DateTime;
use DateTimeZone;
use Exception;
use JetBrains\PhpStorm\NoReturn;

class PrayerGenerator
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function showSubscribers(array $subscribers): void
    {
        if (empty($subscribers)) {
            echo "Not found!".PHP_EOL;
        }

        foreach ($subscribers as $subscriber) {
            echo sprintf('ID: %s, Name: %s', $subscriber['id'], $subscriber['name']);
            echo PHP_EOL;
        }
    }

    public function showSubscribersWithBoxes(array $subscribers): void
    {
        if (empty($subscribers)) {
            echo "Not found!".PHP_EOL;
        }

        foreach ($subscribers as $i => $subscriber) {
            echo sprintf(
                '#: %s, Subs ID: %s, Subs Name: %s, Box ID: %s, Box Name: %s, Prayer Zone: %s',
                $i+1,
                $subscriber['subscriber_id'],
                $subscriber['subscriber_name'],
                $subscriber['box_id'],
                $subscriber['box_name'],
                $subscriber['prayer_zone'],
            );
            echo PHP_EOL;
        }
    }

    public function fetchPrayerTimeByZone(string $zone): array
    {
        $url = sprintf('%s/index.php?r=esolatApi/TakwimSolat&period=week&zone=%s', $_ENV['APP_API_GOV'], $zone);
        $response = file_get_contents($url);

        if ($response) {
            $result = json_decode($response, true);
            return $result['prayerTime'];
        }

        return [];
    }

    public function updatePrayerTimes(): array
    {
        $updatePrayerTimes = [];
        $musicBoxes = $this->db->getMusicBoxes();

        foreach ($musicBoxes as $musicBox) {
            $musicBox['latest_seq'] = 0;
            $zone = $musicBox['prayer_zone'];

            $prayerTimes = $this->fetchPrayerTimeByZone($zone);
            $latestPrayerTime = $this->db->getLatestSong($zone);
            if ($latestPrayerTime) {
                $musicBox['latest_seq'] = $latestPrayerTime['prayer_time_seq'];
            }

            $updated = $this->db->storeSongs($musicBox, $prayerTimes);
            if ($updated) {
                $updatePrayerTimes[] = ['zone' => $zone, 'total' => count($prayerTimes)];
            }
        }

        return $updatePrayerTimes;
    }

    public function sentNotificationError(string $message, string $zone, int $boxId): void
    {
        $to = "trisuliprasetyo@gmail.com";
        $subject = "Error in Prayer Time Voiceover";
        $message = "Box ID: $boxId\nPrayer Time Zone: $zone\nError Message: $message";
        $headers = "From: trisulipras@gmail.com";

        // Set SMTP server settings
        ini_set("SMTP", "smtp.gmail.com");
        ini_set("smtp_port", "587");
        ini_set("sendmail_from", "trisulipras@gmail.com");

        ini_set("auth_username", "trisulipras@gmail.com");
        ini_set("auth_password", "Nov94Eleven");


        mail($to, $subject, $message, $headers);
    }

    /**
     * @throws Exception
     */
    public function notify(array $songs): void
    {
        $timers = 0;
        $notified = [];
        while (true) {
            $now = new DateTime('now', new DateTimeZone($_ENV['APP_TIMEZONE']));
            $time = $now->format('H:i:s');
            echo "\rCurrent Time: $time";
            $time = null;
            $timers += 1;
            if ($timers >= (5*60)) {
                if (! empty($songs)) {
                    foreach ($songs as $song) {
                        $dateTime = $song['prayer_time_date'].' '.$song['prayer_time'];
                        $prayerTime = new DateTime($dateTime, new DateTimeZone($_ENV['APP_TIMEZONE']));
                        if (! in_array($song['id'], $notified)) {
                            if (intval($now->format('U')) >= intval($prayerTime->format('U'))) {
                                echo sprintf('Time to pray %s for zone %s.', $song['title'], $song['prayer_zone']).PHP_EOL;
                                $this->db->markPrayerTimeNotified(intval($song['id']));
                                $notified[] = $song['id'];
                            }
                        }
                    }
                } else {
                    $song = $this->db->getNextPrayerTime($now->format('H:i:s'));
                    if ($song) {
                        echo sprintf('Time to pray %s for zone %s.', $song['title'], $song['prayer_zone']).PHP_EOL;
                        $this->db->markPrayerTimeNotified(intval($song['id']));
                        $notified[] = $song['id'];
                    }
                }

                $timers = 0;
            }

            sleep(1);
        }
    }
}