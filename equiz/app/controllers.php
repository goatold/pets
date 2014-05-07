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
* 2014-04-23 leow 	add few new features
- list question per quiz
- list 10 quiz per page
- add comments fields to question for answer explanation. 
- add the "Review" link for question answer review
- add question type 4: descriptive text
*/

// controller list
$ctrls = array("site", "question", "quiz", "particip", "tag");

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

Class tagController extends CController{
	protected $rules = array('add' => 'checkAdm',
	                         'del' => 'checkAdm',
	                         'view' => 'checkAdm');

	public function __construct() {
		global $eqdb;
		$this->db = $eqdb;
	}
	
	public function delAction() {
		if(!isset($_REQUEST['tag'])) {
			die('Please specify tag to delete');
		}
		$this->db->dbe('delete from tags where tag="' . $_REQUEST['tag'] .'"');
		$this->viewAction();
	}

	public function defAct() {
		$this->viewAction();
	}

	public function viewAction() {
		$this->render('tag', 'view', $this->db->dbq_tags());
	}

	public function addAction() {
		if(isset($_POST['tag'])) {
			$this->db->dbe('insert into tags values("'. $_POST['tag'] .'")');
			$this->viewAction();
		} else {
			$this->render('tag', 'add');
		}
	}

}

Class questionController extends CController{

	protected $rules = array('add' => 'checkAdm',
	                         'edit' => 'checkAdm',
	                         'del' => 'checkAdm',
	                         'view' => 'checkAdm');

	private $formfields = array('quizId',
	                            'seq',
	                            'type',
	                            'body',
	                            'options',
	                            'answers',
	                            'comments');

	private $typeOptions = array(1 => 'Single Choice',
	                             2 => 'Multiple Choices',
	                             3 => 'Bland Filling',
	                             4 => 'Description Text');

	private function listQuiz() {
		$qzlist = array();
		// query list of quiz id and title
		global $eqdb;
		foreach($eqdb->dbListQuiz() as $q) {
			$qzlist[$q['id']] = $q['title'];
		}
		return $qzlist;
	}

	public function __construct() {
		$this->md = new questionModel();
	}

	public function viewAction() {
		global $eqdb;
		if (isset($_REQUEST['quizId'])) {
			$quizId = $_REQUEST['quizId'];
		}
		if (isset($quizId) && $eqdb->dbq_quizexist($quizId)) {
			$substr = array('body'=>100, 'options'=>100, 'comments'=>100);
			$this->render('question', 'view',
			              array('data' => $this->md->dbRead(null, 'quizId=' . $quizId, Null, $substr),
			                    'quizId' => $quizId));
		} else {
			$this->render('question', 'view',
			              array('data' => $this->md->dbRead()));
		}
	}

	public function defAct() {
		$this->viewAction();
	}

	public function addAction() {
		if(isset($_POST['body'])) {
			$this->md->dbWrite($_POST);
			$this->viewAction();
		} else {
			$formdata = array();
			foreach($this->formfields as $f) {
				$formdata[$f] = array('label' => $this->md->fields[$f]);
			}
			$formdata['type']['options'] = $this->typeOptions;
			if(isset($_REQUEST['quizId'])) {
				$formdata['quizId']['value'] = $_REQUEST['quizId'];
			}
			$formdata['quizId']['options'] = $this->listQuiz();
			$this->render('question', 'add', $formdata);
		}
	}

	public function editAction() {
		isset($_REQUEST['id']) or die('id not specified');
		if(isset($_POST['body'])) {
			$this->md->dbWrite($_POST, $_REQUEST['id']);
			$this->viewAction();
		} else {
			$formdata = array();
			foreach($this->formfields as $f) {
				$formdata[$f] = array('label' => $this->md->fields[$f]);
			}
			$values = $this->md->dbRead($this->formfields, 'id='.$_REQUEST['id']);
			foreach($this->formfields as $f) {
				$formdata[$f]['value'] = $values[0][$f];
			}
			$formdata['type']['options'] = $this->typeOptions;
			$formdata['quizId']['options'] = $this->listQuiz();
			$this->render('question', 'edit', array('id' => $_REQUEST['id'], 'fields' => $formdata));
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

	private $formfields = array('title', 'tag', 'descrip', 'duetime');

	public function __construct() {
		$this->md = new quizModel();
	}

	public function viewAction() {
		global $eqdb, $viewPgSize;
		$cnt = intval($eqdb->dbq('select count() from quiz')->fetch(PDO::FETCH_NUM)[0]);
		$maxpg = intval($cnt/$viewPgSize);
		if (($cnt%$viewPgSize) > 0) $maxpg++;
		$offset = 0;
		$pn = 1;
		if(isset($_REQUEST['pagen'])) {
			$pn = intval($_REQUEST['pagen']);
			if ($pn > $maxpg) $pn = $maxpg;
			if ($pn < 1) $pn = 1;
			$offset = $viewPgSize * ($pn - 1);
		}
		$where = null;
		if(isset($_REQUEST['tag'])) {
			$where = 'tag="'. $_REQUEST['tag'] .'"';
		}
		$vargs = array();
		$vargs['data'] = $this->md->dbRead(null, $where, 'order by id desc limit '. $offset .','. $viewPgSize);
		$vargs['pn'] = $pn;
		$vargs['maxpg'] = $maxpg;
		$this->render('quiz', 'view', $vargs);
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
		$rc = $eqdb->dbq_quizdue($quiz_id);
		if ($rc[0]) {
			die("Sorry, the Quiz submissoin had closed by: ". $rc[1]);
		}
		// check token
		$eqdb->dbq_vtoken($quiz_id, $_POST['pid'], $_POST['token']) or die('invalid token');
		$data = $this->md->dbLoadChkSub($quiz_id);
		$answ = '';
		foreach ($data as $q) {
			$sub = $q['id'] . questionModel::QA_SEP;
			if ($q['type'] == 1) {
				$fn = "q" . $q['id'] . "_a";
				if (isset($_POST[$fn])) {
					$sub .= $_POST[$fn];
				}
			} elseif ($q['type'] == 2) {
				$c = count(explode(questionModel::OP_SEP, $q['options']));
				for ($i=0;$i<$c;$i++) {
					$fn = "q". $q['id'] . "_a" . $i;
					if (isset($_POST[$fn])) {
						$sub .= $i.questionModel::OP_SEP;
					}
				}
			} elseif ($q['type'] == 3) {
				$c = count(explode(questionModel::OP_SEP, $q['answers']));
				for ($i=0;$i<$c;$i++) {
					$fn = "q". $q['id'] . "_a" . $i;
					if (isset($_POST[$fn])) {
						$sub .= $_POST[$fn];
					}
					$sub .= questionModel::OP_SEP;
				}
			} else {
				continue;
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
			$formdata = array();
			foreach($this->formfields as $f) {
				$formdata[$f] = array('label' => $this->md->fields[$f]);
			}
			global $quizDueDay, $quizDueTime, $defaultTag, $eqdb;
			$date = new DateTime();
			$date->add($quizDueDay);
			$formdata['duetime']['value'] = $date->format("Y-m-d ") . $quizDueTime;
			$formdata['tag']['value'] = $defaultTag;
			$tags = array();
			foreach($eqdb->dbq_tags() as $tag) {
				$tags[$tag] = $tag;
			}
			$formdata['tag']['options'] = $tags;
			$this->render('quiz', 'add', $formdata);
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

	public function reviewAction() {
		isset($_REQUEST['id']) or die('Quiz id not specified');
		global $eqdb;
		$rc = $eqdb->dbq_quizdue($_REQUEST['id']);
		if(!isset($_SESSION)) { session_start(); }
		if(!isset($_SESSION['admLogin']) && 
		   !$rc[0]) {
			die('Please come back review answers after Quiz submission closure: '. $rc[1]);
		}
		$vargs = $this->md->dbLoad($_REQUEST['id']);
		global $dirviews;
		include($dirviews . 'quiz/review.php');
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
		global $dirbase, $urlbase, $quizMailHead, $adminEmail;
		// compose email
		require_once($dirbase . 'app/views/quiz/usrview.php');
		$vargs = $this->md->dbLoad($qid);
		$qh = genQzHtml($vargs);
		$args = array('from' => $adminEmail,
	                  'subject' => "EQuiz: " . $t,
	                 );
		require_once($dirbase . 'app/emailer.php');
		$eh = new EmailHelper($args);
		$url = $urlbase . '/quiz/take/?id=' . $qid . '&pid=%d&token=%s';
		foreach($states as $s) {
			if (isset($s['token']) && 
			    (!isset($s['stat']) || $s['stat'] < 1)
			   ) {
				$m = '<html><head>'. $htmlcss .'</head><body>';
				$u = sprintf($url, $s['id'], $s['token']);
				$m .= sprintf($quizMailHead, $u);
				$m .= $qh[0];
				$m .= genPinfoHtml($s['token'], $s['id']);
				$m .= $qh[1];
				$m .= '</body></html>';
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
			$formdata = array();
			foreach($this->formfields as $f) {
				$formdata[$f] = array('label' => $this->md->fields[$f]);
			}
			global $eqdb;
			$tags = array();
			foreach($eqdb->dbq_tags() as $tag) {
				$tags[$tag] = $tag;
			}
			$formdata['tag']['options'] = $tags;
			$values = $this->md->dbRead($this->formfields, 'id='.$_REQUEST['id']);
			foreach($this->formfields as $f) {
				$formdata[$f]['value'] = $values[0][$f];
			}
			$this->render('quiz', 'edit', array('id' => $_REQUEST['id'], 'fields' => $formdata));
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

	public function unSubAction() {
		if(isset($_POST['email'])) {
			global $eqdb, $adminEmail, $subEmailDomain;
			$email = $_POST['email'];
			if(!($pos = strpos($email, '@'))) $email .= $subEmailDomain;
			$sql = 'select id from partinfo where email like "'.$email.'"';
			$rc = $eqdb->dbq($sql);
			if(!($rc&&($id=$rc->fetch(PDO::FETCH_COLUMN)))) {
				die("Email: ".$email." is not registered!");
			}
			$rip = $_SERVER['REMOTE_ADDR'];
			global $dirbase, $urlbase;
			require_once($dirbase . 'app/common.php');
			$token = getRandKey();
			$sql = 'insert into reginfo (token, email, op) values("'.$token.'", "'.$email.'", 2)';
			$eqdb->dbe($sql);
			// sending email
			$args = array('from' => $adminEmail,
			              'subject' => "EQuiz: unsubscription",
			             );
			require_once($dirbase . 'app/emailer.php');
			$eh = new EmailHelper($args);
			$url = $urlbase . '/particip/confsub/?token='. $token .'&email='. $email;
			$m =<<<EOV
<p>Someone from IP:$rip is trying to remove your email address ($email) from eQuiz. Please use following link to confirm the unsubscription</p>
<span>$url</span>
EOV;
			$eh->sndMail(array('message' => $m, 'to' => $email));
			echo '<p>Thank you for using eQuiz</p>';
			echo '<p>A confirmation email has been sent to <b>'. $email .
			     '</b>. Please check your email to complete the unsubscription.</p>';
		} else {
			global $dirviews, $urlbase;
			$vargs = array('email' => array('label'=>'Unsubscribing email:','attrib' => ' required '));
                	include($dirviews . 'particip/unsub.php');
		}
	}

	public function subscrbAction() {
		// get available tag from quiz
		global $eqdb, $adminEmail, $subEmailDomain;
		$tags = array();
		foreach($eqdb->dbq_tags() as $tag) {
			$tags[$tag] = $tag;
		}
		if(!is_array($tags) || count($tags)<1) {
			die('No quiz available for subscription. Please contact <b>'. $adminEmail .'</b> for help.');
		}
		if(isset($_POST['email'])) {
			$semail = $_POST['email'];
			$sname = $_POST['name'];
			if(!($pos = strpos($semail, '@'))) $semail .= $subEmailDomain;
			$rip = $_SERVER['REMOTE_ADDR'];
			$tagstr = '';
			foreach($tags as $t) {
				if(array_key_exists('tag_'.$t, $_POST)) $tagstr .= $t . particpModel::TAG_SEP;
			}
			$tagstr = trim($tagstr, particpModel::TAG_SEP);
			if (strlen($tagstr) < 1) die('No valid eQuiz Tag selected please go back check.'.
			    '<input type="button" onclick="history.back();" value="Back">');
			global $dirbase, $urlbase;
			require_once($dirbase . 'app/common.php');
			$token = getRandKey();
			$sql = 'insert into reginfo (token, name, email, tags, op) values("%s", "%s", "%s", "%s", 1)';
			$eqdb->dbe(sprintf($sql, $token, $sname, $semail, $tagstr));
			// sending email
			$args = array('from' => $adminEmail,
			              'subject' => "EQuiz: subscription",
			             );
			require_once($dirbase . 'app/emailer.php');
			$eh = new EmailHelper($args);
			$url = $urlbase . '/particip/confsub/?token='. $token .'&email='. $semail;
			$m =<<<EOV
<p>$sname from IP:$rip registered this email address to receive eQuiz emails. Please use following link to confirm the subscription</p>
<span>$url</span>
EOV;
			$eh->sndMail(array('message' => $m, 'to' => $semail));
			echo '<p>Thank you <strong>'. $sname .
			     '</strong> for subscribing eQuiz with tag: <i>' . $tagstr .'</i></p>';
			echo '<p>A confirmation email has been sent to <b>'. $semail .
			     '</b>. Please check your email to complete the subscription.</p>';
		} else {
			global $dirviews, $urlbase, $defaultTag;
			$vargs = array('name' => array('label'=>'Name:', 'attrib' => ' required '),
			               'email' => array('label'=>'Email:','attrib' => ' required '),
			               'tag' => array('label'=>'Subscribe To:', 'choices'=>$tags, 'values'=>array($defaultTag)));
                	include($dirviews . 'particip/subscrb.php');
		}
	}

	public function confsubAction() {
		global $eqdb;
		$email = $_REQUEST['email'];
		$token = $_REQUEST['token'];
		$sql = 'select name, tags, op from reginfo where email="'.$email.'" and token="'.$token.'"';
		$rc = $eqdb->dbq($sql);
		if(!($rc&&($rd=$rc->fetch(PDO::FETCH_ASSOC)))) die("No registration found!");
		if($rd['op'] == '2') { // unsubscrib
			$eqdb->dbe('delete from partinfo where email like "'.$email.'"');
			$eqdb->dbe('delete from reginfo where email="'.$email.'" and token="'.$token.'"');
			die("Bye <b>". $email ."</b>! You've been removed from eQuiz");
		}
		$name = $rd['name'];
		$stags = explode(particpModel::TAG_SEP, $rd['tags']);
		$sql = 'select id from partinfo where email like "'.$email.'"';
		$rc = $eqdb->dbq($sql);
		if(!($rc&&($id=$rc->fetch(PDO::FETCH_COLUMN)))) { // New user
			$eqdb->dbe('insert into partinfo (name, email) values("'.$name.'","'.$email.'")');
			$rc = $eqdb->dbq('select max(id) from partinfo where name="'.$name.'" and email="'.$email.'"');
			$id = $rc->fetch(PDO::FETCH_COLUMN);
		}
		$tags = $eqdb->dbq_tags();
		// update subInfo table
		foreach($tags as $tag) {
			if(is_int(array_search($tag, $stags))) {
				$sql = sprintf('insert or replace into subInfo (pid, tag) values (%d, "%s")', $id, $tag);
			} else {
				$sql = sprintf('delete from subinfo where pid=%d and tag="%s"', $id, $tag);
			}
			$eqdb->dbe($sql);
		}
		$eqdb->dbe('delete from reginfo where email="'.$email.'" and token="'.$token.'"');
		echo '<p>Thank you <b>'. $name .'</b> for subscribing eQuiz <i>'.$rd['tags'].'</i></p>';
		echo '<p>Subscription of <b>'. $email .'</b> confirmed!</p>';
	}

	public function viewAction() {
		$tag = null;
		isset($_REQUEST['tag']) and $tag = $_REQUEST['tag'];
		isset($tag) and $tag = 'id in (select pid from subinfo where tag="'.$tag.'")';
		$this->render('particip', 'view', $this->md->dbRead(null, $tag));
	}

	public function defAct() {
		$this->viewAction();
	}

	public function addAction() {
		if(isset($_POST['name'])) {
			$this->md->dbWrite($_POST);
			$this->viewAction();
		} else {
			$formdata = array('name' => array('attrib' => ' required '),
			                  'email' => array('attrib' => ' required '));
			foreach(array_keys($formdata) as $f) {
				$formdata[$f]['label'] = $this->md->fields[$f];
			}
			global $eqdb;
			$tags = array();
			foreach($eqdb->dbq_tags() as $tag) {
				$tags[$tag] = $tag;
			}
			$formdata['tag'] = array('label' => 'Subscribe To:',
			                         'choices' => $tags,
			                         'values' => array());
			if (isset($_REQUEST['tag'])) array_push($formdata['tag']['values'], $_REQUEST['tag']);
			$this->render('particip', 'add', $formdata);
		}
	}

	public function editAction() {
		isset($_REQUEST['id']) or die('id not specified');
		if(isset($_POST['name'])) {
			$this->md->dbWrite($_POST, $_REQUEST['id']);
			$this->viewAction();
		} else {
			$formdata = array('name' => array('attrib' => ' required '),
			                  'email' => array('attrib' => ' required '));
			foreach(array_keys($formdata) as $f) {
				$formdata[$f]['label'] = $this->md->fields[$f];
			}
			$values = $this->md->dbRead(array_keys($formdata), 'id='.$_REQUEST['id']);
			foreach(array_keys($formdata) as $f) {
				$formdata[$f]['value'] = $values[0][$f];
			}
			global $eqdb;
			$tags = array();
			$dbr = $eqdb->dbq_tags();
			foreach($dbr as $tag) {
				$tags[$tag] = $tag;
			}
			$dbr = $eqdb->dbq_ptags($_REQUEST['id']);
			$formdata['tag'] = array('label' => 'Subscribe To:',
			                         'choices' => $tags,
			                         'values' => $dbr);
			$this->render('particip', 'edit', array('id' => $_REQUEST['id'], 'fields' => $formdata));
		}
	}
}

?>

