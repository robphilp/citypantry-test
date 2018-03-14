<?php

use RobPhilp\MealSearch;

use PHPUnit\Framework\TestCase;

class MealSearchTest extends TestCase
{
    /**
     * @expectedException Exception
     * @expectedExceptionMessage 5 arguments are required
     */
    public function testIncorrectParameterCountIsHandled()
    {
        $args = ['test_data_1.txt'];
        new MealSearch($args);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage file cannot be found
     */
    public function testMissingFileIsHandled()
    {
        $args = ['not_a_file.txt', '23/03/80', '12:30', 'DA16 3RH', '50'];
        new MealSearch($args);
    }

    public function testGetAvailableMeals()
    {
        $mockCurrentDate = DateTimeImmutable::createFromFormat('d/m/y H:i', '20/10/15 00:00');
        $args = [__DIR__ . '/test_data_1.txt', '21/10/15', '11:00', 'NW43QB', '20'];

        $mealSearch = new MealSearch($args, $mockCurrentDate);

        $this->assertEquals(
            [
                [
                    'name' => 'Grain salad',
                    'allergens' => 'nuts',
                    'lead_time' => '12h'
                ],
                [
                    'name' => 'The Classic',
                    'allergens' => 'gluten',
                    'lead_time' => '24h'
                ],
                [
                    'name' => 'Breakfast',
                    'allergens' => 'gluten,eggs',
                    'lead_time' => '12h'
                ],
                [
                    'name' => 'Full English breakfast',
                    'allergens' => 'gluten',
                    'lead_time' => '24h',
                ]
            ], $mealSearch->getAvailableMeals()
        );

    }

}