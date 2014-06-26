<?php
/*
* EQuiz
* Copyright (C) 2010 Wang, Leo Li
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* EQuiz is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*
* email helper class
* emailer.php 2010-09-20 leow
* 2014-06-25	leow	introduce swift mailer
*/
require_once('swift_required.php');

Class EmailHelper {
	protected $mailer;

	public function EmailHelper() {
		global $smtpSvr, $smtpPort;
		$transport = Swift_SmtpTransport::newInstance($smtpSvr, $smtpPort);
		// Create the Mailer using your created Transport
		$this->mailer = Swift_Mailer::newInstance($transport);
	}

	public function composeMsg($args=array()) {
		global $dbgEmail;
		$message = Swift_Message::newInstance();
		// Give the message a subject
		if(isset($args['subject'])) {
			$message->setSubject($args['subject']);
		} else {
			$message->setSubject('Test message');
		}

		// Set the From address with an associative array
		if(isset($args['from'])) {
			$message->setFrom($args['from']);
		} else {
			$message->setFrom($dbgEmail);
		}

		// Set the To addresses with an associative array
		if(isset($args['to'])) {
			$message->setTo($args['to']);
		} else {
			$message->setTo($dbgEmail);
		}

		// Give it a body
		//  ->setBody('Here is the message itself语文。', 'text/html')

		// And optionally an alternative body
		//->addPart('<q>Here is the message itself友谊</q>', 'text/html')

		// Optionally add any attachments
		//->attach(Swift_Attachment::fromPath('my-document.pdf'))
  		//;
 		// print the msg as text for debug purpose
		//echo $message->toString(); 
		return $message;
	}

	public function sndMail($message) {
		//options to send to cc+bcc
		if(isset($args['cc'])) { $headers .= "Cc: ".$args['cc']."\r\n"; }
		if(isset($args['bcc'])) { $headers .= "Bcc: ".$args['bcc']."\r\n"; }
		try {
			$result = $this->mailer->send($message);
		}
		catch (Exception $e) {
			echo "send mail Failed:\n" . $e->getMessage();
			return False;
		}
		return True;
	}
}

?>
