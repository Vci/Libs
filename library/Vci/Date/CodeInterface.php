<?php 
/**
 * @file
 * Class file for the Date Code class.
 *
 * @author David Hazel
 * 
 * Copyright 2011 Voyager Components
 */

namespace Vci\Date;

use DateTime;

/**
 * This is the date code interface.
 *
 * OO Programming best practices principle: always code to an interface
 */
interface CodeInterface {
    /**
     * makeFromDate
     * @param DateTime $earliest 
     * @param DateTime $latest 
     * @return {DateCode}
     * @author David Hazel <dhazel@gmail.com>
     **/
    public static function makeFromDate(DateTime $earliest, DateTime $latest = NULL);

    /**
     * make
     * @param string $string 
     *  A date-code in VCI standard string representation
     * @return {DateCode}
     * @author David Hazel <dhazel@gmail.com>
     **/
    public static function make($string);

    /**
     * @param string $dateCode
     * @return {bool} Indicating whether or not the given datecode is valid
     **/
    public static function validates($dateCode);

    /**
     * @return {\DateTime} The earliest date covered by the datecode
     */
    public function getEarliestDate();

    /**
     * @return {\DateTime} The latest date covered by the datecode
     */
    public function getLatestDate();

    /**
     * @return {array} Associative array of both the earliest and latest dates
     *  represented by the datecode, with explanatory keys.
     */
    public function getBaseDates();

    /**
     * @return {bool} true/false whether or not the date code represents multiple years.
     */
    public function hasMultipleYears();

    /**
     * @return {bool} true/false whether or not the date code represents multiple weeks.
     */
    public function hasMultipleWeeks();

    /**
     * @return {string} The date code in a human-readable format.
     */
    public function getHumanReadable();

    /**
     * @return {string} The date code in the standard string representation
     **/
    public function getString();

    /**
     * registerCodeInterpreter
     * @param CodeInterpreterInterface $interpreter 
     * @return {Code}
     **/
    public static function registerCodeInterpreter(CodeInterpreterInterface $interpreter);

    /**
     *  __toString
     * @return {string}
     **/
    public function __toString();
}


?>
