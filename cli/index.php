<?php

require __DIR__ . '/../vendor/autoload.php';

$inputs = $argv;
unset($inputs[0]);

$data = new Input(array_values($inputs));

echo $data->getSuggestions();
