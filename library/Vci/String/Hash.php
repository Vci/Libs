<?php
/**
 * @file
 * Part of the voyager brain project.
 *
 * @author "David Hazel" <dhazel@gmail.com>
 *
 * © Copyright 2012 Voyager Components. All Rights Reserved.
 */

//require_once('my/path/to/file.php');

/**
 * Utility class containing useful string hashing functions.
 **/
class Vci_String_Hash
{
    //====== construction ============================================
    

    //====== variables ===============================================
    // (none)

    //====== methods =================================================
    /**
     * Short-hasher, returns an alpha-numeric multi-case hash
     *
     * Taken from the public domain.
     *
     * Provides a much lower collision probability (esp for short hashes) than 
     *  the hash2() method.
     *
     * @param string $input
     *  The input string to hash
     * @param int $length
     *  The desired length for the generated hash
     * @return A multi-case, alpha-numeric hash
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function hash1($input, $length = 8){
        // code...
        return substr(
            preg_replace('#[_/]+#','',base64_encode(sha1($input))),
            0,
            $length
        );
    }

    /**
     * Short-hasher, returns an alphabetical lower-case hash
     *
     * Taken from the public domain.
     *
     * For 100,000 different strings you'll have ~2% chance of hash collision 
     *  (for a 8 chars long hash), and for a million of strings this chance 
     *  rises up to ~90%.
     *
     * @param string $input
     *  The input string to hash
     * @param int $length
     *  The desired length for the generated hash
     * @return A lower-case, alphabetical hash
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function hash2($input, $length = 8){
        // Convert to a string which may contain only characters [0-9a-p]
        $hash = base_convert(md5($input), 16, 26);

        // Get part of the string
        $hash = substr($hash, -$length);

        // In rare cases it will be too short, add zeroes
        $hash = str_pad($hash, $length, '0', STR_PAD_LEFT);

        // Convert character set from [0-9a-p] to [a-z]
        $hash = strtr($hash, '0123456789', 'qrstuvwxyz');

        return $hash;
    }
}
?>
