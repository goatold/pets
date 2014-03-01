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
* htmltempl.php 2010-09-16 leow
*/
// html templates
$baseurl = "http://135.252.152.115/equiz";
$htm_csshdr = <<<EOV
<STYLE type="text/css">
.whitebg {
background-color: white;
}
.hlcolorbg1 {
background-color: #ddeeff;
}
.hlcolorbg2 {
background-color: #ccddee;
}
div.hide {
display: none;
}
ol.questions {
list-style-type: decimal;
}
ol.q_choices {
list-style-type: upper-alpha;
}
</STYLE>
EOV;

$htm_jQref = <<<EOV
<script type="text/javascript" src="$baseurl/jq1.4.2.min.js"></script>
EOV;

$htm_equizlogo = <<<EOV
<h1 id="logo">
<!--equiz logo here-->
<img src="$baseurl/equiz_logo.png">
</h1>
EOV;

$htmlhdr = "<html><head>".$htm_csshdr.$htm_jQref."</head><body>".$htm_equizlogo;

$htm_quizhdr = <<<EOV
<div class="info">
<!--quiz title here-->
<h2>%s</h2>
<!--quiz description here-->
<div>%s</div>
<!--quiz duetime here-->
<div>submission will be closed by: %s</div>
</div>
<hr>
<div id="quizrslt_div" class="hide">
<div id="resp_div"></div>
<input id="backbtn" type="button" value="go back" />
<input type="button" onclick="javascript:window.opener='x';window.close();" value="close" />
</div>
<div id="quiz_div">
<form id="quizfm" name="quizfm" class="quizForm" autocomplete="off" enctype="multipart/form-data" method="post" action="$baseurl/submit.php">
<input type="hidden" name="quiz_id" value="%d" />
<input type="hidden" name="token" value="%s" id="token" />
<input type="hidden" name="pid" value="%d" id="pid" />
<ol class="ol_question">
EOV;

$htm_formjs = <<<EOV
<script language="JavaScript">
$("div.whitebg").hover(function () {
	$(this).addClass("hlcolorbg1");
	}, function () {
	$(this).removeClass("hlcolorbg1");
});

function hlCurQ() {
	$("div.hlcolorbg2").removeClass("hlcolorbg2");
	$(this).parents("div.whitebg").addClass("hlcolorbg2");
}

$("input").click(hlCurQ);
$("input").change(hlCurQ);

$().ready(function(){
	$.ajaxSetup({
		error:function(x,e){
			if(x.status==0){
			var msg = 'Connect to server failed!! Please Check Your Network.';
			}else if(x.status==404){
			var msg = 'Requested URL not found.';
			}else if(x.status==500){
			var msg = 'Internel Server Error.';
			}else if(e=='parsererror'){
			var msg = 'Error. Parsing Request failed.';
			}else if(e=='timeout'){
			var msg = 'Request Time out.';
			}else {
			var msg = 'Unknow Error. '+x.responseText;
			}
			$('#resp_div').html(msg);
		}
	});
});

$('form').submit(function() {
  	$.ajax({
		url: "$baseurl/submit.php",
		global: false,
		type: "POST",
		data: $(this).serialize(),
		dataType: "html",
		async:false,
		success: function(msg){
			$('#resp_div').html(msg);
		}
	});
	$('#quiz_div').hide("slow");
	$('#quizrslt_div').show("slow");
	return false;
});

$('#backbtn').click(function(){
	$('#quiz_div').show("slow");
	$('#quizrslt_div').hide("slow");
});
</script>
EOV;

$htm_subbtn = <<<EOV
<div>
<input type="reset" value="reset" />
<input type="submit" value="submit" />
</div>
EOV;

$htm_quizend = <<<EOV
</ol>
</form>
</div>
<hr>
<div class="info">
Close note
</div>
$htm_formjs
EOV;

$htmlend = "</body></html>";

function genhtm_quiz($id, $title, $descrip, $duetime, $token, $pid, $qhtml, $sub=True) {
	global $htmlhdr, $htm_quizhdr, $htm_subbtn, $htm_quizend, $htmlend;
	$strhtml = $htmlhdr;
	$strhtml .= sprintf($htm_quizhdr, $title, $descrip, $duetime, $id, $token, $pid);
	$strhtml .= $qhtml;
	if($sub){$strhtml .= $htm_subbtn;}
	$strhtml .= $htm_quizend.$htmlend;
	return $strhtml;
}

$htm_q_sc = <<<EOV
<div class="whitebg">
<li id="liq_%d" class="scq">
<label class="qdesc">
%s
</label>
<ol class="q_choices">
%s
</ol>
</li>
</div>
EOV;

function genhtm_q_sc($id, $body, $choices) {
	global $htm_q_sc;
	return sprintf($htm_q_sc, $id, $body, $choices);
}

$htm_sc_radio = <<<EOV
<li>
<span>
	<input id="%s" name="%s" type="radio" class="choiceRadio" value="%d" />
	<label class="choice" for="%s">%s</label>
</span>
</li>
EOV;

function genhtm_sc_radio($qid, $cid, $cstr, $dis=False, $chk=False) {
	global $htm_sc_radio;
	$id = sprintf("q%d_c%d", $qid, $cid);
	$name = sprintf("q%d_a", $qid);
	$strhtml = sprintf($htm_sc_radio, $id, $name, $cid, $id, $cstr);
	if ($dis) { $strhtml = str_replace("/>", "disabled />", $strhtml); }
	if ($chk) { $strhtml = str_replace("/>", "checked />", $strhtml); }
	return $strhtml;
}

$htm_q_mc = <<<EOV
<!-- multiple choices question -->
<div class="whitebg">
<li id="liq_%d" class="mcq">
<label class="qdesc">
%s
</label>
<ol class="q_choices">
%s
</ol>
</li>
</div>
EOV;

function genhtm_q_mc($id, $body, $choices) {
	global $htm_q_mc;
	return sprintf($htm_q_mc, $id, $body, $choices);
}

$htm_mc_chkbox = <<<EOV
<li>
<span>
	<input id="%s" name="%s" type="checkbox" class="choiceChkbox" value="%d" />
	<label class="choice" for="%s">%s</label>
</span>
</li>
EOV;

function genhtm_mc_chkbox($qid, $cid, $cstr, $dis=False, $chk=False) {
	global $htm_mc_chkbox;
	$id = sprintf("q%d_c%d", $qid, $cid);
	$name = sprintf("q%d_a%d", $qid, $cid);
	$strhtml = sprintf($htm_mc_chkbox, $id, $name, $cid, $id, $cstr);
	if ($dis) { $strhtml = str_replace("/>", "disabled />", $strhtml); }
	if ($chk) { $strhtml = str_replace("/>", "checked />", $strhtml); }
	return $strhtml;
}

$htm_q_bf = <<<EOV
<!-- blank-filling question -->
<div class="whitebg">
<li id="liq_%d" class="bfq">
%s
</li>
</div>
EOV;

function genhtm_q_bf($id, $body) {
	global $htm_q_bf;
	return sprintf($htm_q_bf, $id, $body);
}

$htm_bf_text = <<<EOV
<input id="%s" name="%s" type="text" class="bfText" />
EOV;

function genhtm_bf_text($qid, $cid, $dis=False, $v=null) {
	global $htm_bf_text;
	$id = sprintf("q%d_b%d", $qid, $cid);
	$name = sprintf("q%d_a%d", $qid, $cid);
	$strhtml = sprintf($htm_bf_text, $id, $name);
	if ($dis) { $strhtml = str_replace("/>", "disabled />", $strhtml); }
	if (isset($v)) { $strhtml = str_replace("/>", "value='$v' />", $strhtml); }
	return $strhtml;
}

?>