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
 * This class knows how to install cron jobs, with a special emphasis on jobs
 *  that call http scripts.
 **/
class Vci_Cron_Manager_Web
extends Vci_Cron_Manager
implements Vci_Cron_Manager_WebInterface
{
    //====== construction ============================================
    /**
     * @param string $command
     *  See setCommand()
     * @param int $interval
     *  See setInterval()
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function __construct($command = NULL, $interval = NULL){
        parent::__construct($command, $interval);
    }

    //====== variables ===============================================
    /// (none)

    //====== methods =================================================
    /**
     * @param string $url
     *  The URL that will be polled by the cron job
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setUrl($url){
        // typecheck
        if ( ! is_string($url) ) {
            throw new Exception(sprintf(
                'The "url" parameter must be a string. %s given',
                gettype($url)
            ));
        }

        // set the cron string to poll the url
        $this->setCommand(sprintf(
            '/usr/bin/wget --quiet %s',
            $url
        ));

        // return
        return $this;
    }
}
?>
