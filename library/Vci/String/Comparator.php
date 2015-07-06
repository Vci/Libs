<?php
/**
 * @file
 * Part of the vci libs.
 *
 * @author David Hazel
 *
 * © Copyright 2011 Voyager Components Inc. All Rights Reserved.
 */


//require_once('my/path/to/file.php');


/**
 * Utility class for advanced string-compare methods.
 *
 * Note that numbers are not converted to phonetic equivalents in comparisons,
 *  this must be done externally.
 **/
class Vci_String_Comparator
{
    //====== construction ============================================
    // (none)

    //====== variables ===============================================
    protected $biDirectional; ///< see setBidirectional()

    //====== methods =================================================
    /**
     * Compares string1 with string2 using php's string phonetics functions.
     *  This function takes into account how the strings "sound" when they are
     *  pronounced.
     *
     * Currently (2011-07-29) this function normalizes to the english
     *  pronunciation patterns of words; other patterns, like spanish, are
     *  not supported.
     *
     * @param string $string1
     *  The base string we are comparing against.
     * @param string $string2
     *  The test string that we wish to verify.
     * @return The percentage of phonetic correlation between the two strings.
     *  (ie: complete phonetic match = 100, complete phonetic mis-match = 0)
     * @author David Hazel
     **/
    public function phoneticSimilarity($string1, $string2){
        // generate the metaphone code for each string
        $metaphone1 = metaphone($string1);
        $metaphone2 = metaphone($string2);

        // calculate and return the percentage match between metaphone codes
        return $this->similarity($metaphone1, $metaphone2);
    }

    /**
     * Compares string1 with string2 using php's string similarity functions.
     *
     * This function is similar to phoneticSimilarity() except it does not 
     *  take into account the phonetic "sound" of the strings when pronounced.
     *  It simply compares the strings for similarities, character-to-character.
     *
     * @param string $string1
     *  The base string we are comparing against.
     * @param string $string2
     *  The test string that we wish to verify.
     * @return The percentage of correlation between the two strings (character-
     *  to-character).  (ie: complete match = 100, complete mis-match = 0)
     * @author David Hazel
     **/
    public function similarity($string1, $string2){
        // calculate the levenshtein distance between the strings
        $distance = levenshtein($string1, $string2);

        // determine our base string (longest takes the cake)
        //  (this makes the comparison operation bi-directional)
        if( strlen($string2) > strlen($string1) ){
            $baseString = $string2;
        }else{
            $baseString = $string1;
        }

        // special case of empty base string
        if ( empty($baseString) ){
            return 0;
        }

        // calculate the percentage match
        $matchPercentage = 100 - (100 * $distance / strlen($baseString));

        // return the percentage match
        return $matchPercentage;
    }

    /**
     * Compares string1 with string2 using php's string similarity functions.
     *
     * This function is similar to similarity() except that it takes into
     *  account the difference in lengths between the two strings and attempts
     *  to "normalize" them and compensate for the skew introduced by this
     *  difference.
     *
     * The effect is that if one string is contained within the other as a 
     *  fully contiguous sub-string, then the normalizedSimilarity() return
     *  value is 100 (100%); however, this is only a measure of 
     *  "substring-ishness" -- non-substrings can return values of 100 as well.
     *  It's best to use regular expressions if one wishes to detect a
     *  bona-fide substring.
     *
     * @param string $string1
     *  The base string we are comparing against.
     * @param string $string2
     *  The test string that we wish to verify.
     * @return The percentage of correlation between the two strings (character-
     *  to-character).  (ie: complete match = 100, complete mis-match = 0)
     * @author David Hazel
     **/
    public function normalizedSimilarity($string1, $string2){
        // calculate the levenshtein distance between the strings
        $distance = levenshtein($string1, $string2);

        // determine which string is longest
        if( strlen($string2) > strlen($string1) ){
            $longString = $string2;
            $shortString = $string1;
        }else{
            $longString = $string1;
            $shortString = $string2;
        }

        // subtract the difference in string lengths from the lev. distance
        $distance = $distance - (strlen($longString) - strlen($shortString));

        // calculate the percentage match
        $matchPercentage = 100 - (100 * $distance / strlen($shortString));

        // return the percentage match
        return $matchPercentage;
    }

    /**
     * Compares string1 with string2 using php's string phonetics functions.
     *  This function takes into account how the strings "sound" when they are
     *  pronounced.
     *
     * Currently (2011-07-29) this function normalizes to the english
     *  pronunciation patterns of words; other patterns, like spanish, are
     *  not supported.
     *
     * This function is similar to phoneticSimilarity() except that it takes 
     *  into account the difference in lengths between the two phrases and 
     *  attempts to "normalize" them and compensate for the skew introduced by 
     *  this difference.
     *
     * The effect is that if one phrase is contained within the other as a 
     *  fully contiguous sub-phrase, then the normalizedPhoneticSimilarity() 
     *  return value is 100 (100%); however, this is only a measure of 
     *  "subphrase-ishness" -- non-subphrases can return values of 100 as well.
     *  It's best to use regular expressions on the raw metaphones if one 
     *  wishes to detect a bona-fide subphrase.
     *
     * @param string $string1
     *  The base string we are comparing against.
     * @param string $string2
     *  The test string that we wish to verify.
     * @return The percentage of phonetic correlation between the two strings.
     *  (ie: complete phonetic match = 100, complete phonetic mis-match = 0)
     * @author David Hazel
     **/
    public function normalizedPhoneticSimilarity($string1, $string2){
        // generate the metaphone code for each string
        $metaphone1 = metaphone($string1);
        $metaphone2 = metaphone($string2);

        // calculate and return the normalized similarity between the codes
        return $this->normalizedSimilarity($metaphone1, $metaphone2);
    }

    /**
     * Compares string1 with string2 using php's string similarity functions.
     *
     * This function is similar to similarity() except for several key aspects:
     *
     * 1) This function compares strings starting at the beginning and 
     *  iterates through the string characters.
     * 2) This function defaults to unidirectional mode, meaning the order 
     *  of its arguments matters. A short string compared against a long 
     *  string can yield a 100% match, and will disregard the tail of the 
     *  longer string.
     * 3) This function weights the strings, placing higher value on 
     *  similarities from certain portions of the strings than on others.
     *
     * The initial concept of this function was to use direct character
     *  comparison between the strings; however, that approach quickly loses 
     *  accuracy in the presence of missing or added characters, so this 
     *  implementation uses a rolling levenshtein distance while iterating over
     *  both input strings, adding a character to each compared substring with
     *  each iteration.
     *
     * @param string $string1
     *  The base string we are comparing against.
     * @param string $string2
     *  The test string that we wish to verify.
     * @param int $gradient
     *  This parameter configures the weighting formula. A higher number means
     *  a greater weight difference between beginning and end of the string.
     *  See weightFormula() for more info. In practice, this parameter does
     *  not appear to really do much, so the default is generally a good choice.
     *  The incremental levenshtein used by this function already provides a 
     *  kind of gradiential weighting simply by virtue of how it operates.
     *  (min: 0, max: 100)
     * @return The percentage of correlation between the two strings.
     *  (ie: complete match = 100, complete mis-match = 0)
     * @author David Hazel
     **/
    public function weightedSimilarity($string1, $string2, $gradient = 0){
        // check for bidirection
        if( !empty($this->biDirectional) ){
            // set string1 to be the longest string of the two
            if( strlen($string1) < strlen($string2) ){
                $tmp = $string1;
                $string1 = $string2;
                $string2 = $tmp;
                unset($tmp);
            }
        }

        // save our base string
        $baseString = $string1;

        // loop through $string1
        $string1 = str_split($string1);
        $string2 = str_split($string2);
        $substring1 = '';
        $substring2 = '';
        $totalSimilarity = 0;
        foreach( $string1 as $key => $val ){
            // generate our current substring
            $substring1 .= $string1[$key];
            if( !empty($string2[$key]) ){
                $substring2 .= $string2[$key];
            }

            // generate our current weight value
            $weight = $this->weightFormula($baseString, $key, $gradient);

            // normalize our weight value to 1
            $weight = $weight / 100;

            // calculate our generic similarity between the two substrings
            $similarity = $this->similarity($substring1, $substring2);

            // weight the current similarity and add it to the total
            $totalSimilarity += $weight * $similarity;
        }

        // return the final weighted similarity
        return $totalSimilarity;
    }

    /**
     * This is the default weight formula used by the weightedSimilarity()
     *  function.
     *
     * @param string $string
     *  The string that we are basing weights off of.
     * @param int $position
     *  The position on the string for which we wish to calculate a weight.
     * @param int $changeFactor
     *  The function uses a basic first-order equation to calculate weight.
     *  This parameter allows us to set the slope on that formula and tailor
     *  our weight configuration.  The formula itself generates a basic linear 
     *  weighting along the length of $string.  The larger this number, the 
     *  greater the difference in weights between the beginning and ending of 
     *  the string. Note that this formula always weights the beginning of the
     *  string higher than the end. This number is a percentage that the 
     *  formula uses to calculate slope, so minimum = 0, maximum = 100.
     * @return An integer representing the weight value at the given string
     *  position. If all the weights from along the entire length of the string
     *  are added together, the sum will equal 100. Note that if the "string"
     *  parameter is empty, then a weight of zero will be returned.
     * @author David Hazel
     **/
    protected function weightFormula($string, $position, $changeFactor){
        // type-check
        if( !is_int($changeFactor) ){
            throw new Exception('Change factor must be an integer');
        }elseif( ($changeFactor < 0) || ($changeFactor > 100) ){
            throw new Exception('Change factor is a percentage value and must be between 0 and 100. Value submitted: '.$changeFactor);
        }elseif( empty($string) ){
            return 0;
        }

        // get our string length
        $length = strlen($string);

        // reverse our position so that it matches our slope and formula
        $position = ($length - 1) - $position;

        // adjust our position for 1-indexing to match our formula
        $position = $position + 1;

        // type check
        if( ($position > $length) || ($position < 1) ){
            throw new Exception('Position is out of range');
        }

        // calculate our maximum slope
        $maxSlope = 200 / (($length * $length) + $length);

        // determine slope based on $changeFactor (percentage)
        $slope = ($changeFactor / 100) * $maxSlope;

        // calculate our initial value
        $initialValue = ((100 - (0.5 * $slope * (($length * $length) + $length))) / $length);

        // calculate our weight
        $weight = ($slope * $position) + $initialValue;

        // return the weight value
        return $weight;
    }

    /**
     * Compares string1 with string2 using php's string phonetics functions.
     *  This function takes into account how the strings "sound" when they are
     *  pronounced, and it considers phrases with similar initial phonetics
     *  to be of greater similarity than those without similar initial phonetics
     *
     * Currently (2011-07-29) this function normalizes to the english
     *  pronunciation patterns of words; other patterns, like spanish, are
     *  not supported.
     *
     * This function is similar to phoneticSimilarity() except that it uses
     *  the weighted similarity functionality from weightedSimilarity() to
     *  correlate phrases with similar beginnings.
     *
     * Note that this function, like weightedSimilarity() defaults to  
     *  unidirectional mode, meaning that the order of the string arguments 
     *  matters. Keep in mind that a short string compared against a long 
     *  string can yield a 100% match, and will disregard the tail of the 
     *  longer string.
     *
     * @param string $string1
     *  The base string we are comparing against.
     * @param string $string2
     *  The test string that we wish to verify.
     * @param int $gradient
     *  See weightedSimilarity()
     * @return The percentage of phonetic correlation between the two strings.
     *  (ie: complete phonetic match = 100, complete phonetic mis-match = 0)
     * @author David Hazel
     **/
    public function weightedPhoneticSimilarity($string1, $string2, $gradient = 0){
        // check for bidirection
        if( !empty($this->biDirectional) ){
            // set string1 to be the longest string of the two
            if( strlen($string1) < strlen($string2) ){
                $tmp = $string1;
                $string1 = $string2;
                $string2 = $tmp;
                unset($tmp);
            }
        }

        // generate the metaphone code for each string
        $metaphone1 = metaphone($string1);
        $metaphone2 = metaphone($string2);

        // calculate and return the normalized similarity between the codes
        return $this->weightedSimilarity($metaphone1, $metaphone2, $gradient);
    }

    /**
     * Sets/unsets bidirectional mode for functions (like weightedSimilarity())
     *  that have two modes available.
     *
     * @return void
     * @author David Hazel
     **/
    public function setBidirectional($mode){
        $this->biDirectional = !empty($mode);
    }
}
?>
