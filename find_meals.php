<?php

require_once(__DIR__ . '/vendor/autoload.php');

use RobPhilp\MealSearch;

try {
    // Get CLI args and remove first item - the script name
    $args = $argv;
    array_shift($args);

    // instantiate class and do the legwork
    $mealSearch = new MealSearch($args);
    $meals = $mealSearch->getAvailableMeals();

    // print results
    foreach($meals as $meal) {
        print(implode(';', $meal) . "\n");
    }
} catch (Exception $e) {
    print('Exception: ' . $e->getMessage());
}