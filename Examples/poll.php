<?php
require('../autoloader.php');

/*
 * This script polls for new messages in the EPP system
 * The messages tell you if a domain name was transferred away to another provider
 * Or it tells you that your credit balance is low
 * Please use the pollack function to acknowledge a message and remove it from the queue
 */


try {
    echo "Polling for messages\n";
    $conn = new Metaregistrar\EPP\metaregEppConnection();
    $conn->setConnectionDetails('');
    // Connect and login to the EPP server
    if ($conn->login()) {
        $messageid = poll($conn);
        if ($messageid) {
            pollack($conn, $messageid);
        }
        $conn->logout();
    }
} catch (Metaregistrar\EPP\eppException $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

/**
 * @param $conn Metaregistrar\EPP\eppConnection
 * @return null|string
 */
function poll($conn) {
    try {
        $poll = new Metaregistrar\EPP\eppPollRequest(Metaregistrar\EPP\eppPollRequest::POLL_REQ, 0);
        if ((($response = $conn->writeandread($poll)) instanceof Metaregistrar\EPP\eppPollResponse) && ($response->Success())) {
            /* @var $response Metaregistrar\EPP\eppPollResponse */
            if ($response->getResultCode() == Metaregistrar\EPP\eppResponse::RESULT_MESSAGE_ACK) {
                echo $response->saveXML();
                echo $response->getMessageCount() . " messages waiting in the queue\n";
                echo "Picked up message " . $response->getMessageId() . ': ' . $response->getMessage() . "\n";
                return $response->getMessageId();
            } else {
                echo $response->getResultMessage() . "\n";
            }
        }
    } catch (Metaregistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
    return null;
}

/**
 * @param $conn Metaregistrar\EPP\eppConnection
 * @param $messageid string
 */
function pollack($conn, $messageid) {
    try {
        $poll = new Metaregistrar\EPP\eppPollRequest(Metaregistrar\EPP\eppPollRequest::POLL_ACK, $messageid);
        if ((($response = $conn->writeandread($poll)) instanceof Metaregistrar\EPP\eppPollResponse) && ($response->Success())) {
            /* @var $response Metaregistrar\EPP\eppPollResponse */
            echo "Message $messageid is acknowledged\n";
        }
    } catch (Metaregistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
}