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
* controllers.php 2010-10-24 leow
* controller classes
*/

// controller list
$ctrls = array("site", "question", "quiz", "particip", "user");

class CController {
	
	protected $rules = array();
	public function __construct() {
		$this->md = new CModel();
	}
	
	public function defAct() {
	}
	
	public function checkAdm() {
		global $urlbase, $reqStr;
		session_start();
		if(!isset($_SESSION['admLogin'])) {
			$_SESSION['pre_req'] = $reqStr;
			header('Location: ' . $urlbase . '/site/login');
			exit;
		}
	}
	
	protected function preExec($actn) {
		if (isset($this->rules[$actn]) && method_exists($this, $this->rules[$actn])) {
			$act  = new ReflectionMethod(get_class($this), $this->rules[$actn]);
			$act->invoke($this);
		}
	}
	
	public function render($module, $view, $vargs=array()) {
		global $dirviews, $urlbase;
		$pstr = $dirviews . $module . '/';
		if (!isset($vargs)) {
			$vargs = array();
		}
		include($dirviews . 'header.php');
		include($pstr . $view . '.php');
		include($dirviews . 'footer.php');
	}
	
	public function exec($actn) {
		$this->preExec($actn);
		if (!isset($actn) || !method_exists($this, $actn.'Action')) {
			$this->defAct();
		} else {
			$act  = new ReflectionMethod(get_class($this), $actn.'Action');
			$act->invoke($this);
		}
	}
	
	public function delAction() {
		if(!isset($_REQUEST['id'])) {
			die('Please specify id to delete');
		}
		$this->md->dbDel('id=' . $_REQUEST['id']);
		$this->viewAction();
	}
}

Class siteController extends CController{
	protected $rules = array('menu' => 'checkAdm',
	                        );

	public function __construct() {
	}
	
	public function loginAction() {
		global $adminLogin, $adminPass;
		session_start();
		if (isset($_SESSION['admLogin'])) {
			$this->render('site', 'menu');
			return;
		}
		if (isset($_POST['user']) &&
		    $_POST['user'] == $adminLogin &&
		    isset($_POST['pass']) &&
		    sha1($_POST['pass']) == $adminPass) {
			$_SESSION['admLogin'] = $_POST['user'];
			if (isset($_SESSION['pre_req'])) {
				global $urlbase;
				header('Location: ' . $urlbase . $_SESSION['pre_req']);
			} else {
				$this->render('site', 'menu');
			}
		} else {
			$vargs = array('warnmsg' => 'wrong login/password!');
			$this->render('site', 'login', $vargs);
		}
	}

	public function logoutAction() {
		session_start();
		unset($_SESSION['admLogin']);
		session_unset();
		$this->render('site', 'login');
	}

	public function menuAction() {
		$this->render('site', 'menu');
	}

	public function defAct() {
		$this->menuAction();
	}
}

Class questionController extends CController{

	protected $rules = array('add' => 'checkAdm',
	                         'edit' => 'checkAdm',
	                         'del' => 'checkAdm',
	                         'view' => 'checkAdm');

	public function __construct() {
		$this->md = new questionModel();
	}

	public function viewAction() {
		$this->render('question', 'view', $this->md->dbRead());
	}

	public function defAct() {
		$this->viewAction();
	}

	public function addAction() {
		if(isset($_POST['body'])) {
			$this->md->dbWrite($_POST);
			$this->viewAction();
		} else {
			$formfields = array('quizId' => array(),
			                    'seq' => array(),
			                    'type' => array(),
			                    'body' => array(),
			                    'options' => array(),
			                    'answers' => array(),
			                   );
			foreach(array_keys($formfields) as $f) {
				$formfields[$f]['lable'] = $this->md->fields[$f];
			}
			$formfields['body']['ftype'] = 'textarea';
			$formfields['options']['ftype'] = 'textarea';
			$this->render('question', 'add', $formfields);
		}
	}

	public function editAction() {
		isset($_REQUEST['id']) or die('id not specified');
		if(isset($_POST['body'])) {
			$this->md->dbWrite($_POST, $_REQUEST['id']);
			$this->viewAction();
		} else {
			$formfields = array('quizId' => array(),
			                    'seq' => array(),
			                    'type' => array(),
			                    'body' => array(),
			                    'options' => array(),
			                    'answers' => array(),
			                   );
			foreach(array_keys($formfields) as $f) {
				$formfields[$f]['lable'] = $this->md->fields[$f];
			}
			$values = $this->md->dbRead(array_keys($formfields), 'id='.$_REQUEST['id']);
			foreach(array_keys($formfields) as $f) {
				$formfields[$f]['value'] = $values[0][$f];
			}
			$formfields['body']['ftype'] = 'textarea';
			$formfields['options']['ftype'] = 'textarea';
			$this->render('question', 'edit', array('id' => $_REQUEST['id'], 'fields' => $formfields));
		}
	}
}

function cmp_score($a, $b) {
    if ($a['score'] == $b['score']) {
        return 0;
    }
    return ($a['score'] < $b['score']) ? 1 : -1;
}

Class quizController extends CController{

	protected $rules = array('add' => 'checkAdm',
	                         'del' => 'checkAdm',
	                         'edit' => 'checkAdm',
	                         'state' => 'checkAdm',
	                         'score' => 'checkAdm',
	                         'token' => 'checkAdm',
	                         'email' => 'checkAdm',
	                         'view' => 'checkAdm');

	public function __construct() {
		$this->md = new quizModel();
	}

	public function viewAction() {
		$this->render('quiz', 'view', $this->md->dbRead());
	}

	public function scoreAction() {
		isset($_REQUEST['id']) or die('Quiz id not specified');
		$answers = $this->md->dbGetAnswer($_REQUEST['id']);
		$subs = $this->md->dbReadSub($_REQUEST['id']);
		$scores = array();
		foreach ($subs as $s) {
			if (!isset($scores[$s['pId']])) {
				$scores[$s['pId']] = array();
				$scores[$s['pId']]['score'] = 0;
				$scores[$s['pId']]['name'] = $s['name'];
				$scores[$s['pId']]['email'] = $s['email'];
				$scores[$s['pId']]['sub_score'] = '';
			}
			$score = 0;
			$suba = array();
			$t = explode(questionModel::Q_SEP, $s['subValue']);
			foreach($t as $v) {
				$i = explode(questionModel::QA_SEP, $v);
				if (isset($i[1])) {
					$suba[$i[0]] = $i[1];
				}
			}
			foreach ($suba as $q=>$a) {
				if (isset($answers[$s['quizId']][$q]) && 
				    $answers[$s['quizId']][$q] == $a) {
				    $score += 1;
				}
			}
			$scores[$s['pId']]['sub_score'] .= $s['quizId'] . ': ' . $score . ', ' . $s['subtime'] . '; ';
			$scores[$s['pId']]['score'] += $score;
		}
		uasort($scores, 'cmp_score');
		$this->render('quiz', 'score', $scores);
	}

	public function submitAction() {
		$quiz_id = $_REQUEST['quiz_id'];
		global $eqdb;
		if ($eqdb->dbq_quizdue($quiz_id)) {
			die("Sorry, the Quiz had closed.");
		}
		// check token
		$eqdb->dbq_vtoken($quiz_id, $_POST['pid'], $_POST['token']) or die('invalid token');
		$data = $this->md->dbLoad($quiz_id);
		$answ = '';
		foreach ($data['questions'] as $q) {
			$sub = $q['ID'] . questionModel::QA_SEP;
			if ($q['Type'] == 1) {
				$fn = "q" . $q['ID'] . "_a";
				if (isset($_POST[$fn])) {
					$sub .= $_POST[$fn];
				}
			} elseif ($q['Type'] == 2) {
				$c = count(explode(questionModel::OP_SEP, $q['Options']));
				for ($i=0;$i<$c;$i++) {
					$fn = "q". $q['ID'] . "_a" . $i;
					if (isset($_POST[$fn])) {
						$sub .= $i.questionModel::OP_SEP;
					}
				}
			} elseif ($q['Type'] == 3) {
				$c = count(explode(questionModel::OP_SEP, $q['Answers']));
				for ($i=0;$i<$c;$i++) {
					$fn = "q". $q['ID'] . "_a" . $i;
					if (isset($_POST[$fn])) {
						$sub .= $_POST[$fn];
					}
					$sub .= questionModel::OP_SEP;
				}
			}
			$sub = rtrim($sub, questionModel::OP_SEP);
			$answ .= $sub.questionModel::Q_SEP;
		}
		$this->md->dbSubmita($quiz_id, $_POST['pid'], $answ);
		$un = $eqdb->dbq_pname($_POST['pid']);
		echo 'Thanks ' . $un . ' for your submission.';
	}
	
	public function addAction() {
		if(isset($_POST['title'])) {
			$this->md->dbWrite($_POST);
			$this->viewAction();
		} else {
			$formfields = array('title' => array(),
			                    'tags' => array(),
			                    'descrip' => array()
			                   );
			foreach(array_keys($formfields) as $f) {
				$formfields[$f]['lable'] = $this->md->fields[$f];
			}
			$formfields['duetime']['lable'] = 'CloseTime';
			$formfields['descrip']['ftype'] = 'textarea';
			$this->render('quiz', 'add', $formfields);
		}
	}
		
	public function takeAction() {
		isset($_REQUEST['id']) or die('Quiz id not specified');
		$vargs = $this->md->dbLoad($_REQUEST['id']);
		$vargs['token'] = '';
		$vargs['pid'] = '';
		isset($_POST['token']) and $vargs['token'] = $_POST['token'];
		isset($_GET['token']) and $vargs['token'] = $_GET['token'];
		isset($_REQUEST['pid']) and $vargs['pid'] = $_REQUEST['pid'];
		global $dirviews;
		include($dirviews . '/quiz/take.php');
	}


	public function stateAction() {
		isset($_REQUEST['id']) or die('Quiz id not specified');
		$vargs = array('qid' => $_REQUEST['id']);
		$t = $this->md->dbRead(array('title'), 'id='.$_REQUEST['id']);
		$vargs['title'] = $t[0]['title'];
		$vargs['pinfo'] = $this->md->dbReadState($_REQUEST['id']);
		$this->render('quiz', 'state', $vargs);
	}
	
	public function tokenAction() {
		isset($_REQUEST['id']) or die('Quiz id not specified');
		$pids = array();
		if (isset($_REQUEST['pid'])) {
			$pids[0] = $_REQUEST['pid'];
		} else {
			$parts = $this->md->dbReadState($_REQUEST['id']);
			foreach($parts as $p) {
				if (!isset($p['token'])) {
					array_push($pids, $p['id']);
				}
			}
		}
		global $dirbase;
		require($dirbase . 'app/common.php');
		foreach($pids as $pid) {
			$token = getRandKey();
			$this->md->dbWriteToken($_REQUEST['id'], $pid, $token);
		}
		$this->stateAction();
	}
	
	public function emailAction() {
		isset($_REQUEST['id']) or die('Quiz id not specified');
		$qid = $_REQUEST['id'];
		$pid = null;
		if (isset($_REQUEST['pid'])) {
			$pid = $_REQUEST['pid'];
		}
		$t = $this->md->dbRead(array('title'), 'id=' . $qid);
		$t = $t[0]['title'];
		$states = $this->md->dbReadState($qid, $pid);
		global $dirbase, $urlbase, $adminEmail;
		require($dirbase . 'app/emailer.php');
		// compose email
		$url = $urlbase . '/quiz/take/?id=' . $qid . '&pid=%d&token=%s';
		$msg = '<h1>Machine generated email. Do NOT reply.' . 
		       'Click <a href="%s">here</a> if msg not showing up properly.' .
		       '</h1><hr>';
		$args = array('from' => $adminEmail,
	                  'subject' => "EQuiz: " . $t,
	                 );
		$eh = new EmailHelper($args);
		foreach($states as $s) {
			if (isset($s['token']) && 
			    (!isset($s['stat']) || $s['stat'] < 1)
			   ) {
				$u = sprintf($url, $s['id'], $s['token']);
				$m = sprintf($msg, $u);
				ob_start();
				include $u;
				$m .= ob_get_contents();
				ob_end_clean();
				$eh->sndMail(array('message' => $m, 'to' => $s['email']));
				$this->md->dbUpdState($qid, $s['id']);
			}
		}
		$this->stateAction();
	}

	public function editAction() {
		isset($_REQUEST['id']) or die('id not specified');
		if(isset($_POST['title'])) {
			$this->md->dbWrite($_POST, $_REQUEST['id']);
			$this->viewAction();
		} else {
			$formfields = array('title' => array(),
			                    'tags' => array(),
			                    'descrip' => array(),
			                   );
			foreach(array_keys($formfields) as $f) {
				$formfields[$f]['lable'] = $this->md->fields[$f];
			}
			$fields = array_keys($formfields);
			$fields["datetime(duetime, 'localtime')"] = 'duetime';
			$formfields['duetime'] = array('lable' => 'CloseTime');
			$values = $this->md->dbRead($fields, 'id='.$_REQUEST['id']);
			foreach(array_keys($formfields) as $f) {
				$formfields[$f]['value'] = $values[0][$f];
			}
			$formfields['descrip']['ftype'] = 'textarea';
			$this->render('quiz', 'edit', array('id' => $_REQUEST['id'], 'fields' => $formfields));
		}
	}

	public function defAct() {
		$this->viewAction();
	}
}

Class participController extends CController{

	protected $rules = array('add' => 'checkAdm',
	                         'del' => 'checkAdm',
	                         'edit' => 'checkAdm',
	                         'view' => 'checkAdm');

	public function __construct() {
		$this->md = new particpModel();
	}

	public function viewAction() {
		$tags = null;
		isset($_POST['tags']) and $tags = $_POST['tags'];
		isset($_GET['tags']) and $tags = $_GET['tags'];
		isset($tags) and $tags = 'tags="'.$tags.'"';
		$this->render('particip', 'view', $this->md->dbRead(null, $tags));
	}

	public function defAct() {
		$this->viewAction();
	}

	public function addAction() {
		if(isset($_POST['name'])) {
			$this->md->dbWrite($_POST);
			$this->viewAction();
		} else {
			$formfields = array('name' => array(),
			                    'email' => array(),
			                    'tags' => array(),
			                   );
			foreach(array_keys($formfields) as $f) {
				$formfields[$f]['lable'] = $this->md->fields[$f];
			}
			$this->render('particip', 'add', $formfields);
		}
	}

	public function editAction() {
		isset($_REQUEST['id']) or die('id not specified');
		if(isset($_POST['name'])) {
			$this->md->dbWrite($_POST, $_REQUEST['id']);
			$this->viewAction();
		} else {
			$formfields = array('name' => array(),
			                    'email' => array(),
			                    'tags' => array(),
			                   );
			foreach(array_keys($formfields) as $f) {
				$formfields[$f]['lable'] = $this->md->fields[$f];
			}
			$values = $this->md->dbRead(array_keys($formfields), 'id='.$_REQUEST['id']);
			foreach(array_keys($formfields) as $f) {
				$formfields[$f]['value'] = $values[0][$f];
			}
			$this->render('particip', 'edit', array('id' => $_REQUEST['id'], 'fields' => $formfields));
		}
	}
}

?>

