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
*/
Class EmailHelper {
	protected $args = array('to' => null,
	                        'from' => null,
	                        'subject' => "EmailHelper test",
	                        'message' => "EmailHelper test",
	                        'headers' => "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n",
	                        'cc' => null,
	                        'bcc' => null);

	public function EmailHelper($args=array()) {
		$this->update($args);
	}

	public function update($args=array()) {
		if (!is_array($args)) {return;}
		foreach (array_keys($this->args) as $k) {
			if(isset($args[$k])) {$this->args[$k] = $args[$k];}
		}
	}

	public function sndMail($args=array()) {
		if (!is_array($args)) {return False;}
		foreach (array_keys($this->args) as $k) {
			if(!isset($args[$k])) {$args[$k] = $this->args[$k];}
		}
		$headers = $args['headers']."From: ".$args['from']."\r\n";
		$headers = $args['headers']."Reply-To: ".$args['from']."\r\n";
		$headers = $args['headers']."Return-Path: ".$args['from']."\r\n";
		//options to send to cc+bcc
		if(isset($args['cc'])) { $headers .= "Cc: ".$args['cc']."\r\n"; }
		if(isset($args['bcc'])) { $headers .= "Bcc: ".$args['bcc']."\r\n"; }
		// now lets send the email.
		try {
			mail($args['to'], $args['subject'], $args['message'], $headers);
		}
		catch (Exception $e) {
			echo "send mail Failed:\n" . $e->getMessage();
			return False;
		}
		return True;
	}
}

?>
