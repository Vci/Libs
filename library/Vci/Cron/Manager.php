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
 * This class knows how to install cron jobs
 **/
class Vci_Cron_Manager
implements Vci_Cron_ManagerInterface
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
        // set the cron string
        if ( $command !== NULL ) {
            $this->setCommand($command, $interval); 
        }
    }

    //====== variables ===============================================
    /// The time period (in seconds) between runs
    protected $interval = 240; // 4 minutes

    /// The crontab string for our scraper cron job (set in __construct())
    protected $cronString = '';

    /// The location of our cron temporary file
    protected $cronTempFile = '/tmp/vci-micron.txt';


    //====== methods =================================================
    /**
     * @param string $string
     *  The command to execute via cron
     * @param int $interval
     *  See setInterval()
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setCommand($string, $interval = NULL){
        // typecheck
        if ( ! is_string($string) ) {
            throw new Exception(sprintf(
                'The "string" parameter must be a string. %s given.',
                gettype($string)
            ));
        }

        // set the interval (if given)
        if ( $interval !== NULL ) {
            $this->setInterval($interval);
        }

        // set the cron string
        $this->cronString = sprintf(
            '*/%u *  *   *   *     %s',
            ($this->interval / 60),
            $string
        );

        // return
        return $this;
    }

    /**
     * @return [int] The number of seconds between scrape runs.
     **/
    public function getInterval(){
        return $this->interval;
    }

    /**
     * Activates the pricing cron job
     *
     * @return void
     * @author David Hazel
     **/
    public function activateCronJob(){
        // check for whether cron string has been set yet
        if ( $this->cronString === '' ) {
            throw new Exception(
                'Cron string is empty. Please set a cron command before activating the cron job. See setCommand().' 
            );
        }

        // return if nothing needs doing
        if( $this->isCronJobActive() ){
            return;
        }

        // get the crontab contents
        $contents = $this->getCronTab();

        // open temp file
        $fh = fopen($this->cronTempFile, 'w');

        // write all lines to temp file, commenting out our cron string
        foreach($contents as $line){
            if(preg_match('%^\s*#\s*'.preg_quote($this->cronString).'%', $line)){
                $line = preg_replace(
                    '%^\s*#\s*'.preg_quote($this->cronString).'%'
                    ,$this->cronString
                    ,$line
                );
                $found = true;
            }
            fwrite($fh, $line."\n");
        }
        if( ! isset($found) ){
            fwrite($fh, $this->cronString."\n");
        }
        fclose($fh);

        // load the new cron file
        exec('/usr/bin/crontab '.$this->cronTempFile);

        // return
        return;
    }

    /**
     * Deactivates the pricing cron job
     *
     * @return void
     * @author David Hazel
     **/
    public function deactivateCronJob(){
        // check for whether cron string has been set yet
        if ( $this->cronString === '' ) {
            return;
        }

        // return if nothing needs doing
        if( ! $this->isCronJobActive() ){
            return;
        }

        // get the crontab contents
        $contents = $this->getCronTab();

        // open temp file
        $fh = fopen($this->cronTempFile, 'w');

        // write all lines to temp file, commenting out our cron string
        foreach($contents as $line){
            if(preg_match('%^\s*'.preg_quote($this->cronString).'%', $line)){
                $line = preg_replace(
                    '%'.preg_quote($this->cronString).'%'
                    ,'#'.$this->cronString
                    ,$line
                );
            }
            fwrite($fh, $line."\n");
        }
        fclose($fh);

        // load the new cron file
        exec('/usr/bin/crontab '.$this->cronTempFile);

        // return
        return;
    }

    /**
     * Checks whether the pricing cron job is active
     *
     * @return bool
     * @author David Hazel
     **/
    public function isCronJobActive(){
        // check for whether cron string has been set yet
        if ( $this->cronString === '' ) {
            throw new Exception(
                'Cron string is empty. Please set a cron command before checking the cron job. See setCommand().' 
            );
        }

        // get the crontab contents
        $contents = $this->getCronTab();

        // loop through, looking for our cron line
        foreach($contents as $line){
            if(preg_match('%^\s*'.preg_quote($this->cronString).'%', $line)){
                return true;
            }
        }

        // if active cron line not found, return false
        return false;
    }

    /**
     * Gets the current crontab file contents
     *
     * @return An array of lines in the crontab file
     * @author David Hazel
     **/
    protected function getCronTab(){
        // get the crontab contents
        exec('/usr/bin/crontab -l', $output);

        // return contents
        return $output;
    }

    /**
     * @param string $filePath
     *  The full path to the file that will be used as temporary storage while
     *  modifying the crontab.
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setTempFile($filePath){
        // typecheck
        if ( ! is_string($filePath) ) {
            throw new Exception(sprintf(
                'The "filePath" parameter must be a string. %s given.',
                gettype($filePath)
            ));
        }

        // set the path
        $this->cronTempFile = $filePath;

        // return
        return $this;
    }
}
?>
