<?php
/**
 * @file
 * Part of the Voyager Components Libraries.
 *
 * @author "David Hazel" <dhazel@gmail.com>
 *
 * © Copyright 2012 Voyager Components. All Rights Reserved.
 */

//require_once('my/path/to/file.php');


/**
 * Outputs a progress-bar to the terminal session
 **/
class Vci_Cli_ProgressBar
{
    //====== construction ============================================
    /**
     * @param string $label
     *  The name of the bar (displayed on screen)
     * @param int $total
     *  The total progress available for the bar
     * @param int $start
     *  The starting progress of the bar
     * @param int $size
     *  The on-screen size of the bar
     * @return $this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function __construct(
        $label = '',
        $total = 100,
        $start = 0,
        $size = 50
    ){
        // typecheck
        if ( ! is_string($label) ) {
            throw new Exception(sprintf(
                'The "label" parameter must be a string. %s given.',
                gettype($label)
            ));
        } else if ( ! is_int($start) ) {
            throw new Exception(sprintf(
                'The "start" parameter must be a string. %s given.',
                gettype($start)
            ));
        } else if ( ! is_int($size) ) {
            throw new Exception(sprintf(
                'The "size" parameter must be a string. %s given.',
                gettype($size)
            ));
        }

        // set variables
        $this->label = $label;
        $this->currentPoint = $start;
        $this->size = $size;
        $this->setTotal($total);
    }

    //====== variables ===============================================
    /// The name of the bar (to be displayed)
    protected $label = '';

    /// The total progress points for the bar
    protected $total = 100;
    
    /// The on-screen size of the bar
    protected $size = 50;

    /// The current progress point of the bar
    protected $currentPoint = NULL;

    /// The current progress message
    protected $currentMessage = '';

    /// The current draw size of the bar
    protected $currentSize = NULL;
    
    /// When set, the progress-bar throttling is turned off
    protected $highSpeed = NULL;

    //====== methods =================================================
    /**
     * Sets the status message to be printed with the bar
     *
     * @param string $message
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setMessage($message = ''){
        // typecheck
        if ( is_string($message) ) {
            $this->currentMessage = $message;
        }

        return $this;
    }
    /**
     * Advances the bar one point
     *
     * @param string $message
     *  The message to print to screen with the progress update
     * @param string $barMessage
     *  The message to print with the bar
     * @param int $amount
     *  The number of points to advance the bar
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function advance($barMessage = '', $amount = 1){
        // update to an incremented current point
        $this->update(
            ($this->currentPoint + $amount),
            $barMessage
        );

        return $this;
    }

    /**
     * Updates the bar on the screen
     *
     * @param int $progressPoint
     *  The new progress point of the bar
     * @param string $barMessage
     *  The message to print with the bar
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function update($progressPoint, $barMessage = ''){
        // update the current point
        $this->currentPoint = $progressPoint;

        // redraw the bar
        $this->reDraw($barMessage);

        return $this;
    }

    /**
     * Redraws the bar
     *
     * @param string $barMessage
     *  The message to print with the bar
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function reDraw($barMessage = ''){
        // if present, erase the old bar
        if ( isset($this->currentSize) ) {
            // check whether we should be going high-speed (causes flickering) 
            if ( empty($this->highSpeed) ) {
                time_nanosleep(0, 10000000); 
            } 

            // echo a backspace (hex:08) to remove each previous character
            for ( $i = $this->currentSize; $i > 0; $i-- ) {
                fputs(STDERR, "\x08");
                fputs(STDERR, ' '); // overwrite char with space
                fputs(STDERR, "\x08");
            }
        }

        // leave behind the current message
        if ( (! empty($this->currentMessage)) && is_string($this->currentMessage) ) {
            fputs(STDERR, $this->currentMessage . "\n");
            unset($this->currentMessage);
        }

        // build the header of the new bar
        $bar = $this->label . ': ';

        // build the progress portion of the bar
        $bar .= '[';
        for ( $i = 0; $i <= $this->size; $i++ ) { 
            if ( $i <= ($this->currentPoint / $this->total * $this->size) ) {
                // output green spaces if we're finished through this point 
                $bar .= "\033[42m \033[0m";     
            } else { 
                $bar .= "\033[47m \033[0m";// or grey spaces if not 
            }
        } 
        $bar .= ']';

        // build the percent indicator
        $percentage = round(($this->currentPoint/$this->total)*100,2);        
        for ( $i=strlen($percentage); $i<=4; $i++ ) {
            $percentage = ' ' . $percentage;    
        }
        $bar .= ' ' . $percentage . '%';

        // add the message to the bar
        if ( ! empty($barMessage) ) {
            $bar .= ' | ' . $barMessage;
        }

        // check for the end
        //if ( $this->currentPoint == $this->total ) {
        //    $bar .= "\n" . 'Done' . "\n";
        //}

        // save the bar size
        $this->currentSize = strlen($bar);

        // output the bar
        fputs(STDERR, $bar); 

        return $this;
    }

    /**
     * Removes the throttling added by the progress-bar (causes flickering).
     *
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setHighSpeed($setting = true){
        $this->highSpeed = ! empty($setting);
        return $this;
    }
    /*
     * Setter for total
     * 
     * @param int total
     * @return this
     */
    public function setTotal( $total )
    {
        if ( ! is_int($total) ) {
            throw new Exception(sprintf(
                'The "total" parameter must be an int. %s given.',
                gettype($total)
            ));
        }
        $this->total = $total;
        return $this;
    }
    
}
