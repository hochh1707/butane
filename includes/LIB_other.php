<?php
function mail_to($to,$sub,$body) {
	$domain=preg_replace('@^www.@','',strtolower($_SERVER["HTTP_HOST"]));
	$headers = "From: noreply@{$domain}\r\n" .
			"Reply-To: noreply@{$domain}\r\n" .
			'X-Mailer: PHP/' . phpversion();
	mail($to,$sub,$body,$headers);
}

function th($text,$class='',$other='') {
	return element('th',$text,$class,$other);
}
function td($text,$class='',$other='') {
	return element('td',$text,$class,$other);
}
function tr($text,$class='',$other='') {
	return element('tr',$text,$class,$other)."\n";
}
function element($element,$text,$class='',$other='') {
	if ($class) {
		return "<$element class=\"$class\" $other>$text</$element>";
	}
	return "<$element $other>$text</$element>";

}

function page_title($text) {
	echo "<br><h3>$text</h3><br><br>";
}

function array_dropdown($ddname, $options, $selected = '') {
	global $db;

	if ($selected == -1) {
		$first = 0;
	} else {
		$first = 1;
	}
	$ret = "<select name='$ddname' type='dropdown'>";
	foreach ($options as $option) {
		$ret .= "\n<option value='$option'";
		if ($selected == $option || $first == 0) {
			$ret .= " selected='selected'";
			$first = 1;
		}
		$ret .= ">$option</option>";
	}
	$ret .= "\n</select>";
	return $ret;
}

function array2_dropdown($ddname, $options, $selected = '') {
	global $db;
	$ret = "<select name='$ddname' type='dropdown'>";
	foreach ($options as $key => $option) {
		$ret .= "\n<option value='$key'";
		if ($selected == $key || $first == 0) {
			$ret .= " selected='selected'";
			$first = 1;
		}
		$ret .= ">$option</option>";
	}
	$ret .= "\n</select>";
	return $ret;
}

function yes_no_dropdown($ddname, $selected = 1) {
	$ret .= "<select name='$ddname' type='dropdown'>\n";
	$opt = array("1" => "Yes", "0" => "No");
	foreach($opt as $k => $v) {
		if ($k == $selected) {
			$ret .= "<option value='{$k}' selected='selected'>{$v}</option>\n";
		} else {
			$ret .= "<option value='{$k}'>{$v}</option>\n";
		}
	}
	$ret .= "</select>";
	return $ret;
}
function preg_return($regex,$haystack) {
	if (preg_match($regex,$haystack,$result)) {
		return $result[1];
	}
	return False;
}
?>