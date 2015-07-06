<?php

namespace Vci\Date;

/**
 * CodeInterpreterInterface
 * @author David Hazel
 **/
interface CodeInterpreterInterface
{
    /**
     * calculateDateRange
     * @param string $dateCodeString 
     * @return {array|false} Two-element array containing DateTime objects
     *  representing the earliest date and the latest date (respectively) that
     *  the datecode string could represent.
     * @author David Hazel <dhazel@gmail.com>
     **/
    public function calculateDateRange($dateCodeString);

    /**
     * @param string $dateCode
     * @return {bool} Indicating whether or not the given datecode is valid
     **/
    public function validates($dateCodeString);

    /**
     * getValidationMessage
     * @return {string} 
     **/
    public function getValidationMessage();

    /**
     * This is used for validation and year-inference purposes
     * @param string $code
     * @return {this}
     **/
    public static function setEarliestValidYearCode($code);

}
