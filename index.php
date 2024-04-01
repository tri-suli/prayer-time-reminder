<?php

$app = require_once __DIR__.'/bootstrap/app.php';

// Main function to execute application logic
//function main($argv): void {
//    global $db;
//
//    $generator = new PrayerGenerator($db);
//    $options = getopt('', ['migrate', 'migrate:rollback', 'migrate:refresh', 'seed']);
//
//    if (! empty($options)) {
//        if (array_key_exists('migrate', $options)) {
//            // run db migrations && seeders
//            echo "Applying migrations!".PHP_EOL;
//            $db->migrate();
//            echo "Applied migrations!".PHP_EOL;
//        }
//
//        if (array_key_exists('migrate:rollback', $options)) {
//            // Rollback the table
//            $db->migrate('rollback');
//        }
//
//        if (array_key_exists('migrate:refresh', $options)) {
//            // Re-create the db tables
//            $db->migrate('refresh');
//        }
//
//        if (array_key_exists('seed', $options)) {
//            // run db migrations && seeders
//            $db->seed();
//            echo "Seeders generated! Please check your database".PHP_EOL;
//        }
//
//        echo "Please re-run the index.php without (--migrate) and (--seed).".PHP_EOL;
//        return;
//    }
//
//    $generator->notify();
//}

// Run the main function
//main($argv);

$app->run();
