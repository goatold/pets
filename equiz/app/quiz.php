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
* quiz.php 2010-09-16 leow
*/

// html template
require_once(dirname(__FILE__)."/htmltempl.php");
// common funcs
require_once(dirname(__FILE__)."/common.php");

Class Question {
	const OP_SEP = "|";
	const Q_SEP = ";";
	const QA_SEP = ":";
	protected $id = NULL;
	protected $body = NULL;
	protected $options = NULL;
	protected $answers = NULL;

	public function Question($id) {
		$this->id = $id;
	}

	public function dbLoad($db) {
		try {
			$rc = $db->dbq_question($this->id);
			if (!$rc || count($rc) != 1) {return False;}
			$dbq = $rc->fetch(PDO::FETCH_ASSOC);
			$this->body = $dbq['body'];
			$this->options = explode(self::OP_SEP, $dbq['options']);
			$this->answers = $dbq['answers'];
		}
		catch (Exception $e) {
	        echo "load question from db failed\n" . $e->getMessage();
	        return False;
	    }
		return True;
	}

	public function chkAns($a) {
		return ($a[$this->id] == $this->answers);
	}
}

Class Question_sc extends Question {
	public function genHtml($ans=False) {
		$choices = "";
		$dis = $ans;
		foreach ($this->options as $cid=>$cstr) {
			$chk = $ans && ($cid == $this->answers);
			$choices .= genhtm_sc_radio($this->id, $cid, $cstr, $dis, $chk);
		}
		$strHrml = genhtm_q_sc($this->id, $this->body, $choices);
		return $strHrml;
	}

	public function readForm($f) {
		$sub = $this->id.self::QA_SEP;
		$fn = "q".$this->id."_a";
		if (isset($f[$fn])) {
			$sub .= $f[$fn];
		}
		return $sub.self::Q_SEP;
	}
}

Class Question_mc extends Question {
	public function genHtml($ans=False) {
		$choices = "";
		$dis = $ans;
		foreach ($this->options as $cid=>$cstr) {
			$chk = $ans && !(strpos($this->answers, strval($cid))===False);
			$choices .= genhtm_mc_chkbox($this->id, $cid, $cstr, $dis, $chk);
		}
		$strHrml = genhtm_q_mc($this->id, $this->body, $choices);
		return $strHrml;
	}

	public function readForm($f) {
		$sub = $this->id.self::QA_SEP;
		$c = count($this->options);
		for ($i=0;$i<$c;$i++) {
			$fn = "q".$this->id."_a".$i;
			if (isset($f[$fn])) {
				$sub .= $i.self::OP_SEP;
			}
		}
		$sub = rtrim($sub, self::OP_SEP);
		return $sub.self::Q_SEP;
	}
}

Class Question_bf extends Question {
	public function genHtml($ans=False) {
		$strHrml = "";
		$sect = explode($this->options[0],$this->body);
		$lsec = array_pop($sect);
		$va = array();
		if ($ans) {$va = explode(self::OP_SEP, $this->answers);}
		foreach ($sect as $bid=>$bstr) {
			$strHrml .= $bstr;
			$v = null;
			if (isset($va[$bid])) {$v=$va[$bid];}
			$strHrml .= genhtm_bf_text($this->id, $bid, $ans, $v);
		}
		$strHrml .= $lsec;
		$strHrml = genhtm_q_bf($this->id, $strHrml);
		return $strHrml;
	}

	public function readForm($f) {
		$sub = $this->id.self::QA_SEP;
		$c = count(explode(self::OP_SEP, $this->answers));
		for ($i=0;$i<$c;$i++) {
			$fn = "q".$this->id."_a".$i;
			if (isset($f[$fn])) {
				$sub .= $f[$fn];
			}
			$sub .= self::OP_SEP;
		}
		$sub = rtrim($sub, self::OP_SEP);
		return $sub.self::Q_SEP;
	}
}
// This is not used for now
Class Question_pt extends Question {
	public function genHtml($ans=False) {
		return "<div>" . this->$body . "</div>";
	}

	public function readForm($f) {
		return "";
	}
	// TODO: change db query to get body only
	public function dbLoad($db) {
		try {
			$rc = $db->dbq_question($this->id);
			if (!$rc || count($rc) != 1) {return False;}
			$dbq = $rc->fetch(PDO::FETCH_ASSOC);
			$this->body = $dbq['body'];
		}
		catch (Exception $e) {
	        echo "load question from db failed\n" . $e->getMessage();
	        return False;
	    }
	}

	public function chkAns($a) {
		return true;
	}
}

$QTYPE_CLASS = array(1=>'Question_sc',
                     2=>'Question_mc',
                     3=>'Question_bf',
                     4=>'Question_pt'
                     );
Class Quiz {
	protected $id = NULL;
	protected $title = NULL;
	protected $descrip = NULL;
	protected $duetime = NULL;
	protected $qlist = array();
	protected $plist = array();

	public function Quiz($id) {
		$this->id = $id;
	}

	public function dbLoad($db) {
		global $QTYPE_CLASS;
		try {
			$rc = $db->dbq_quiz($this->id);
			if (!$rc || count($rc) != 1) {return false;}
			$dbq = $rc->fetch(PDO::FETCH_ASSOC);
			$this->title = $dbq['title'];
			$this->descrip = $dbq['descrip'];
			$this->duetime = $dbq['duetime'];
			$qidlist = $db->dbq_qlist($this->id);
			foreach ($qidlist as $seq=>$qid) {
				$qtype = $db->dbq_qtype($qid);
				$qClass = new ReflectionClass($QTYPE_CLASS[$qtype]);
				$q = $qClass->newInstanceArgs(array('id'=>$qid));
				$q->dbLoad($db);
				$this->qlist[$seq] = $q;
			}
		}
		catch (Exception $e) {
	        echo "load quiz from db failed\n" . $e->getMessage();
	        return False;
	    }
		return True;
	}

	public function dbAddParticip($db, $pid) {
		try {
			// check if token already in db
			$token = $db->dbq_token($this->id, $pid);
			if (!isset($token)){
				$token = getRandKey();
				$db->dbin_token($this->id, $pid, $token);
			}
			return $token;
		}
		catch (Exception $e) {
	        echo "add token to db failed\n" . $e->getMessage();
	    }
	    return null;
	}

	public function genHtml($token, $pid, $ans=False) {
		$qhtml = "";
		foreach ($this->qlist as $q) {
			$qhtml .= $q->genHtml($ans);
		}
		$strHrml = genhtm_quiz($this->id, $this->title,
		                       $this->descrip, $this->duetime,
		                       $token, $pid, $qhtml, (!$ans));
		return $strHrml;
	}

	public function readForm($f) {
		$sub = "";
		foreach ($this->qlist as $q) {
			$sub .= $q->readForm($f);
		}
		return $sub;
	}

	public function chkAns($astr) {
		$a = array();
		$t = explode(Question::Q_SEP, $astr);
		foreach($t as $v) {
			$i = explode(Question::QA_SEP, $v);
			if(isset($i[1])) {
				$a[$i[0]] = $i[1];
			}
		}
		foreach ($this->qlist as $q) {
			if(!$q->chkAns($a)) { return False; }
		}
		return True;
	}
}

?>
