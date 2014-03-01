<?php
global $jqfile, $urlbase;
?>

<html><head>
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
<script type="text/javascript" src="<?php echo $jqfile;?>"></script>
</head>
<boday>
<h1 id="logo">
<!--equiz logo here-->
<img src="<?php echo $urlbase . '/../images/equiz_logo.png';?>">
</h1>

<div class="info">
<!--quiz title here-->
<h2><?php echo $vargs['quiz']['Title']?></h2>
<!--quiz description here-->
<div><?php echo $vargs['quiz']['Quiz_Description']?></div>
<!--quiz duetime here-->
<div>submission will be closed by: <?php echo $vargs['quiz']['CloseTime']?></div>
</div>
<hr>
<div id="quizrslt_div" class="hide">
<div id="resp_div"></div>
<input id="backbtn" type="button" value="go back" />
<input type="button" onclick="javascript:window.opener='x';window.close();" value="close" />
</div>
<div id="quiz_div">
<form id="quizfm" name="quizfm" method="post" action="<?php echo $urlbase . '/quiz/submit/'?>" class="quizForm" autocomplete="off" enctype="multipart/form-data">
<input type="hidden" name="quiz_id" value="<?php echo $vargs['quiz']['ID']?>" />
<input type="hidden" name="token" value="<?php echo $vargs['token']?>" id="token" />
<input type="hidden" name="pid" value="<?php echo $vargs['pid']?>" id="pid" />
<ol class="ol_question">

<?php
$questions = $vargs['questions'];
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

$htm_sc_radio = <<<EOV
<li>
<span>
	<input id="%s" name="%s" type="radio" class="choiceRadio" value="%d" />
	<label class="choice" for="%s">%s</label>
</span>
</li>
EOV;

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

$htm_mc_chkbox = <<<EOV
<li>
<span>
	<input id="%s" name="%s" type="checkbox" class="choiceChkbox" value="%d" />
	<label class="choice" for="%s">%s</label>

</span>
</li>
EOV;

$htm_q_bf = <<<EOV
<!-- blank-filling question -->
<div class="whitebg">

<li id="liq_%d" class="bfq">
%s
</li>
</div>
EOV;

$htm_bf_text = <<<EOV
<input id="%s" name="%s" type="text" class="bfText" />
EOV;

foreach ($questions as $q) {
	if ($q['Type'] == 1) {
		$choices = "";
		$options = explode(questionModel::OP_SEP, $q['Options']);
		foreach ($options as $cid=>$cstr) {
			$id = sprintf("q%d_c%d", $q['ID'], $cid);
			$name = sprintf("q%d_a", $q['ID']);
			$choices .= sprintf($htm_sc_radio, $id, $name, $cid, $id, $cstr);
		}
		echo sprintf($htm_q_sc, $q['ID'], $q['Body'], $choices);
	} elseif ($q['Type'] == 2) {
		$choices = "";
		$options = explode(questionModel::OP_SEP, $q['Options']);
		foreach ($options as $cid=>$cstr) {
			$id = sprintf("q%d_c%d", $q['ID'], $cid);
			$name = sprintf("q%d_a%d", $q['ID'], $cid);
			$choices .= sprintf($htm_mc_chkbox, $id, $name, $cid, $id, $cstr);
		}
		echo sprintf($htm_q_mc, $q['ID'], $q['Body'], $choices);
	} elseif ($q['Type'] == 3) {
		$sect = explode($q['Options'], $q['Body']);
		$lsec = array_pop($sect);
		$str = '';
		foreach ($sect as $bid=>$bstr) {
			$str .= $bstr;
			$v = null;
			$id = sprintf("q%d_b%d", $q['ID'], $bid);
			$name = sprintf("q%d_a%d", $q['ID'], $bid);
			$str .= sprintf($htm_bf_text, $id, $name);
		}
		$str .= $lsec;
		echo sprintf($htm_q_bf, $q['ID'], $str);
	}
}
?>

</ol>
<div>
<input type="reset" value="reset" />
<input type="submit" value="submit" />
</div>
</form>
</div>
<hr>
<div class="info">
Computer generated form.
</div>

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
		url: "<?php echo $urlbase . '/quiz/submit/'?>",
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

</body></html>

