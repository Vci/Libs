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
 * Class knows how to install cron jobs, with a special emphasis on jobs
 *  that call http scripts.
 **/
interface Vci_Cron_Manager_WebInterface
{
    /**
     * @param string $url
     *  The URL that will be polled by the cron job
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setUrl($url);
}
?>
