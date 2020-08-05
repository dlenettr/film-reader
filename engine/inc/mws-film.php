<?php
/*
=============================================
 Name      : Film Reader v1.8.3
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : https://mehmethanoglu.com.tr
 License   : MIT License
=============================================
*/

if (!defined('DATALIFEENGINE') OR !defined('LOGGED_IN')) {
	die("Hacking attempt!");
}

$conf_file = ENGINE_DIR ."/data/mws-film.conf.php";
require_once ENGINE_DIR . "/data/config.php";
require_once ENGINE_DIR . "/classes/mysql.php";
require_once ENGINE_DIR . "/data/dbconfig.php";
require_once ($conf_file);
require_once ENGINE_DIR . "/inc/mws-film.func.php";

$MNAME = "mws-film";

include_once ROOT_DIR . "/language/" . $config['langs'] . "/mws-film.lng";
unset( $lng_site );

if ( ! is_writable( $conf_file ) ) {
	$lang['stat_system'] = str_replace("{file}", "engine/data/mws-film.conf.php", $lang['stat_system']);
	$fail .= $lang['stat_system'] . "<br />";
}

if ( $_REQUEST['action'] == "save" ) {
	if ( $member_id['user_group'] != 1 ) { msg("error", $lang['opt_denied'], $lang['opt_denied']); }
	if ( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash ) {die("Undefined user hash."); }

	$save_con = $_POST['save'];
	$save_con['downcover'] = intval($save_con['downcover']);
	$save_con['read_trailer'] = intval($save_con['read_trailer']);
	$save_con['overwrite'] = intval($save_con['overwrite']);
	$save_con['imgsize'] = intval($save_con['imgsize']);
	$save_con['screens_down'] = intval($save_con['screens_down']);
	$save_con['req_achange'] = intval($save_con['req_achange']);
	$save_con['req_same'] = intval($save_con['req_same']);
	$save_con['req_add_as'] = intval($save_con['req_add_as']);
	$save_con['req_del_post'] = intval($save_con['req_del_post']);
	$save_con['req_notify'] = intval($save_con['req_notify']);
	$save_con['req_notifyall'] = intval($save_con['req_notifyall']);
	$save_con['req_multi'] = intval($save_con['req_multi']);
	$save_con['req_multic'] = intval($save_con['req_multic']);
	$save_con['req_multi_dlimit'] = intval($save_con['req_multi_dlimit']);
	$save_con['req_multi_rlimit'] = intval($save_con['req_multi_rlimit']);

	ksort( $save_con );
	$handler = fopen( $conf_file, "w" );
	fwrite( $handler, "<?PHP \n\n//MWS Film Reader Configuration\n\n\$mws_film = array (\n\n" );

	foreach ( $save_con as $name => $value ) {
		$value = str_replace( "$", "&#036;", $value );
		$value = str_replace( chr(0), "", $value );
		$value = str_replace( chr(92), "", $value );
		$value = str_ireplace( "base64_decode", "base64_dec&#111;de", $value );
		$value = ( substr( $name, 0, 2 ) == "p_" ) ? intval( $value ) : $value;
		$value = ( is_array( $value ) ) ? en_serialize( $value ) : $db->safesql( $value );
		$name = str_replace( "{", "&#123;", $name );
		$name = str_replace( "}", "&#125;", $name );
		$name = str_replace( chr(0), "", $name );
		$name = str_replace( chr(92), "", $name );
		$name = str_replace( '(', "", $name );
		$name = str_replace( ')', "", $name );
		$name = str_ireplace( "base64_decode", "base64_dec&#111;de", $name );
		fwrite( $handler, "'{$name}' => \"{$value}\",\n\n" );
	}
	fwrite( $handler, ");\n\n?>" );
	fclose( $handler );
	clear_cache();
	msg("info", $lang['opt_sysok'], "{$lang['opt_sysok_1']}<br /><br /><a href=\"{$PHP_SELF}?mod={$MNAME}\">{$lang['db_prev']}</a>");
}


echoheader("<i class=\"fa fa-film\"></i> MWS Film Reader", $lng_inc['00'] );

$xfields = array("" => "------------", "_short_" => "* {$lng_inc['21']}", "_full_" => "* {$lng_inc['97']}", "_title_" => "* {$lng_inc['22']}", "_tags_" => "* {$lng_inc['107']}");
foreach( xfieldsload() as $xarr) {
	$xfields[$xarr[0]] = $xarr[1] . "\t(". $xarr[0] . ")";
}

echo $JS_CSS;
echo <<< HTML
<div class="row">
HTML;

if ( ! isXfieldsOk() ) {
	echo <<< HTML
	<div class="col-md-6 col-sm-12">
		<div class="alert alert-warning alert-styled-left alert-arrow-left">{$lng_inc['213']} <a href="#" onclick="add_xfields();return false">{$lng_inc['215']}</a></div>
	</div>
HTML;
}

if ( ! isHtaccessOk() ) {
	echo <<< HTML
	<div class="col-md-6 col-sm-12">
		<div class="alert alert-warning alert-styled-left alert-arrow-left">{$lng_inc['214']} <a href="#" onclick="add_htaccess();return false">{$lng_inc['215']}</a></div>
	</div>
HTML;
}

echo <<< HTML
</div>
HTML;


if ( $_REQUEST['action'] == "flush" && $_REQUEST['do'] == "delete" ) {
	list( $dbimages, $images ) = readCovers();

	$directory = ROOT_DIR . "/uploads/posts/covers/";

	foreach ( $images as $name ) {
		if ( ! in_array( "covers/" . $name, $dbimages ) ) {
			unlink( $directory . $name );
		}
	}
	unset( $dbimages, $directory, $images );

	mainTable_head($lng_inc['101'], "<input type=\"button\" class=\"btn btn-sm btn-red\" onclick=\"window.location='{$PHP_SELF}?mod={$MNAME}'\" value=\"&laquo; {$lng_inc['24']}\" />");
	echo <<< HTML
	<tr>
		<td align="center" colspan="5">{$lng_inc['104']}<br /><br /><a href="{$PHP_SELF}?mod={$MNAME}">{$lang['db_prev']}</a></td>
	</tr>
HTML;
	mainTable_foot();


} else if ( $_REQUEST['action'] == "flush" ) {

	list( $dbimages, $images ) = readCovers();

	$directory = ROOT_DIR . "/uploads/posts/covers/";
	$total = 0;
	$total_size = 0;
	$html = "";

	foreach ( $images as $name ) {

		if ( ! in_array( "covers/" . $name, $dbimages ) ) {

			$_size = filesize( $directory . $name );
			$size = formatsize( $_size );
			$time = langdate("F d Y H:i:s", filemtime( $directory . $name ) + ( intval( $config['date_adjust'] ) * 60 ) );
			++$total;
			$total_size += $_size;

			$html .= "<tr class=\"imgs\">
				  <td width=\"20%\"><a class=\"maintitle\" target=\"_blank\" href=\"{$config['http_home_url']}uploads/posts/covers/{$name}\">{$name}</a></td>
				  <td align=\"left\" width=\"10%\">&nbsp;</td>
				  <td align=\"left\" width=\"25%\">{$time}</td>
				  <td align=\"left\" width=\"41%\">/uploads/posts/covers/{$name}</td>
				  <td align=\"right\" width=\"4%\"><nobr>{$size}</nobr></td>
			  </tr>";
		}
	}
	if ( $total != 0 ) {

		mainTable_head($lng_inc['102'], "<input type=\"button\" class=\"btn btn-sm btn-black\" onclick=\"window.location='{$PHP_SELF}?mod={$MNAME}&action=flush&do=delete'\" value=\"{$lng_inc['105']}\" />&nbsp;&nbsp;<input type=\"button\" class=\"btn btn-sm btn-red\" onclick=\"window.location='{$PHP_SELF}?mod={$MNAME}'\" value=\"&laquo; {$lng_inc['24']}\" />");

		$total_size = formatsize( $total_size );
		echo <<< HTML
			{$html}
			<tr class="imgs">
				<td align="left" colspan="3">&nbsp;</td>
				<td align="right" colspan="2" width="100%">{$lng_inc['103']} : {$total} / {$total_size}</td>
			</tr>
HTML;
		mainTable_foot();

	} else {

		mainTable_head($lng_inc['102'], "<input type=\"button\" class=\"btn btn-sm btn-red\" onclick=\"window.location='{$PHP_SELF}?mod={$MNAME}'\" value=\"&laquo; {$lng_inc['24']}\" />");

		echo <<< HTML
		<table width="100%">
			  <tr class="imgs">
				  <td align="left" colspan="5" style="padding:10px">{$lng_inc['106']}</td>
			  </tr>
		</table>
HTML;
		mainTable_foot();
	}
	unset( $dbimages, $directory, $images, $total, $total_size, $html );

} else {

	echo <<< HTML
	<script type="text/javascript">
		$(document).ready(function() {
			$('[data-toggle="tab"]').on('shown.bs.tab', function(e) {
				var id;
				id = $(e.target).attr("href");
				$("select[type='settings']").change( function() {
					$.uniform.update();
					var item = $(this);
					var item_id = item.attr("id");
					var item_sl = item.val();
					if ( item_id == "save[achange]" ) {
						if ( item_sl == 1 ) {
							$("#rtext").slideDown();
						} else {
							$("#rtext").slideUp();
						}
					} else if ( item_id == "save[req_notify]" ) {
						if ( item_sl == 1 ) {
							$("#ntext").slideDown();
						} else {
							$("#ntext").slideUp();
						}
					}
				});
				$(".ibutton-container").click( function() {
					var item = $(this).find("input");
					var item_id = item.attr("id");
					var item_ch = item.is(":checked");
					if ( item_ch == true ) { item.attr("value", "1"); }
					else { item.attr("value", "0"); }
					if ( item_id == "save[downcover]" ) {
						if ( item_ch == false ) {
							$("#image1_opt").fadeOut();
							$("#image2_opt").fadeOut();
						} else {
							$("#image1_opt").fadeIn();
							$("#image2_opt").fadeIn();
						}
					} else if ( item_id == "save[read_trailer]" ) {
						if ( item_ch == false ) {
							$("#trailer_term").fadeOut();
							$("#trailer_term2").fadeOut();
						} else {
							$("#trailer_term").fadeIn();
							$("#trailer_term2").fadeIn();
						}
					}
				});
			});
			$(".notify").chosen({ allow_single_deselect: true, no_results_text: '{$lang['addnews_cat_fault']}' });
			$(".notifysel").chosen({ allow_single_deselect: true, no_results_text: '{$lang['addnews_cat_fault']}' });
			$("select[type='settings']").change( function() {
				$.uniform.update();
				var item = $(this);
				var item_id = item.attr("id");
				var item_sl = item.val();
				if ( item_id == "save[achange]" ) {
					if ( item_sl == 1 ) {
						$("#rtext").slideDown();
					} else {
						$("#rtext").slideUp();
					}
				} else if ( item_id == "save[req_notify]" ) {
					if ( item_sl == 1 ) {
						$("#ntext").slideDown();
					} else {
						$("#ntext").slideUp();
					}
				}
			});
			$(".ibutton-container").click( function() {
				var item = $(this).find("input");
				var item_id = item.attr("id");
				var item_ch = item.is(":checked");
				if ( item_ch == true ) { item.attr("value", "1"); }
				else { item.attr("value", "0"); }
				if ( item_id == "save[downcover]" ) {
					if ( item_ch == false ) {
						$("#image1_opt").fadeOut();
						$("#image2_opt").fadeOut();
					} else {
						$("#image1_opt").fadeIn();
						$("#image2_opt").fadeIn();
					}
				} else if ( item_id == "save[read_trailer]" ) {
					if ( item_ch == false ) {
						$("#trailer_term").fadeOut();
						$("#trailer_term2").fadeOut();
					} else {
						$("#trailer_term").fadeIn();
						$("#trailer_term2").fadeIn();
					}
				}
			});
		});
	</script>
	<form action="{$PHP_SELF}?mod={$MNAME}&action=save" class="systemsettings" method="post">
	<div class="panel panel-default">
		<div class="panel-heading">
			<ul class="nav nav-tabs nav-tabs-solid">
				<li class="active"><a href="#tabgeneral" data-toggle="tab"><i class="fa fa-wrench"></i> {$lng_inc['00']}</a></li>
				<li><a href="#tabplugins" data-toggle="tab"><i class="fa fa-magnet"></i> {$lng_inc['89']}</a></li>
				<li><a href="#tabxfields" data-toggle="tab"><i class="fa fa-file"></i> {$lng_inc['53']}</a></li>
				<li><a href="#tabtagsystem" data-toggle="tab"><i class="fa fa-pencil"></i> {$lng_inc['124']}</a></li>
			</ul>
		    <div class="heading-elements">
		        <ul class="icons-list">
					<li><a href="javascript:window.location='{$PHP_SELF}?mod={$MNAME}&action=flush'" title=""><i class="fa fa-trash"></i> <span>{$lng_inc['101']}</span></a></li>
					<li><a href="#" onclick="ShowAlert('{$lng_inc['68']}', '{$lng_inc['67']}'); return false;"><i class="fa fa-info"></i> {$lng_inc['69']}</a></li>
					<li><a href="#" class="panel-fullscreen"><i class="fa fa-expand"></i></a></li>
				</ul>
		    </div>
		</div>
		<div class="tab-content">
HTML;

	openTab( "general", $active = true );

	showRow(
		$lng_inc['37'],
		$lng_inc['38'],
		makeDropDown(
			array(
				"1"  => $lng_inc['37'] . " : " . $lng_inc['87'],
				"0"  => $lng_inc['37'] . " : " . $lng_inc['88']
			), "save[achange]", "{$mws_film['achange']}"
		) . "<br /><br />" .
		"<div id=\"rtext\"><textarea class=\"form-control\" style=\"width:350px;height:100px;\" name=\"save[rtext]\">{$mws_film['rtext']}</textarea>&nbsp;<input type=\"button\" class=\"btn btn-sm btn-default\" onclick=\"ShowAlert('{$lang['p_info']}', '{$lng_inc['58']}', this, event, '300px')\" value=\"?\" /></div>"
	);
	showRow(
		$lng_inc['191'],
		$lng_inc['192'],
		makeMultiSelect(array(
				"quotes" => $lng_inc['193'],
				"spaces" => $lng_inc['194'],
				"breaklines" => $lng_inc['195'],
				"hyperlinks" => $lng_inc['196'],
			),"save[clean_text]", "{$mws_film['clean_text']}", "notify"
		)
	);
	showRow(
		$lng_inc['77'],
		$lng_inc['78'],
		makeButton( "save[overwrite]", $mws_film['overwrite'] )
	);
	showRow(
		$lng_inc['59'],
		$lng_inc['60'],
		makeButton( "save[downcover]", $mws_film['downcover'] )
	);
	showRow(
		$lng_inc['121'],
		$lng_inc['122'],
		makeDropDown(array(
				"1"  => $lng_inc['120'] . $lang['opt_sys_yes'],
				"0"  => $lng_inc['120'] . $lang['opt_sys_no']
			),"save[overwriteimg]", "{$mws_film['overwriteimg']}"
		) . "<br /><br />" .
		makeDropDown(array(
				"1"  => $lng_inc['85'] . $lang['opt_sys_yes'],
				"0"  => $lng_inc['85'] . $lang['opt_sys_no']
			),"save[insertwm]", "{$mws_film['insertwm']}"
		) . "<br /><br />" .
		makeDropDown(array(
				"1"  => $lng_inc['119'] . $lang['opt_sys_yes'],
				"0"  => $lng_inc['119'] . $lang['opt_sys_no']
			),"save[resizeimg]", "{$mws_film['resizeimg']}"
		) . "<br /><br />" .
		makeDropDown(array(
				"2" => $lng_inc['116'],
				"1" => $lng_inc['117'],
				"0" => $lng_inc['118']
			),"save[resizetype]", "{$mws_film['resizetype']}"
		) . "&nbsp;&nbsp;" .
		"<input type=\"text\" class=\"form-control\" style=\"width:40px;text-align: center\" size=\"3\" name=\"save[resizesize]\" value=\"{$mws_film['resizesize']}\">&nbsp;(px)" .
		"<br /><br />" .
		makeDropDown(array(
				"1" => $lng_inc['114'],
				"0" => $lng_inc['115']
			),"save[orderw]", "{$mws_film['orderw']}"
		) . "&nbsp;&nbsp;<input type=\"button\" class=\"btn btn-sm btn-default\" onclick=\"ShowAlert('{$lang['p_info']}', '{$lng_inc['123']}');\" value=\"?\" />",
		$hide = ($mws_film['downcover'] == "0") ? True : False,
		$id = "image1_opt"
	);
	showRow(
		$lng_inc['203'],
		$lng_inc['204'],
		"<input type=\"text\" class=\"form-control\" size=\"50\" name=\"save[default_cover]\" value=\"{$mws_film['default_cover']}\">"
	);

	showRow(
		$lng_inc['79'],
		$lng_inc['80'],
		makeButton( "save[read_trailer]", $mws_film['read_trailer'] )
	);
	showRow(
		$lng_inc['83'],
		$lng_inc['84'],
		makeDropDown(array(
				"short" => $lng_inc['112'],
				"long"  => $lng_inc['113']
			),"save[trailertag]", "{$mws_film['trailertag']}"
		) . "<br /><br />" .
		"<input type=\"text\" class=\"form-control\" size=\"8\" name=\"save[trailer_term]\" value=\"{$mws_film['trailer_term']}\" />" .
		"<br /><br />{$lng_inc['81']}&nbsp;" .  "<input type=\"text\" class=\"form-control\" style=\"width: 50px; text-align: center\" size=\"3\" name=\"save[trailer_max]\" value=\"{$mws_film['trailer_max']}\">" .
		"&nbsp;&nbsp;<a href=\"#\" onclick=\"ShowAlert('{$lang['p_info']}', '{$lng_inc['82']}');return false;\"><i class=\"icon-info-sign\"></i></a>",
		$hide = ($mws_film['read_trailer'] == "0") ? True : False,
		$id = "trailer_term"
	);
	showRow(
		$lng_inc['201'],
		$lng_inc['202'],
		"<input type=\"text\" class=\"form-control\" size=\"50\" name=\"save[api_key]\" value=\"{$mws_film['api_key']}\">",
		$hide = ($mws_film['read_trailer'] == "0") ? True : False,
		$id = "trailer_term2"
	);
	showRow(
		$lng_inc['209'],
		$lng_inc['210'],
		makeDropDown(
			array(
				"0"  => $lng_inc['211'],
				"1"  => $lng_inc['212']
			), "save[rating_sep]", "{$mws_film['rating_sep']}"
		)
	);

	closeTab();


	openTab( "plugins" );
	echo <<< HTML
	<thead>
		<tr>
			<td style="width: 250px">{$lng_inc['90']}</td>
			<td style="width: 120px">{$lng_inc['91']}</td>
			<td style="width: 200px">{$lng_inc['92']}</td>
			<td style="width: 100px">{$lng_inc['93']}</td>
			<td style="width: 100px">{$lng_inc['94']}</td>
			<td style="width: 100px">{$lng_inc['95']}</td>
			<td style="width: 30px">{$lng_inc['96']}</td>
		</tr>
	</thead>

HTML;

	$plugins = readPlugins();
	if ( count( $plugins ) == 0 ) {
		echo "<tr><td colspan=\"7\" align=\"center\"><font size=\"3\" color=\"red\">{$lng_inc['200']}</font></td></tr>";
	} else {
		foreach( $plugins as $plugin ) {
			if ( count( $plugin ) > 1 ) {
				pluginRow( $plugin );
			}
		}
	}
	showpluginRow(
		$lng_inc['108'],
		$lng_inc['109'],
		makeDropDown(array(
				"1" => $lng_inc['110'],
				"0" => $lng_inc['111'],
			),"save[imgsize]", "{$mws_film['imgsize']}"
		)
	);
	showpluginRow(
		$lng_inc['175'],
		$lng_inc['176'],
		makeDropDown(array(
				"1" => $lang['opt_sys_yes'],
				"0" => $lang['opt_sys_no']
			),"save[screens_down]", "{$mws_film['screens_down']}"
		) .
		"<br /><br /><input type=\"text\" class=\"form-control\" size=\"5\" name=\"save[screens_count]\" value=\"{$mws_film['screens_count']}\">&nbsp;{$lnc_inc['177']}"
	);
	closeTab();


	openTab( "xfields" );
	echo <<< HTML
<thead>
	<tr>
		<td><a href="#" onclick="ShowAlert('{$lang['p_info']}', '{$lng_inc['135']}');return false;"><i class="icon-info-sign"></i></a>&nbsp;&nbsp;{$lng_inc['125']}</td>
		<td>{$lng_inc['126']}</td>
		<td width="10"><a href="#" onclick="ShowAlert('{$lang['p_info']}', '{$lng_inc['127']}');return false;"><i class="icon-info-sign"></i></a></td>
	</tr>
</thead>
HTML;
	showXFRow(
		$lng_inc['03'],
		$lng_inc['03'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_name]", $mws_film['film_name'], True),
		"name"
	);
	showXFRow(
		$lng_inc['04'],
		$lng_inc['04'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_year]", $mws_film['film_year'], True),
		"year"
	);
	showXFRow(
		$lng_inc['05'],
		$lng_inc['05'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_url]", $mws_film['film_url'], True),
		"url"
	);
	showXFRow(
		$lng_inc['06'],
		$lng_inc['06'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_director]", $mws_film['film_director'], True),
		"director"
	);
	showXFRow(
		$lng_inc['98'],
		$lng_inc['98'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_screenman]", $mws_film['film_screenman'], True),
		"screenman"
	);
	showXFRow(
		$lng_inc['07'],
		$lng_inc['07'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_writers]", $mws_film['film_writers'], True),
		"writers"
	);
	showXFRow(
		$lng_inc['08'],
		$lng_inc['08'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_actors]", $mws_film['film_actors'], True),
		"actors"
	);
	showXFRow(
		$lng_inc['09'],
		$lng_inc['09'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_story]", $mws_film['film_story'], True),
		"story"
	);
	showXFRow(
		$lng_inc['39'],
		$lng_inc['40'],
		makeDropDown($xfields,"save[film_ratingb]", $mws_film['film_ratingb'], True)
	);
	showXFRow(
		$lng_inc['41'],
		$lng_inc['42'],
		makeDropDown($xfields,"save[film_ratinga]", $mws_film['film_ratinga'], True)
	);
	showXFRow(
		$lng_inc['43'],
		$lng_inc['44'],
		makeDropDown($xfields,"save[film_ratingc]", $mws_film['film_ratingc'], True)
	);
	showXFRow(
		$lng_inc['12'],
		$lng_inc['12'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_genres]", $mws_film['film_genres'], True),
		"genres"
	);
	showXFRow(
		$lng_inc['13'],
		$lng_inc['13'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_country]", $mws_film['film_country'], True),
		"country"
	);
	showXFRow(
		$lng_inc['14'],
		$lng_inc['14'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_language]", $mws_film['film_language'], True),
		"language"
	);
	showXFRow(
		$lng_inc['99'],
		$lng_inc['99'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_soundtracks]", $mws_film['film_soundtracks'], True),
		"soundtracks"
	);
	showXFRow(
		$lng_inc['15'],
		$lng_inc['15'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_runtime]", $mws_film['film_runtime'], True),
		"runtime"
	);
	showXFRow(
		$lng_inc['16'],
		$lng_inc['16'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_datelocal]", $mws_film['film_datelocal'], True),
		"datelocal"
	);
	showXFRow(
		$lng_inc['17'],
		$lng_inc['17'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_aratio]", $mws_film['film_aratio'], True),
		"aratio"
	);
	showXFRow(
		$lng_inc['18'],
		$lng_inc['18'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_budget]", $mws_film['film_budget'], True),
		"budget"
	);
	showXFRow(
		$lng_inc['45'],
		$lng_inc['45'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_sound]", $mws_film['film_sound'], True),
		"sound"
	);
	showXFRow(
		$lng_inc['46'],
		$lng_inc['46'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_color]", $mws_film['film_color'], True),
		"color"
	);
	showXFRow(
		$lng_inc['47'],
		$lng_inc['47'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_img]", $mws_film['film_img'], True)
	);
	showXFRow(
		$lng_inc['48'],
		$lng_inc['48'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_tagline]", $mws_film['film_tagline'], True),
		"tagline"
	);
	showXFRow(
		$lng_inc['49'],
		$lng_inc['50'],
		makeDropDown($xfields,"save[film_locations]", $mws_film['film_locations'], True),
		"locations"
	);
	showXFRow(
		$lng_inc['51'],
		$lng_inc['51'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_author]", $mws_film['film_author'], True),
		"author"
	);
	showXFRow(
		$lng_inc['100'],
		$lng_inc['100'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_productionfirm]", $mws_film['film_productionfirm'], True),
		"productionfirm"
	);
	showXFRow(
		$lng_inc['52'],
		$lng_inc['52'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_namelocal]", $mws_film['film_namelocal'], True),
		"namelocal"
	);
	showXFRow(
		$lng_inc['174'],
		$lng_inc['174'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_screens]", $mws_film['film_screens'], True)
	);
	showXFRow(
		$lng_inc['72'],
		$lng_inc['72'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_trailer]", $mws_film['film_trailer'], True)
	);
	showXFRow(
		$lng_inc['73'],
		$lng_inc['73'].$lng_inc['19'],
		makeDropDown($xfields,"save[film_trailer_mobil]", $mws_film['film_trailer_mobil'], True)
	);
	hiddenRow("save[film_type]", "type");
	hiddenRow("save[film_seasons]", "seasons");
	hiddenRow("save[film_season_count]", "season_count");
	hiddenRow("save[film_episodes]", "episodes");
	hiddenRow("save[film_years]", "years");
	closeTab();

	openTab( "tagsystem" );
	$tlist = de_serialize( $mws_film['tclist'] );

	foreach( $tlist as $tag ) {
		showTSRow( $tag );
	}

	echo <<< HTML
	<tr class="head">
		<td colspan="1">
			<b>{$lng_inc['136']}</b>
			<br />
			<span>{$lng_inc['138']}</span>
			<br />
			<br />
			<input class="btn btn-sm btn-warning" type="button" onclick="generate_htaccess();return false;" value="{$lng_inc['137']}" />&nbsp;&nbsp;
		</td>
		<td colspan="2">
			<textarea id="htaccess_code" class="htaccess_code form-control" style="width:500px;"></textarea>
		</td>
	</tr>
HTML;
	closeTab();

	echo <<< HTML
		</div>
		<div class="panel-footer">
			<div class="pull-right">
				<input class="btn btn-success" type="submit" value="{$lang['user_save']}">
				<input type="hidden" name="user_hash" value="{$dle_login_hash}" />
			</div>
		</div>
	</div>
</form>
HTML;
}

echofooter();

?>
