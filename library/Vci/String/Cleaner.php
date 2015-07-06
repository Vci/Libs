<?php
/**
 * @file
 * Part of the voyager searcher brain project.
 *
 * @author David Hazel
 *
 * © Copyright 2011 Voyager Components. All Rights Reserved.
 */


//require_once('my/path/to/file.php');


/**
 * This is a utility class of special string cleaning functions.
 **/
class Vci_String_Cleaner
{
    //====== construction ============================================
    // (none)

    //====== variables ===============================================
    // (none)

    //====== methods =================================================
    /**
     * This function removes all elements from an html string that might be
     *  dangerous for a browser to execute, supposing that one does not trust
     *  the source of the html.
     *
     * (Useful for testing/viewing scraped pages)
     *
     * <code>
     * @code
     *      $page_string = getUtf8Page('voyagercomponents.com');
     *      $modified_page_string = removeDangerFromHtmlString($page_string);
     * @endcode
     * </code>
     *
     * @param string  $string
     *  A string of HTML-formatted text.
     * @return 
     *  A modified string that is easily human-readable without any dangerous 
     *  elements, but with no information removed.
     */
    static function removeDangerFromHtmlString($string) {
        $string = self::stripSrcAttributesFromHtmlString($string);
        $string = self::stripScriptsFromHtmlString($string);
        $string = self::breakReferencesInHtmlString($string);
        return $string;
    }

    /**
     * This function modifies all src attributes from the provided HTML string.
     *
     * @param string  $string
     *  A string of HTML-formatted text.
     * @return 
     *  A modified string with obfuscated src attributes.
     */
    static function stripSrcAttributesFromHtmlString($string) {
        // remove the src attributes
        return preg_replace('/src=/i','meta.src=',$string);
    }

    /**
     * This function strips all <script> nodes from the provided HTML string.
     *
     * @param string  $string
     *  A string of HTML-formatted text.
     * @return 
     *  A modified string with obfuscated <script> nodes.
     */
    static function stripScriptsFromHtmlString($string) {
        // remove the script nodes
        $string =  preg_replace('/< *script/i','<div meta="script" style="display:none;"',$string);
        $string = preg_replace('/<\/ *script *>/i','</div>',$string);
        return $string;
    }

    /**
     * This function breaks all "http://" type references in a page by replacing
     *  any instance of ":/" with ""; thus, "http://" becomes "http/".
     *
     * @param utf-8  $string
     *  A string of HTML-formatted utf-8 text.
     * @return 
     *  A modified string without any valid http links. (relative links are left
     *  untouched)
     */
    static function breakReferencesInHtmlString($string) {
        // transmogrify the "http://" prefix
        return preg_replace('/:\//','',$string);
    }

}
?>
