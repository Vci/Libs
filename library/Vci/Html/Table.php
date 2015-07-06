<?php
/**
 * @file
 * General voyager library.
 *
 * @author David Hazel
 *
 * © Copyright 2011 Voyager Components. All Rights Reserved.
 */


//require_once('my/path/to/file.php');

/**
 * Generates an HTML table from an array.
 **/
interface Vci_Html_Table
{
    /**
     * Sets the class name for the table element.
     **/
    public function setClassName($name);

    /**
     * Sets the label for the table element.
     **/
    public function setLabelName($name);

    /**
     * Removes the specified column (by header name) from the table.
     **/
    public function removeColumn($name);

    /**
     * Generates the HTML5 source and prints it to the buffer.
     **/
    public function display();
}

?>
