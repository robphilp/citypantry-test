<?php

namespace RobPhilp;

use DateTimeImmutable;
use Exception;

class MealSearch
{
    private $filename;
    private $deliveryTime;
    private $locationPostcode;
    private $coverCount;

    private $vendors;

    private $currentTime;

    /**
     * Cosntructor that takes ARGV parameter array from CLI
     * Allows for a mock "now" time to be passed in, for testing
     *
     * @param $arguments
     * @param DateTimeImmutable|null $mockTime
     */
    public function __construct($arguments, DateTimeImmutable $mockTime = null)
    {
        $this->currentTime = is_null($mockTime) ? new DateTimeImmutable() : $mockTime;

        $this->getParams($arguments);
        $this->loadVendorDataFromFile($this->filename);
    }

    public function getAvailableMeals()
    {
        $currentTimeStamp  = $this->currentTime->getTimestamp();
        $deliveryTimeStamp = $this->deliveryTime->getTimestamp();

        $hours = ($deliveryTimeStamp - $currentTimeStamp) / 60 / 60;

        $possible_meals = [];

        foreach($this->vendors as $vendor) {
            foreach($vendor['meals'] as $meal) {
                $lead_time = substr($meal['lead_time'], 0, -1);
                if ($lead_time <= $hours) {
                    $possible_meals[] = $meal;
                }
            }
        }

        return $possible_meals;
    }

    /**
     * Parses file and splits into vendors and their respective meal options
     *
     * @param $filename
     */
    private function loadVendorDataFromFile($filename)
    {
        // break file into lines
        $lines = explode("\r\n", file_get_contents($filename));

        $vendor_array = [];
        $vendor_data = [];

        // Break down to each vendor by blank line
        foreach($lines as $line) {
            if (!empty($line)) {
                $vendor_data[] = $line;
            } else {
                $vendor_array[] = $vendor_data;
                $vendor_data = [];
            }
        }
        $vendor_array[] = $vendor_data;

        // process each vendor to get data and each available meal
        foreach($vendor_array as $vendor_data) {

            // first line is vendor data, remaining are meal data
            $vendor_detail = array_shift($vendor_data);
            $vendor = $this->getVendorDetail($vendor_detail);

            // Meal data
            $vendor['meals'] = [];
            foreach($vendor_data as $meal) {
                $vendor['meals'][] = $this->getMeal($meal);
            }

            $this->vendors[] = $vendor;
        }
    }

    /**
     * Get detail about a vendor from the semicolon-separated line data
     * Some of the Regex provided in the test were not accurate so I made them better - I hope.
     *
     * @param $vendor_detail
     * @return array
     * @throws Exception
     */
    private function getVendorDetail($vendor_detail)
    {
        $vendor = array_combine(['name', 'postcode', 'max_covers'], explode(';', $vendor_detail));

        if (!preg_match("/^[A-Za-z ]*$/", $vendor['name'])) {
            throw new Exception('Vendor name invalid');
        }
        if (!preg_match("/^[A-Z]{1,2}[0-9]{1,2}[0-9][A-Z]{2}$/", $vendor['postcode'])) {
            throw new Exception('Vendor postcode invalid');
        }
        if (!preg_match("/^\d*$/", $vendor['max_covers'])) {
            throw new Exception('Vendor max covers invalid');
        }

        return $vendor;
    }

    /**
     * Grab the semicolon-separated meal data
     *
     * @param $meal_data
     * @return array
     */
    private function getMeal($meal_data)
    {
        $meal = array_combine(['name', 'allergens', 'lead_time'], explode(';', $meal_data));

        return $meal;
    }

    /**
     * Takes the passed-in arguments from the CLI and validates and processes them
     *
     * Some of the Regex provided in the test were not accurate so I made them better - I hope.
     *
     * @param $arguments
     * @throws Exception
     */
    private function getParams($arguments)
    {
        if (count($arguments) !== 5) {
            throw new Exception('5 arguments are required');
        }

        list($this->filename, $delivery_day, $delivery_time, $this->locationPostcode, $this->coverCount) = $arguments;

        if (!file_exists($this->filename)) {
            throw new Exception('Vendor data file cannot be found');
        };

        preg_match("/^([0-9]{2})\/([0-9]{2})\/([0-9]{2})$/", $delivery_day, $matches);
        if (count($matches) == 0 || !checkdate($matches[2], $matches[1], $matches[3])) {
            throw new Exception('day parameter is not a valid dd/mm/yy date');
        }

        preg_match("/^[0-1]{1}[0-9]{1}|[2]{1}[0-3]{1}:[0-5]{1}[0-9]{1}$/", $delivery_time, $matches);
        if (count($matches) == 0) {
            throw new Exception('time parameter is not a valid hh:mm time');
        }

        $this->deliveryTime = DateTimeImmutable::createFromFormat('d/m/y H:i', sprintf('%s %s', $delivery_day, $delivery_time));

        preg_match("/^[A-Z]{1,2}[0-9]{1,2}[0-9][A-Z]{2}$/", $this->locationPostcode, $matches);
        if (count($matches) == 0) {
            throw new Exception('Location postcode parameter is not valid');
        }

        preg_match("/^\d+$/", $this->coverCount, $matches);
        if (count($matches) == 0) {
            throw new Exception('Cover count parameter is not valid');
        }
    }
}
