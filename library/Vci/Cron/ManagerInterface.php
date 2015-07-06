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
 * Class knows how to install cron jobs
 **/
interface Vci_Cron_ManagerInterface
{
    /**
     * @param string $string
     *  The command to execute via cron
     * @param int $interval
     *  See setInterval()
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setCommand($string, $interval = NULL);

    /**
     * @return [int] The number of seconds between scrape runs.
     **/
    public function getInterval();

    /**
     * Activates the pricing cron job
     *
     * @return void
     * @author David Hazel
     **/
    public function activateCronJob();

    /**
     * Deactivates the pricing cron job
     *
     * @return void
     * @author David Hazel
     **/
    public function deactivateCronJob();

    /**
     * Checks whether the pricing cron job is active
     *
     * @return bool
     * @author David Hazel
     **/
    public function isCronJobActive();

    /**
     * @param string $filePath
     *  The full path to the file that will be used as temporary storage while
     *  modifying the crontab.
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setTempFile($filePath);
}
?>
