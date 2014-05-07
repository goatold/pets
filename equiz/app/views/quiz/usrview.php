<?php
// css
$htmlcss =  <<<EOV
<STYLE type="text/css" >
.whitebg {
background-color: white;
font-family:  verdana,helvetica,arial;
margin-bottom: 30px;
max-width: 800px;
word-wrap: break-word;
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
font-family: "courier new";
}
.qdesc {
font-family:  verdana,helvetica,arial;
word-wrap: break-word;
max-width: 800px;
margin-bottom: 10px;
}
p.comment {
font-family: Calibri;
background-color: yellow;
}
</STYLE>
EOV;

function  genQzHtml(&$vargs, $isReview=false) {
	global $jqfile, $urlbase, $adminEmail;
	// store html into array of sections. some variable html may be inserted.
	$html = array();
	$hsec = 0;
	$html[$hsec] = '';
	if (!$isReview) {
		$html[$hsec] .= '<script type="text/javascript" src="'. $jqfile .
		         '"></script>';
	}
	$revurl = $urlbase .'/quiz/review/?id='. $vargs['quiz']['ID'];
	// logo
	$html[$hsec] .= '<img src="'. $urlbase .'/../images/equiz_logo.png">';
	// Quiz Info
	$html[$hsec] .= '<div><h2>'. $vargs['quiz']['Title'] .'</h2>';
	$html[$hsec] .= '<p>'. $vargs['quiz']['Quiz_Description'] .'</p>';
	$html[$hsec] .= '<p>submission close time: '. $vargs['quiz']['CloseTime'] .'</p>';
	$html[$hsec] .= '<p>You may come back <a href="'.$revurl.'">review answers</a> after submission closure</p>';
	$html[$hsec] .= '</div><hr>';
	// interactive resp div for usr submit quiz
	if (!$isReview) {
		$html[$hsec] .= <<<EOV
<div id="quizrslt_div" class="hide">
<div id="resp_div"></div>
<input id="backbtn" type="button" value="go back" />
<input type="button" onclick="javascript:window.opener='x';window.close();" value="close" />
</div>
EOV;
	}
	$hf = genQzFormHtml($vargs, $isReview);
	$html[$hsec] .= array_shift($hf);
	$html = array_merge($html, $hf);
	$hsec = count($html) - 1;
	$html[$hsec] .= '<hr><div>Sponsor '. $adminEmail .'</div>';
	$html[$hsec] .= '<div> <a href="'. $urlbase . '/particip/subscrb/">link to subscribe</a></div>';
	$html[$hsec] .= '<div> <a href="'. $urlbase . '/particip/unsub/">link to unsubscribe</a></div>';
	// javascript for dym effect
	if (!$isReview) {
		$html[$hsec] .= <<<EOV
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
		url: "$urlbase/quiz/submit/",
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
	}
	return $html;
}

function  genScqHtml($q, $isReview) {
	$htm_q_sc = <<<EOV
<div class="whitebg">
<li id="liq_%d" class="scq">
<label class="qdesc">
%s
</label>
%s
<ol class="q_choices">
%s
</ol>
</li>
</div>
EOV;

	$htm_sc_radio = <<<EOV
<li>
<span>
	<input id="%s" name="%s" type="radio" class="choiceRadio" value="%d" %s %s />
	<label class="choice" for="%s">%s</label>
</span>
</li>
EOV;
	$choices = "";
	$options = explode(questionModel::OP_SEP, $q['Options']);
	$da = '';
	$com = '';
	if($isReview) {
		$da = 'disabled';
		$ans = $q['Answers'];
		$com = '<p class="comment">'. $q['Comments'] .'</p>';
	}
	foreach ($options as $cid=>$cstr) {
		$id = sprintf("q%d_c%d", $q['ID'], $cid);
		$name = sprintf("q%d_a", $q['ID']);
		$chk = (isset($ans) && $cid == $ans)?'checked':'';
		$choices .= sprintf($htm_sc_radio, $id, $name, $cid,
		                    $da, $chk, $id, $cstr);
	}
	return sprintf($htm_q_sc, $q['ID'], $q['Body'], $com, $choices);
}

function  genMcqHtml($q, $isReview) {
	$htm_q_mc = <<<EOV
<!-- multiple choices question -->
<div class="whitebg">
<li id="liq_%d" class="mcq">
<label class="qdesc">
%s
</label>
%s
<ol class="q_choices">
%s
</ol>
</li>
</div>
EOV;

	$htm_mc_chkbox = <<<EOV
<li>
<span>
	<input id="%s" name="%s" type="checkbox" class="choiceChkbox" value="%d" %s %s />
	<label class="choice" for="%s">%s</label>
</span>
</li>
EOV;
	$choices = "";
	$options = explode(questionModel::OP_SEP, $q['Options']);
	$answers = array();
	$da = '';
	$com = '';
	if($isReview) {
		$da = 'disabled';
		$answers = explode(questionModel::OP_SEP,$q['Answers']);
		$com = '<p class="comment">'. $q['Comments'] .'</p>';
	}
	foreach ($options as $cid=>$cstr) {
		$id = sprintf("q%d_c%d", $q['ID'], $cid);
		$name = sprintf("q%d_a%d", $q['ID'], $cid);
		$chk = in_array($cid, $answers)?'checked':'';
		$choices .= sprintf($htm_mc_chkbox, $id, $name,
		                    $cid, $da, $chk, $id, $cstr);
	}
	return sprintf($htm_q_mc, $q['ID'], $q['Body'], $com, $choices);
}

function  genBfqHtml($q, $isReview) {
	$htm_q_bf = <<<EOV
<!-- blank-filling question -->
<div class="whitebg">
<li id="liq_%d" class="bfq">
%s
%s
</li>
</div>
EOV;

	$htm_bf_text = '<input id="%s" name="%s" type="text" ';
	$answers = array();
	$com = '';
	if($isReview) {
		$htm_bf_text .= 'readonly ';
		$answers = explode(questionModel::OP_SEP,$q['Answers']);
		$com = '<p class="comment">'. $q['Comments'] .'</p>';
	}
	$htm_bf_text .= 'value="%s" class="bfText" />';
	$sect = explode($q['Options'], $q['Body']);
	$lsec = array_pop($sect);
	$str = '';
	foreach ($sect as $bid=>$bstr) {
		$str .= $bstr;
		$v = '';
		if($isReview && isset($answers[$bid])) {
			$v = $answers[$bid];
		}
		$id = sprintf("q%d_b%d", $q['ID'], $bid);
		$name = sprintf("q%d_a%d", $q['ID'], $bid);
		$str .= sprintf($htm_bf_text, $id, $name, $v);
	}
	$str .= $lsec;
	return sprintf($htm_q_bf, $q['ID'], $str, $com);
}

function  genDtxthtml($q, $isReview) {
	return '<div class="qdesc">' . $q['Body'] . '</div>';
}

function  genPinfoHtml($token, $pid) {
	$html = '<input type="hidden" name="token" value="'. $token .'" id="token" />';
	$html .= '<input type="hidden" name="pid" value="'. $pid .'" id="pid" />';
	return $html;
}

function  genQzFormHtml(&$vargs, $isReview) {
	global $urlbase;
	$html = array();
	$hsec = 0;
	$html[$hsec] = '<div id="quiz_div">';
	if (!$isReview) {
		$html[$hsec] .= '<form id="quizfm" name="quizfm" method="post" action="'.
		                $urlbase .
		                '/quiz/submit/" class="quizForm" autocomplete="off" enctype="multipart/form-data">';
		$html[$hsec] .= '<input type="hidden" name="quiz_id" value="'. $vargs['quiz']['ID'] .'" />';
		$html[] = '';
		$hsec++;
	} else {
		$html[$hsec] .= '<form>';
	}
	// list of questions
	$html[$hsec] .= '<ol class="ol_question">';
	$qhtmlGenFuncs = array(1 => 'genScqHtml',
	                       2 => 'genMcqHtml',
	                       3 => 'genBfqHtml',
	                       4 => 'genDtxtHtml');

	foreach ($vargs['questions'] as $q) {
		if (isset($qhtmlGenFuncs[$q['Type']])) {
			$html[$hsec] .= $qhtmlGenFuncs[$q['Type']]($q, $isReview);
		}
	}
	$html[$hsec] .= '</ol>';
	// submit buttons
	if (!$isReview) {
		$html[$hsec] .= <<<EOV
<div>
<input type="reset" value="reset" />
<input type="submit" value="submit" />
</div>
EOV;
	}
	$html[$hsec] .= '</form></div>';
	return $html;
}
?>

