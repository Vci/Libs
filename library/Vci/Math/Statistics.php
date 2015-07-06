<?php
/**
 * @file
 * Part of the voyager brain project.
 *
 * @author "David Hazel" <dhazel@gmail.com>
 *
 * © Copyright 2012 Voyager Components Inc. All Rights Reserved.
 */

//require_once('my/path/to/file.php');


/**
 * Utility class for calculating general statistics
 **/
class Vci_Math_Statistics
{
    //====== construction ============================================
    // (none)

    //====== variables ===============================================
    // (none)

    //====== methods =================================================
    /**
     * @param Array $array
     * @return [array] An associative array containing all the individual stats
     *  for the given array.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public static function allStats(Array $array){
        $returnArray = array();
        $returnArray['sum'] = self::sum($array);
        $returnArray['min'] = self::min($array);
        $returnArray['max'] = self::max($array);
        $returnArray['range'] = self::range($array);
        $returnArray['mean'] = self::mean($array);
        $returnArray['median'] = self::median($array);
        $returnArray['mode'] = self::mode($array);
        return $returnArray;
    }
    /**
     * @param Array $array
     * @return [num|null] The mean (average) value of the array, else NULL if
     *  the array is empty.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public static function mean(Array $array){
        if ( empty($array) ) return NULL;
        $count = count($array); 
        $sum = array_sum($array); 
        return $sum / $count;
    }

    /**
     * @param Array $array
     * @return [mixed] The median (middle) value of the array, else NULL if the
     *  array is empty.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public static function median(Array $array){
        if ( empty($array) ) return NULL;
        rsort($array); 
        $middle = round(count($array) / 2); 
        return $array[$middle-1];
    }

    /**
     * @param Array $array
     * @return [mixed] The mode (most repeated) value of the array, else NULL
     *  if the array is empty.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public static function mode(Array $array){
        if ( empty($array) ) return NULL;
        $countMap = array_count_values($array); 
        arsort($countMap); 
        foreach ( $countMap as $key => $value ) {
            return $key; 
        }
    }

    /**
     * @param Array $array
     * @return [num] The range (difference between min and max) of the array,
     *  note that int(0) is returned if the array is empty.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public static function range(Array $array){
        $min = self::min($array);
        $max = self::max($array);
        return ( $max - $min );
    }

    /**
     * @param Array $array
     * @return [mixed] The min (minimum value) element in the array, else NULL
     *  if the array is empty.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public static function min(Array $array){
        if ( empty($array) ) return NULL;
        sort($array); 
        $min = array_shift($array);
        while ( $min === null && count($array) > 0 ) {
            $min = array_shift($array);
        }
        return $min;
    }

    /**
     * @param Array $array
     * @return [mixed] The max (maximum value) element in the array, else NULL
     *  if the array is empty.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public static function max(Array $array){
        if ( empty($array) ) return NULL;
        rsort($array); 
        return $array[0]; 
    }

    /**
     * @param Array $array
     * @return [int|float] The sum of the numerical values of all array elements
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public static function sum(Array $array){
        return array_sum($array);
    }
}
?>
