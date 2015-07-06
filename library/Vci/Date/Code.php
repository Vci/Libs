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
use Exception;

/**
 * Defines an object describing a VCI style date code. Oh yes.
 */
class Code implements CodeInterface
{
    //= variables =========================================================
    protected $dateTimeEarliest;
    protected $dateTimeLatest;
    protected static $defaultCodeInterpreter = '\Vci\Date\CodeInterpreter\Vci';
    protected static $codeInterpreter;

    protected static $failMode      = 1;
    const FAIL_EXCEPTION            = 0;
    const FAIL_WARN                 = 1;
    const FAIL_SILENT               = 3;
    public static $FAIL_MODES       = array(
                                           self::FAIL_EXCEPTION,
                                           self::FAIL_WARN,
                                           self::FAIL_SILENT,
                                      );
    

    //= methods ===========================================================
    /**
     * 
     */
    function __construct(DateTime $earliest, DateTime $latest = NULL)
    {
        $this->dateTimeEarliest = $earliest;
        $this->dateTimeLatest = $latest;
    }

    /**
     * makeFromDate
     * @param DateTime $earliest 
     * @param DateTime $latest 
     * @return {DateCode}
     * @author David Hazel <dhazel@gmail.com>
     **/
    public static function makeFromDate(DateTime $earliest, DateTime $latest = NULL) 
    {
        if ( $latest === NULL ) {
            $latest = new DateTime();
        }

        return new self($earliest, $latest);
    }

    /**
     * make
     * @param string $dateCodeString 
     *  A date-code in VCI standard string representation
     * @return {DateCode|false}
     * @author David Hazel <dhazel@gmail.com>
     **/
    public static function make($dateCodeString) 
    {
        list($earliest, $latest) = self::calculateDateRange($dateCodeString);
        if ( empty($earliest) ) {
            return false;
        } else {
            return self::makeFromDate($earliest, $latest);
        }
    }

    /**
     * calculateDateRange
     * @param string $dateCodeString 
     *  String in standard Vci date-code format.
     * @return {array} Two-element array containing DateTime objects
     *  representing the earliest date and the latest date (respectively) that
     *  the datecode string could represent.
     * @author David Hazel <dhazel@gmail.com>
     **/
    protected static function calculateDateRange($dateCodeString)
    {
        // get the code interpreter
        $interpreter = self::getCodeInterpreter();

        // validate the datecode
        if ( ! $interpreter->validates($dateCodeString) ) {
            self::fail(sprintf(
                'Interpreter %s failed, saying: %s',
                get_class($interpreter),
                $interpreter->getValidationMessage()
            ));
            return false;
        } 

        // calculate the date range
        return $interpreter->calculateDateRange($dateCodeString);
    }

    /**
     * @param string $dateCode
     * @return {bool} Indicating whether or not the given datecode is valid
     **/
    public static function validates($dateCode) 
    {
        // get the code interpreter
        $interpreter = self::getCodeInterpreter();

        // validate & return
        return $interpreter->validates($dateCode);
    }

    /**
     * registerCodeInterpreter
     * @param CodeInterpreterInterface $interpreter 
     * @return {Code}
     **/
    public static function registerCodeInterpreter(CodeInterpreterInterface $interpreter)
    {
        self::$codeInterpreter = $interpreter;
    }

    /**
     * getCodeInterpreter
     * @return {CodeInterpreterInterface}
     * @author David Hazel <dhazel@gmail.com>
     **/
    public static function getCodeInterpreter()
    {
        // make sure we have a datecode interpreter loaded
        if ( empty(self::$codeInterpreter) ) {
            self::registerCodeInterpreter(new self::$defaultCodeInterpreter);
        }
        return self::$codeInterpreter;
    }

    /**
     * @return {\DateTime} The earliest date covered by the datecode
     */
    public function getEarliestDate()
    {
        return $this->dateTimeEarliest;
    }

    /**
     * @return {\DateTime} The latest date covered by the datecode
     */
    public function getLatestDate()
    {
        return $this->dateTimeLatest;
    }

    /**
     * @return {array} Associative array of both the earliest and latest dates
     *  represented by the datecode, with explanatory keys.
     */
    public function getBaseDates()
    {
        return array(
            'earliest'  => $this->getEarliestDate(),
            'latest'    => $this->getLatestDate(),
        );
    }

    /**
     * Return true/false whether or not the date code represents multiple years.
     *
     * @return true/false
     */
    public function hasMultipleYears()
    {
        $hasMultipleYears = false;
        if ( $this->getEarliestDate()->format('Y') 
         !== $this->getLatestDate()->format('Y') 
        ) {
            $hasMultipleYears = true;
        }
        return $hasMultipleYears;
    }

    /**
     * Return true/false whether or not the date code represents multiple weeks.
     *
     * @return true/false
     */
    public function hasMultipleWeeks()
    {
        $hasMultipleWeeks = false;
        if ( $this->hasMultipleYears() ) {
            $hasMultipleWeeks = true;
        } else if ( $this->getEarliestDate()->format('W')
                !== $this->getLatestDate()->format('W')
        ){
            $hasMultipleWeeks = true;
        }
        return $hasMultipleWeeks;
    }

    /**
     * @return {string} The date code in a human readable format.
     */
    public function getHumanReadable()
    {
        // set the basic date with the base timestamp
        $returnString = $this->getEarliestDate()->format('F jS Y');

        // add meta info for weeks and years
        if ( $this->hasMultipleYears() ) {
            $returnString = $returnString . ', plus additional years';
        } elseif ( $this->hasMultipleWeeks() ) {
            $returnString = $returnString . ', plus additional weeks';
        }

        // return the human readable string
        return 'Week of ' . $returnString;
    }

    /**
     * @return {string} The date code in a the standard string representation.
     */
    public function getString()
    {
        // set the year code with the base timestamp
        $returnString = $this->getEarliestDate()->format('y');

        // add meta info for weeks and years
        if ( $this->hasMultipleYears() ) {
            $returnString .= '+';
        } elseif ( ! $this->hasMultipleWeeks() ) {
            $returnString .= $this->getLatestDate()->format('W');
        }

        // return the human readable string
        return $returnString;
    }

    /**
     * Handles failures in a dynamic way
     *
     * @param string $message
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected static function fail($message)
    {
        switch ( self::$failMode ) {
            case self::FAIL_EXCEPTION:
                throw new Exception($message);
                break;
            case self::FAIL_WARN:
                trigger_error($message, E_USER_WARNING);
                break;
            case self::FAIL_SILENT:
                break;
            default:
        }
    }

    /**
     * @param int $mode
     *  The mode indicates what sort of behavior an instantiation will follow
     *  when encountering errors during datecode conversion.
     * @return [int] The previous fail mode
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public static function setFailMode($mode)
    {
        // typecheck
        if ( ! in_array($mode, self::$FAIL_MODES) ) {
            throw new Exception(sprintf(
                'Requested mode, "%s", is not a valid fail mode.',
                $mode
            ));
        }

        // store the previous mode
        $origMode = self::$failMode;

        // set the mode
        self::$failMode = $mode;

        // return the previous mode
        return $origMode;
    }

    /**
     *  __toString
     * @return {string}
     * @author David Hazel <dhazel@gmail.com>
     **/
    public function __toString()
    {
        return $this->getString();
    }
}

