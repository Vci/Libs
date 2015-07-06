<?php

namespace Vci\Date\CodeInterpreter;

use Vci\Date\CodeInterpreterInterface;
use DateTime;

/**
 * Class Vci 
 */
class Vci implements CodeInterpreterInterface
{
    /// Used for validation and detecting millenium
    protected $earliestValidYearCode    = '60';

    /// Populated by the validates() method
    protected $validationMessages       = array();

    /**
     * calculateDateRange
     * @param string $dateCodeString 
     * @return {array|false} Two-element array containing DateTime objects
     *  representing the earliest date and the latest date (respectively) that
     *  the datecode string could represent.
     * @author David Hazel <dhazel@gmail.com>
     **/
    public function calculateDateRange($dateCodeString)
    {
        // trim whitespace
        $dateCodeString = trim($dateCodeString);

        // validate
        if ( ! $this->validates($dateCodeString) ) {
            return false;
        }

        // split out the year and week codes
        $codes      = str_split($dateCodeString, 2);
        $yearCode   = $codes[0];
        $weekCode   = empty($codes[1])? null : $codes[1];
        $nowYearCode= date('y');
        $nowWeekCode= date('W');
        $nowYear    = date('Y');

        // determine the "earliest" year
        //    (done manually so we have hard adn fast control over
        //     where to break between the 20th and 21st centuries.)
        if( $yearCode >= $this->earliestValidYearCode )
        {
            $earliestYear = 1900 + $yearCode;
        }
        elseif( ($yearCode >= 0 && $yearCode <= $nowYearCode) )
        {
            $earliestYear = 2000 + $yearCode;
        }

        // Get min/max values
        switch ($weekCode) {
            // year code only
            case NULL:
                $minYear = $earliestYear;
                $maxYear = $earliestYear;
                $minWeek = 1;
                $maxWeek = ($earliestYear == $nowYear)?  $nowWeekCode : 52;
                break;
            // if we have the more relaxed YY+ format
            case '+':
                $minYear = $earliestYear;
                $maxYear = $nowYear;
                $minWeek = 1;
                $maxWeek = $nowWeekCode;
                break;
            // if we have a specific DC (YYWW)
            default:
                $minYear = $earliestYear;
                $maxYear = $earliestYear;
                $minWeek = $weekCode;
                $maxWeek = $weekCode;
        }
        
        $min = ($minWeek == 1)? 
                    new DateTime($earliestYear . '-1-1'):
                    new DateTime($minYear . 'W' . $minWeek . '1');
        $max = ($maxWeek == 52)? 
                    new DateTime($earliestYear . '-12-31'):
                    new DateTime($maxYear . 'W' . $maxWeek . '7');

        return array( $min, $max );
    }

    /**
     * @param string $dateCode
     * @return {bool} Indicating whether or not the given datecode is valid
     **/
    public function validates($dateCodeString)
    {
        $this->validationMessages = array();
        $isValid = false;
        if ( is_string($dateCodeString)
          && preg_match('/^(?:(\d{2})(\+?|\d{2}))$/', $dateCodeString, $matches) 
        ) {
            // check for valid year and week codes
            if ( $this->isValidYearCode($matches[1]) ) {
                $isValid = $this->isValidWeekCode($matches[2], $matches[1]);
            }
        } else {
            $this->addValidationMessage(sprintf(
                'DateCode, %s, is in an invalid format',
                $dateCodeString
            ));
        }

        if ( ! $isValid ) {
            $this->addValidationMessage(sprintf(
                'DateCode, %s, is invalid',
                $dateCodeString
            ));
        }
        return $isValid;
    }
        
    /**
     * @param string $yearCode
     * @author "Mehile Orloff" <mo@voyegercomponents.com>
     * @return [bool] 
     **/
    public function isValidYearCode($yearCode)
    {
        $isValid = false;
        
        if( preg_match('/^[0-9]{2}$/', $yearCode) )
        {
            $now = (int) date('y');
            
            if ( $this->earliestValidYearCode === NULL ) 
            {
                $isValid = true; // any year is valid if this field is empty
            }
            elseif( $yearCode >= $this->earliestValidYearCode )
            {
                $isValid = true; 
            }
            elseif( $yearCode >= 0 && $yearCode <= $now )
            {
                $isValid = true; 
            }
        }

        if ( ! $isValid ) {
            $this->addValidationMessage(sprintf(
                'Year-code string, %s, contains invalid year-code',
                $yearCode
            ));
        }

        return $isValid;
    }

    /**
     * @param string $yearCode
     * @param string $weekCode
     * @author "Mehile Orloff" <mo@voyegercomponents.com>
     * @return [bool] 
     **/
    public function isValidWeekCode($weekCode, $yearCode)
    {
        $isValid = false;
        
        if ( $weekCode === '' || $weekCode === '+' ) {
            $isValid = true;
        }
        elseif ( ($weekCode <= 52) 
              && ($weekCode > 0)
              && $this->isValidYearCode($yearCode) 
        ) {
            $now = array('year' => date('y'), 'week' => date('W'));

            if( $yearCode == $now['year'] )
            {
                if( $weekCode <= $now['week'] )
                    $isValid = true;
            }
            else
            {
                $isValid = true;
            }
        }

        if ( ! $isValid ) {
            $this->addValidationMessage(sprintf(
                'Week-code string, %s, contains invalid week-code',
                $weekCode
            ));
        }
            
        return $isValid;
    }

    /**
     * addValidationMessage
     * @return {this}
     * @author David Hazel <dhazel@gmail.com>
     **/
    protected function addValidationMessage($message)
    {
        $this->validationMessages[] = $message;
        return $this;
    }

    /**
     * getValidationMessage
     * @return {string} 
     **/
    public function getValidationMessage() 
    {
        $message = '';
        foreach ( $this->validationMessages as $key => $string ) {
            $message .= sprintf('(%s) %s; ', $key, $string);
        }
        return $message;
    }

    /**
     * This is used for validation and year-inference purposes
     * @param string $code
     * @return {this}
     **/
    public static function setEarliestValidYearCode($code) 
    {
        if ( preg_match('/^\d{2}$/', $code) ) {
            $this->earliestValidYearCode = $code;
        } else {
            throw new \Exception(sprintf(
                'Invalid year code: %s',
                $code
            ));
        }
        return $this;
    }
}

