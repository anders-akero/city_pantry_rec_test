<?php

require __DIR__ . '/../vendor/autoload.php';

$inputs = $argv;
unset($inputs[0]);

$data = new Input(array_values($inputs));

// The following line can be used for debug purpose.
// It changes the current time for the system.
$data->setCurrentTime(New DateTime('2015-10-20 00:00'));//TODO: Remove this line to run in "real" time
echo $data->getSuggestions();

/**
 * Assumptions:
 * 1. Advance time is always given in hours
 * 2. That the output should not contain the advance time needed
 */

/**
 * To run the tests:
 * `./vendor/bin/phpunit --bootstrap vendor/autoload.php tests --testdox`
 * The test assume that we have a file called "vendors.txt" in the data-foler.
 * The test also assume that this file contains the following:
 * "Grain and Leaf;E32NY;100
    Grain salad;nuts;12h

    Wholegrains;SW34DA;20
    The Classic;gluten;24h

    Ghana Kitchen;NW42QA;40
    Premium meat selection;;36h
    Breakfast;gluten,eggs;12h

    Well Kneaded;EC32BA;150
    Full English breakfast;gluten;24h
"
 */
