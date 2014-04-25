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
* quiz_db.php 2010-09-14 leow
* 2014-04-22 Leow	modify dbq_quizdue() to return both bool(overdue) and duetime
*/


Class Equiz_DB {
	protected $dbf = NULL;
	protected $db = NULL;

	public function Equiz_DB($f) {
		$this->dbf = $f;
		try {
	        $this->db = new PDO($f);
	    }
	    catch (Exception $e) {
	        echo "open equize sqlite db Failed:\n" . $e->getMessage();
	    }
	}

	public function dbq($sql) {
	    try {
	        $rc = $this->db->query($sql);
        	return $rc;
	    }
	    catch (Exception $e) {
	        echo "query equize db Failed:\n" . $e->getMessage();
	        return NULL;
	    }
	}

	public function dbe($sql) {
	    try {
	        $this->db->exec($sql);
	        return True;
	    }
	    catch (Exception $e) {
	        echo "exec equize db Failed:\n" . $e->getMessage();
	        return False;
	    }
	}

	public function dbq_quiz($id) {
		$sql = sprintf("select id, title, descrip, datetime(duetime, 'localtime') as duetime from Quiz where id=%d", $id);
		$rc = $this->dbq($sql);
		return $rc;
	}

	public function dbq_qtype($qid) {
		$sql = sprintf("select type from Question where id=%d", $qid);
		$rc = $this->dbq($sql)->fetch(PDO::FETCH_NUM);
		return $rc[0];
	}

	public function dbq_qlist($quizid) {
		$sql = sprintf("select question_id from Question_list where quizId=%d order by seq", $quizid);
		$rc = $this->dbq($sql);
		return $rc->fetchAll(PDO::FETCH_COLUMN);
	}

	public function dbq_question($qid) {
		$sql = sprintf("select id, type, body, options, answers from Question where id=%d", $qid);
		$rc = $this->dbq($sql);
		return $rc;
	}

	public function dbin_token($qid, $pid, $token) {
		$sql = sprintf("insert into Token (token, quizId, pId) values('%s', %d, %d)",
		               $token, $qid, $pid);
		return $this->dbe($sql);
	}

	public function dbin_submi($qid, $pid, $astr) {
		$sql = sprintf("insert or replace into Submission (subValue, quizId, pId, subtime) values('%s', %d, %d, CURRENT_TIMESTAMP)",
		               SQLite3::escapeString($astr), $qid, $pid);
		return $this->dbe($sql);
	}

	public function dbin_htmlCache($qid, $type, $str) {
		$sql = sprintf("insert or replace into htmlCache (quizId, type, body, mtime) values(%d, %d, '%s', CURRENT_TIMESTAMP)",
		               $qid, $type, SQLite3::escapeString($str));
		return $this->dbe($sql);
	}

	public function dbq_htmlCache($qid, $type) {
		$sql = sprintf("select body from htmlCache where quizId=%d and type=%d", $qid, $type);
		$rc = $this->dbq($sql)->fetch(PDO::FETCH_NUM);
		return $rc[0];
	}

	public function dbq_email($pid) {
		$sql = sprintf("select email from P_info where id=%d", $pid);
		$rc = $this->dbq($sql)->fetch(PDO::FETCH_NUM);
		return $rc[0];
	}

	public function dbListQuiz() {
		$sql = "select id, title from quiz";
		return $this->dbq($sql)->fetchAll(PDO::FETCH_ASSOC);
	}

	public function dbq_quizexist($qid) {
		$sql = sprintf("select 1 from quiz where id=%d", $qid);
		$rc = $this->dbq($sql)->fetch(PDO::FETCH_NUM);
		return ($rc[0]==1);
	}

	public function dbq_token($qid, $pid) {
		$sql = sprintf("select token from Token where quizId=%d and pId=%d",
		               $qid, $pid);
		$rc = $this->dbq($sql)->fetch(PDO::FETCH_NUM);
		return $rc[0];
	}

	public function dbq_quizdue($qid) {
		$sql = sprintf("select datetime('now')>duetime, duetime from quiz where id=%d", $qid);
		$rc = $this->dbq($sql)->fetch(PDO::FETCH_NUM);
		return array($rc[0]==1, $rc[1]);
	}

	public function dbq_vtoken($qid, $pid, $token) {
		$sql = sprintf("select token=='%s' from token where pId=%d and quizId=%d",
		               $token, $pid, $qid);
		$rc = $this->dbq($sql)->fetch(PDO::FETCH_NUM);
		return ($rc[0]==1);
	}

	public function dbq_pname($pid) {
		$sql = sprintf("select name from PartInfo where id=%d", $pid);
		$rc = $this->dbq($sql)->fetch(PDO::FETCH_NUM);
		return $rc[0];
	}
}

$eqdb = new Equiz_DB($dbfile);
?>
