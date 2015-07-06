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
 * A utility class for sending simple email.
 **/
interface Vci_Mail
{
    /**
     * Sends a simple email
     *
     * @param string  $subject
     *  The email subject.
     * @param string  $body
     *  The email body.
     * @param string  $sender
     *  The email of the sender.
     * @param string  $recipient
     *  The email of the recipient.
     */
    function send($subject, $body, $sender, $recipient);

}
?>
