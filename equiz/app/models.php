<?php
/*
* EQuiz
* Copyright (C) 2010 Wang, Leo Li
* All rights reserved.
* License: GNU/GPL License v2 or later
* EQuiz is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*
* models.php 2010-10-24 leow
* model classes
* 2014-04-22 leow	add dbLoadChkSub() for read quiz/question data
*			only for submission check
* 2014-04-24 leow	add more args to dbRead()
*			extra (for limit, order by etc.) and 
*			substr (for select only substr of given fields)
*/
require($dirbase."/app/equiz_db.php");

// model list
$models = array("question", "quiz", "particip");

class CModel {
	public $from = '';
	public $fields = array();
	public $where = null;
	protected $db;

	public function __construct() {
		global $eqdb;
		$this->db = $eqdb;
	}

	public function dbDel($where, $from=null) {
		if (!isset($from) && strlen($from) == 0) {
			$from = $this->from;
		}
		if (!isset($where) && strlen($where) == 0) {
			die('Must specify where clause');
		}
		$sql = 'delete from ' . $from . ' where ' . $where;
		try {
			$this->db->dbe($sql);
		}
		catch (Exception $e) {
	        echo "del db failed\n" . $e->getMessage() . "\n". $sql;
	    }
	}
	
	public function dbRead($fields=null, $where=null, $extra=null, $substr=null, $from=null) {
		if (!isset($fields) || count($fields) == 0) {
			$fields = $this->fields;
		}
		if (!isset($from) || strlen($from) == 0) {
			$from = $this->from;
		}
		if (!isset($where) || strlen($where) == 0) {
			$where = $this->where;
		}
		$sql = 'select ';
		foreach ($fields as $field=>$label) {
			if (is_int($field)) {
				if (isset($substr[$label])) {
					$label = 'substr('. $label .',0,'. intval($substr[$label]) .') as ' . $label;
				}
				$sql .= $label . ',';
			} else {
				if (isset($substr[$field])) {
					$field = 'substr('. $field .',0,'. intval($substr[$field]) .')';
				}
				$sql .= $field . ' as ' . $label . ',';
			}
		}
		$sql = rtrim($sql, ',');
		$sql .= ' from ' . $from;
		if (isset($where) && strlen($where) > 0) {
			$sql .= ' where ' . $where;
		}
		if (isset($extra) && strlen($extra) > 0) {
			$sql .= ' ' . $extra;
		}
		try {
			$rc = $this->db->dbq($sql);
			if (!$rc || count($rc) != 1) {
				$rc = array();
				foreach ($fields as $field) {
					$rc[0][$field] = '';
				}
			} else {
				$rc = $rc->fetchAll(PDO::FETCH_ASSOC);
			}
		}
		catch (Exception $e) {
	        echo "read db failed\n" . $e->getMessage() . "\n". $sql;
	        return null;
	    }
		return $rc;
	}
}

class questionModel extends CModel {
	const OP_SEP = "|";
	const Q_SEP = ";";
	const QA_SEP = ":";
	public $from = 'question';
	public $fields = array('id' => 'ID',
	                       'quizId' => 'Quiz_ID',
	                       'seq' => 'seq_In_Quiz',
	                       'type' => 'Type',
	                       'body' => 'Body',
	                       'options' => 'Options',
	                       'answers' => 'Answers',
	                       'comments' => 'Comments',
	                       'mtime' => 'last_modify_time',
	                      );

	public function dbWrite($attribs, $id='NULL') {
		$sql = sprintf("insert  or replace into Question (id, quizId, seq, type, body, options, answers, comments, mtime)
		                VALUES (%s, %d, %d,  %d, '%s', '%s', '%s', '%s', datetime(CURRENT_TIMESTAMP, 'localtime'));",
		               $id, $attribs['quizId'], $attribs['seq'], $attribs['type'],
		               SQLite3::escapeString($attribs['body']),
		               SQLite3::escapeString($attribs['options']),
		               SQLite3::escapeString($attribs['answers']),
		               SQLite3::escapeString($attribs['comments'])
		              );
		$this->db->dbe($sql);
	}

}

class particpModel extends CModel {
	const TAG_SEP = ",";
	public $from = 'partInfo';
	public $fields = array('id' => 'ID',
	                       'name' => 'Display_Name',
	                       'email' => 'Email',
	                      );

	public function dbWrite($attribs, $id='NULL') {
		$name = SQLite3::escapeString($attribs['name']);
		$email = SQLite3::escapeString($attribs['email']);
		$sql = sprintf("insert or replace into PartInfo (id, name, email)
		                VALUES (%s, '%s', '%s');",
		               $id,
		               $name,
		               $email
		              );
		$this->db->dbe($sql);
		if ($id == 'NULL') {
			$rc = $this->db->dbq('select max(id) from partInfo where name="'.$name.'" and email="'.$email.'"');
			$id = $rc->fetch(PDO::FETCH_COLUMN);
		}
		if(intval($id) == 0) return;
		$tags = $this->db->dbq_tags();
		foreach($tags as $tag) {
			if(array_key_exists('tag_'. $tag, $attribs)) {
				$sql = sprintf('insert or replace into subInfo (pid, tag) values (%d, "%s")', $id, $tag);
			} else {
				$sql = sprintf('delete from subinfo where pid=%d and tag="%s"', $id, $tag);
			}
			$this->db->dbe($sql);
		}
	}

}

class quizModel extends CModel {
	public $from = 'quiz';
	public $fields = array('id' => 'ID',
	                       'title' => 'Title',
	                       'duetime' => 'CloseTime',
	                       'descrip' => 'Quiz_Description',
	                       'tag' => 'Tag',
	                      );
	public $questions = array();
	public $attribs = array();

	public function dbWrite($attribs, $id='NULL') {
		$sql = sprintf("insert or replace into Quiz (id, title, duetime, tag, descrip)
		                VALUES (%s, '%s', '%s', '%s', '%s');",
		               $id,
		               SQLite3::escapeString($attribs['title']),
		               $attribs['duetime'],
		               SQLite3::escapeString($attribs['tag']),
		               SQLite3::escapeString($attribs['descrip'])
		              );
		$this->db->dbe($sql);
	}

	public function dbLoadChkSub($id) {
		$this->db->dbq_quizexist($id) or die('Quiz requested not exists');
		$qm = new questionModel();
		return $qm->dbRead(array('id', 'type', 'options', 'answers'), 'quizId=' . $id . ' and type!=4 order by seq');
	}
	
	public function dbLoad($id) {
		$this->db->dbq_quizexist($id) or die('Quiz requested not exists');
		$qm = new questionModel();
		$this->questions = $qm->dbRead(null, 'quizId=' . $id . ' order by seq');
		$quiz = $this->dbRead(null, 'id=' . $id);
		$this->attribs = $quiz[0];
		$adm = $this->db->dbq_admByTag($quiz['0']['Tag']);
		$rc = array('questions' => $this->questions,
		            'quiz' => $quiz[0],
		            'admEmail' => $adm['email'],
		            'admin' => $adm['admin'],
		           );
		return $rc;
	}
	
	public function dbGetAnswer($id) {
		$qids = explode(',', $id);
		$rc = array();
		foreach ($qids as $qid) {
			$qid = trim($qid);
			if (!$this->db->dbq_quizexist($id)) continue;
			$qm = new questionModel();
			$fields = array('id', 'answers');
			$questions = $qm->dbRead($fields, 'quizId=' . $qid . ' and type!=4 order by seq');
			$rc[$qid] = array();
			foreach ($questions as $q) {
				$rc[$qid][$q['id']] = $q['answers'];
			}
		}
		return $rc;
	}
	
	public function dbReadSub($id) {
		$sql = 'select pId, quizId, name, email, subValue, subtime from submission, partInfo ' .
		       'where submission.pId = partInfo.id and submission.quizId in (' . $id .
		       ')';
		try {
			$rc = $this->db->dbq($sql);
			$rc = $rc->fetchAll(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
	        echo "read db failed\n" . $e->getMessage() . "\n". $sql;
	    }
	    return $rc;
	}
	
	public function dbWriteToken($qid, $pid, $token) {
		$sql = sprintf("insert or replace into Token (quizId, pId, token) values(%d, %d, '%s')",
                       $qid, $pid, $token);
		try {
			$this->db->dbe($sql);
		}
		catch (Exception $e) {
	        echo "write db failed\n" . $e->getMessage() . "\n". $sql;
	    }
	}

	public function dbUpdState($qid, $pid) {
		$sql = 'update Token set stat=1 where quizId='.$qid.' and pId='.$pid;
		try {
			$this->db->dbe($sql);
		}
		catch (Exception $e) {
	        echo "write db failed\n" . $e->getMessage() . "\n". $sql;
	    }
	}
	
	public function dbReadState($id, $pid=null) {
		$tag = $this->db->dbq_qtag($id);
		$sql = 'select id, name, email from partInfo, subinfo '.
		       'where subInfo.tag="'. $tag .'" and id=pid';
		if(isset($pid)) {
			$sql .= ' and partInfo.id=' . $pid;
		}
		try {
			$rc = $this->db->dbq($sql);
			$rc = $rc->fetchAll(PDO::FETCH_ASSOC);
			foreach ($rc as &$p) {
				$sql = 'select token, stat from token '.
			           'where pId='.$p['id'] . ' and quizId=' . $id;
				$t = $this->db->dbq($sql);
				$p['stat'] = $p['token'] = null;
				if ($t) {
					$t = $t->fetch(PDO::FETCH_ASSOC);
					if (is_array($t)) {
						foreach($t as $f=>$v) {
							$p[$f] = $v;
						}
					}
				}
				$sql = 'select subtime, subValue from submission '.
			           'where pId='.$p['id'] . ' and quizId=' . $id;
				$p['subValue'] = $p['subtime'] = null;
				$sub = $this->db->dbq($sql);
				if ($sub) {
					$sub = $sub->fetch(PDO::FETCH_ASSOC);
					if (is_array($sub)) { 
						foreach($sub as $f=>$v) {
							$p[$f] = $v;
						}
					}
				}
			}
		}
		catch (Exception $e) {
	        echo "read db failed\n" . $e->getMessage() . "\n". $sql;
	        return null;
	    }
		return $rc;
	}

	public function dbSubmita($qid, $pid, $astr) {
		$this->db->dbin_submi($qid, $pid, $astr);
	}
}

