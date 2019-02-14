<?php
/*
=============================================
 Name      : Film Reader v1.8.3
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : https://mehmethanoglu.com.tr
 License   : MIT License
=============================================
*/

if( !defined( 'DATALIFEENGINE' ) OR !defined( 'LOGGED_IN' ) ) {
	die( "Hacking attempt!" );
}

if( $member_id['user_group'] != 1 ) {
	msg( "error", $lang['index_denied'], $lang['index_denied'] );
}

function en_serialize( $value ) { return str_replace( '"', "'", serialize( $value ) ); }
function de_serialize( $value ) { return unserialize( str_replace("'", '"', $value ) ); }

function readCovers() {
	global $db;
	$dbimages = array();
	$db->query("SELECT id, images FROM " . PREFIX . "_images");
	while ( $row = $db->get_row() ) { $dbimages[ $row['id'] ] = $row['images']; }
	$db->free();
	$directory = ROOT_DIR . "/uploads/posts/covers/";
	$images = array_diff( scandir( $directory ), array("..", ".") );
	return array( $dbimages, $images );
}

function isXfieldsOk() {
	$handle = fopen( ENGINE_DIR . "/data/xfields.txt", "r");
	$data = fread( $handle, filesize( ENGINE_DIR . "/data/xfields.txt" ) );
	fclose( $handle );
	return ( strpos( $data, "cover|" ) !== false && strpos( $data, "genre|" ) !== false && strpos( $data, "year|" ) !== false );
}

function isHtaccessOk() {
	$handle = fopen( ROOT_DIR . "/.htaccess", "r");
	$data = fread( $handle, filesize( ROOT_DIR . "/.htaccess" ) );
	fclose( $handle );
	return ( strpos( $data, "RewriteRule ^requests(/?)$ index.php?do=film-requests [L]" ) !== false );
}

function mainTable_head( $title, $right = "", $id = false ) {
	if ( $id ) {
		$id = " id=\"{$id}\"";
		$style = " style=\"display:none\"";
	} else { $style = ""; }
	echo <<< HTML
	<div class="panel panel-default">
		<div class="panel-heading"{$id}{$style}>
			<div style="float: left; margin-top: 5px;">
				{$title}
			</div>
			<div style="float: right;">
				{$right}
			</div>
			<div style="clear: both;"></div>
		</div>
		<div class="table-responsive">
			<table class="table table-striped">
HTML;
}

function mainTable_foot() {
	echo <<< HTML
			</table>
		</div>
	</div>
HTML;
}

function showRow( $title = "", $description = "", $field = "", $hide = false, $id = "" ) {
	$hide = ($hide) ? " style=\"display:none;\"" : "";
	$id = ($id != "") ? " id=\"{$id}\"" : "";
	echo "<tr{$hide}{$id}>
        <td class=\"col-xs-6 col-sm-6 col-md-7\"><h6 class=\"media-heading text-semibold\">{$title}</h6><span class=\"text-muted text-size-small hidden-xs\">{$description}</span></td>
        <td class=\"col-xs-6 col-sm-6 col-md-5\">{$field}</td>
	</tr>";
}

function showTSRow( $title = "", $conf = "" ) {
	global $mws_film, $lng_inc;

	$control = ( in_array( $check, $tclist ) ) ? "<input type=\"checkbox\" class=\"switch\" size=\"10\" name=\"save[tclist][]\" value=\"{$check}\" checked />&nbsp;" : "<input type=\"checkbox\" name=\"save[tclist][]\" value=\"{$check}\" />&nbsp;";
	$clist = de_serialize( $mws_film['tags'] ) ;
	$tlist = de_serialize( $mws_film['tlink'] ) ;
	$field_type = field_type( $title, $checked = $clist[$title] );
	$field_alt = makeButton( "save[tlink][{$title}]", $tlist[$title] );
	echo "
	<tr>
        <td class=\"col-xs-4 col-sm-4 col-md-4\"><b>{$title}</b> {$lng_inc['132']}</td>
        <td class=\"col-xs-6 col-sm-6 col-md-6\">{$field_type}</td>
        <td class=\"col-xs-2 col-sm-2 col-md-2\" valign=\"middle\">{$lng_inc['133']} &nbsp; {$field_alt}</td>
	</tr>";
}

function showXFRow($title = "", $description = "", $field = "", $check = "", $tcheck = "") {
	global $mws_film;
	if ( $check != "" ) {
		$clist = de_serialize( $mws_film['clist'] );
		$control = ( in_array( $check, $clist ) ) ? "<input class=\"switch\" type=\"checkbox\" name=\"save[clist][]\" value=\"{$check}\" checked=\"checked\" />&nbsp;" : "<input class=\"switch\" type=\"checkbox\" name=\"save[clist][]\" value=\"{$check}\" />&nbsp;";
		$tclist = de_serialize($mws_film['tclist'] );
		$tcontrol = ( in_array( $check, $tclist ) ) ? "<input class=\"switch\" type=\"checkbox\" name=\"save[tclist][]\" value=\"{$check}\" checked=\"checked\" />&nbsp;" : "<input class=\"switch\" type=\"checkbox\" name=\"save[tclist][]\" value=\"{$check}\" />&nbsp;";
	} else {
		$control = "";
		$tcontrol = "";
	}
	echo "
	<tr>
		<td>{$control} <b style=\"font-size: 13px;\">{$title}</b><br /><br /><span class=\"text-muted text-size-small\">{$description}</span></td>
		<td width=\"400\" align=\"middle\">{$field}</td>
		<td width=\"10\" valign=\"middle\">{$tcontrol}</td>
	</tr>";
}

function showpluginRow( $title = "", $description = "", $field = "" ) {
	echo "<tr>
		<td colspan=\"5\"><h6 class=\"media-heading text-semibold\">{$title}</h6><span class=\"note large\">{$description}</span></td>
		<td colspan=\"2\" style=\"width: 60px\">{$field}</td>
	</tr>";
}

function pluginRow( $info ) {
	global $lng_inc, $mws_film;
	$hash = "p_" . md5( $info[0] . $info[1] . $info[2] );
	$checked = ( array_key_exists( $hash, $mws_film ) && ! empty( $mws_film[$hash] ) ) ? " checked=\"checked\"" : "";
	echo "
	<tr>
		<td><b>{$info[0]}</b></td>
		<td><b>{$info[1]}</b></td>
		<td>{$info[2]}</td>
		<td>{$info[3]}</td>
		<td>{$info[5]}</td>
		<td><a href=\"http://{$info[4]}\" target=\"_blank\">{$info[4]}</a></td>
		<td><input class=\"switch\" type=\"checkbox\"{$checked} name=\"save[{$hash}]\" id=\"save[{$hash}]\" value=\"1\" /></td>
	</tr>";
}


function openTab( $id, $active = false ) {
	$active = ( $active ) ? " active" : "";
	echo <<<HTML
<div class="tab-pane{$active}" id="tab{$id}" >
	<table class="table table-normal table-striped" style="background: #fff">
HTML;
}

function closeTab() {
	echo <<<HTML
	</table>
</div>
HTML;
}

function makeDropDown( $options, $name, $selected, $copy = false ) {
	$output = "<select class=\"uniform\" type=\"settings\" style=\"min-width:100px;\" name=\"{$name}\" id=\"{$name}\">\r\n";
	foreach ( $options as $value => $description ) {
		$output .= "<option value=\"{$value}\"";
		if ( $selected == $value ) {
			$output .= " selected ";
			$tname = $value;
		}
		$output .= ">{$description}</option>\n";
	}
	if ( $copy && ( $tname != "_short_" AND $tname != "_full_" AND $tname != "_title_" || $value != "_tags_" ) ) {
		$output .= "</select>" . "&nbsp;&nbsp;<input type=\"button\" onclick=\"window.clipboardData.setData('Text', '[xfvalue_".$tname."]');\" class=\"btn btn-sm btn-default\" value=\"XF1\" />&nbsp;&nbsp;<input type=\"button\" onclick=\"window.clipboardData.setData('Text', '[xfgiven_".$tname."][xfvalue_".$tname."][/xfgiven_".$tname."]');\" class=\"btn btn-sm btn-default\" value=\"XF2\" />";
	} else $output .= "</select>";
	return $output;
	unset( $output );
}


function makeMultiSelect($options, $name, $selected, $class = '') {
	$selected = de_serialize( $selected );
	$class = ( $class != '' ) ? " {$class}" : "";
	$size = (count($options) >= 6) ? 6 : count($options);
	$output = "<select class=\"uniform{$class}\" style=\"min-width:100px;\" size=\"".$size."\" name=\"{$name}[]\" multiple=\"multiple\">\r\n";
	foreach ( $options as $value => $description ) {
		$output .= "<option value=\"{$value}\"";
		for ($x = 0; $x <= count($selected); $x++) {
			if ($value == $selected[$x]) $output .= " selected ";
		}
		$output .= ">{$description}</option>\n";
	}
	$output .= "</select>";
	return $output;
}

function makeCheckbox( $options, $name, $selected ) {
	$selected = de_serialize( $selected );
	$option_keys = array_keys( $options );
	$check1 = ( in_array( $option_keys[0], array_keys( $selected ) ) ) ? " checked=\"checked\"" : "" ;
	$check2 = ( in_array( $option_keys[1], array_keys( $selected ) ) ) ? " checked=\"checked\"" : "" ;
	return "<div class=\"icheck-select\">{$options[$option_keys[0]]} <input class=\"switch\" type=\"checkbox\"{$check1} name=\"{$name}[{$option_keys[0]}]\" value=\"1\" />&nbsp;
<input class=\"switch\" type=\"checkbox\"{$check2} name=\"{$name}[{$option_keys[1]}]\" value=\"1\" /> {$options[$option_keys[1]]}
</div>";
}


function makeButton( $name, $selected ) {
	$value = ( empty( $selected ) ) ? "0" : $selected;
	$checked = ( $selected == "1" ) ? " checked=\"checked\"" : "";
	return "<input class=\"switch\" type=\"checkbox\"{$checked} name=\"{$name}\" id=\"{$name}\" value=\"{$value}\" />";
}

function hiddenRow($name = "", $value = "") {
	echo "<input type=\"hidden\" name=\"{$name}\" value=\"{$value}\" />";
}

function getSelectValues( $options, $selected, $xf = False ) {
	foreach ( $options as $value => $description ) {
		if ( $xf && ( $value != "_short_" && $value != "_full_" && $value != "_tags_" ) ) {
			$output .= "<option value=\"{$value}\"";
			if ($selected == $value) $output .= " selected";
			$output .= ">{$description}</option>\n";
		}
	}
	return $output;
}

function readPlugins() {
	$plugins = array();
	$directory = ENGINE_DIR . "/classes/mws-film/plugins/";
	$files = array_diff( scandir( $directory ), array("..", ".", ".htaccess") );
	foreach ( $files as $file ) {
		$handle = fopen( $directory . $file , "r");
		$data = fread( $handle, filesize($directory . $file) );
		fclose( $handle );
		$plugins[ $file ] = explode( "\n", str_replace( "\r", "", $data ) );
	}
	return $plugins;
}

function showCategories( $name = "", $selected ) {
	global $lang, $cat_info, $config, $dle_login_hash, $categories;

	$categories = "<select class=\"uniform\" name=\"{$name}\" id=\"{$name}\">";
	function DisplayCategories($selected, $parentid = 0, $sublevelmarker = '') {
		global $lang, $cat_info, $config, $dle_login_hash, $categories;
		if( $parentid != 0 ) {
			$sublevelmarker .= "--";
		}
		if( count( $cat_info ) ) {
			foreach ( $cat_info as $cats ) {
				if( $cats['parentid'] == $parentid ) $root_category[] = $cats['id'];
			}
			if( count( $root_category ) ) {
				foreach ( $root_category as $id ) {
					$sel = ( $selected == $cat_info[$id]['id'] ) ? " selected" : "";
					$categories .= "<option value=\"{$cat_info[$id]['id']}\"{$sel}>{$sublevelmarker}{$cat_info[$id]['name']}</option>";
					DisplayCategories( $selected, $id, $sublevelmarker );
				}
			}
		}
	}
	DisplayCategories($selected);
	$categories .= "</select>";

	return $categories;
}


$setstyle = ( $mws_film['achange'] == "0" ) ? "#rtext {display: none;}" : "";
$setstyle .= ( $mws_film['req_notify'] == "0" ) ? "#ntext {display: none;}" : "";

$JS_CSS = <<< HTML
<style>
.chzn-choices { width: 350px !important;}
.icheck-select {
    padding: 5px 10px;
    width: 100%;
    height: 30px;
}
.icheck-select div {
    margin: -5px 5px;
}
{$setstyle}
</style>
<script type="text/javascript">
function ShowAlert( title, text ) {
	Growl.info({
		title: title,
		text: text
	});
	return false;
}

function ReadCode() {
	var code = document.getElementById("rating_code").value;
	window.clipboardData.setData('Text', code );
	ShowAlert('{$lang['p_info']}', 'OK');
}

function add_htaccess( ) {
	$.post("engine/ajax/controller.php?mod=mws-film", { add_htaccess: 'do' }, function(data) {
		window.location.reload();
	});
}

function add_xfields( ) {
	$.post("engine/ajax/controller.php?mod=mws-film", { add_xfields: 'do' }, function(data) {
		window.location.reload();
	});
}

function generate_htaccess() {
	$.post("engine/ajax/controller.php?mod=mws-film", { htaccess: 'htaccess' }, function(data) {
		$("#htaccess_code").val(data);
		$("#htaccess_code").animate({height: '200px'});
		$("#htaccess_copy").fadeIn();
	});
}

function htaccess_copy() {
	var code = $("#htaccess_code").val();
	try { window.clipboardData.setData('Text', code); }
	catch (err) {}
	$("#htaccess_copy").fadeOut();
	$("#htaccess_code").animate({height: '20px'});
	//$(".htaccess_code").fadeOut();
}
</script>
<style type="text/css" media="all">
{$setstyle}
</style>
HTML;

// ---------------- COMPLETED ---- END -----------------

function msgbox($title) {
	global $lang, $MNAME;
	mainTable_head($title);
	echo <<< HTML
	<table width="100%">
		<tr>
			<td align="center">
				{$lang['opt_sysok_1']}<br /><br /><a class="btn btn-sm btn-primary" href="{$PHP_SELF}?mod={$MNAME}">{$lang['db_prev']}</a>
			</td>
		</tr>
	</table>
HTML;
	mainTable_foot();
}


function field_type( $name, $checked = "" ) {
	global $lng_inc, $mws_film;
	$c1 = ( $checked == "xfield" ) ? " selected" : "";
	$c2 = ( $checked == "tag" ) ? " selected" : "";
	$c3 = ( $checked == "custom" ) ? " selected" : "";
	$hide = ( $checked != "custom" ) ? " style=\"display: none;\"" : "";

	$tname = de_serialize( $mws_film['tnames'] );

	return <<< HTML
	<select class="uniform" style="min-width:100px;" name="save[tags][{$name}]" id="save[tags][{$name}]" data="{$name}_text" type="fieldtype">
		<option value="xfield"{$c1}>{$lng_inc['128']}</option>
		<option value="tag"{$c2}>{$lng_inc['129']}</option>
		<option value="custom"{$c3}>{$lng_inc['130']}</option>
	</select>
	&nbsp;&nbsp;
	<input type="text" style="width: 120px" class="form-control" name="save[tnames][{$name}]" id="{$name}_text"{$hide} value="{$tname[$name]}" />
HTML;
}

?>