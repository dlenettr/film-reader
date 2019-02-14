<?php
/*
=============================================
 Name      : Film Reader v1.8.3
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : https://mehmethanoglu.com.tr
 License   : MIT License
=============================================
*/

require_once ENGINE_DIR . "/data/config.php";
require_once ENGINE_DIR . "/data/mws-film.conf.php";
include_once ROOT_DIR . "/language/" . $config['langs'] . "/mws-film.lng";
unset( $lng_inc );

$imethod = ".val"; // .text for WYSIWYG

// site:addnews
if ( $dle_module == "addnews" ) {

	$dimdb = "\n";
	$wysiwyg = $config['allow_admin_wysiwyg'];
	$tclist = unserialize( str_replace("'", '"', $mws_film['tclist'] ) );
	foreach($mws_film as $key => $value) {
		if ( strpos($key, "film_") !== false && ! empty($value) ) {
			$kid = explode("_", $key);
			if ( in_array( $kid[1], $tclist ) ) $dimdb .= "\t\t\t\tif ( \$(\"#tags\").val() == '' ) { \$(\"#tags\").val(data.{$kid[1]}); } else { var tag = \$(\"#tags\").val(); \$(\"#tags\").val(tag + ', ' + data.{$kid[1]}); }\n";
			if ( $value == "_tags_" ) $dimdb .= "\t\t\t\tif ( \$(\"#tags\").val() == '' ) { \$(\"#tags\").val(data.{$kid[1]}); } else { var tag = \$(\"#tags\").val(); \$(\"#tags\").val(tag + ', ' + data.{$kid[1]}); }\n";
			if ( $mws_film['overwrite'] ) {
				if ( $kid[1] == "story") {
					if ( $value == "_short_" ) {
						$dimdb .= ( $wysiwyg == "1" ) ? "\t\t\t\t\$(\"textarea[name='short_story']\").html(data.{$kid[1]});\n" : "\t\t\t\t\$(\"#short_story\").val(data.{$kid[1]});\n";
					} else if ( $value == "_full_" ) {
						$dimdb .= "\t\t\t\t\$(\"#full_story\").val(data.{$kid[1]});\n";
					} else {
						$dimdb .= "\t\t\t\t\$(\"input[id='xf_{$value}-tokenfield']\").val(data.{$kid[1]});\n";
						$dimdb .= "\t\t\t\t\$(\"input[id='xf_{$value}']\").val(data.{$kid[1]});\n";
					}
					// TinyMCE
					if ( $wysiwyg == "2" ) $dimdb .= "\t\t\t\ttinymce.activeEditor.execCommand('mceInsertContent', false, data.{$kid[1]});\n";
				}
				else if ( ( $kid[1] == "name" || $kid[1] == "namelocal" ) && $value == "_title_") $dimdb .= "\t\t\t\t\$(\"input[name='title']\").val(data.{$kid[1]});\n";
				else if ( $kid[1] == "screens") { $dimdb .= "\t\t\t\t\$(\"textarea[id='xf_{$value}']\"){$imethod}(data.{$kid[1]});\n";}
				else {
					$dimdb .= "\t\t\t\t\$(\"input[id='xf_{$value}-tokenfield']\").val(data.{$kid[1]});\n";
					$dimdb .= "\t\t\t\t\$(\"input[id='xf_{$value}']\").val(data.{$kid[1]});\n";
				}
			} else {
				if ( $kid[1] == "story") {
					if ( $value == "_short_" ) {
						$dimdb .= "\t\t\t\t\tif ( \$(\"#short_story\").val() == '' ) \$(\"#short_story\").val(data.{$kid[1]});\n";
					} else if ( $value == "_full_" ) {
						$dimdb .= "\t\t\t\t\tif ( \$(\"#full_story\").val() == '' ) \$(\"#full_story\").val(data.{$kid[1]});\n";
					} else {
						$dimdb .= "\t\t\t\t\$(\"textarea[id='xf_{$value}']\"){$imethod}(data.{$kid[1]});\n";
					}
					// TinyMCE
					if ( $wysiwyg == "2" ) $dimdb .= "\t\t\t\ttinymce.activeEditor.execCommand('mceInsertContent', false, data.{$kid[1]});\n";
				}
				else if ( $value == "_tags_" ) $dimdb .= "\t\t\t\t\tif ( \$(\"#tags\").val() == '' ) { \$(\"#tags\").val(data.{$kid[1]});\n\t\t\t\t\t} else { var tag = \$(\"#tags\").val(); \$(\"#tags\").val(tag + ', ' + data.{$kid[1]}); }\n";
				else if ( $kid[1] == "screens") { $dimdb .= "\t\t\t\t\$(\"textarea[id='xf_{$value}']\"){$imethod}(data.{$kid[1]});\n";}
				else if ( ( $kid[1] == "name" || $kid[1] == "namelocal" ) && $value == "_title_") $dimdb .= "\t\t\t\t\tif ( \$(\"input[name='title']\").val() == '' ) \$(\"input[name='title']\").val(data.{$kid[1]});\n";
				else {
					$dimdb .= "\t\t\t\t\tif ( \$(\"input[id='xf_{$value}-tokenfield']\").val() == '' ) \$(\"input[id='xf_{$value}']\").val(data.{$kid[1]});\n";
					$dimdb .= "\t\t\t\t\tif ( \$(\"input[id='xf_{$value}']\").val() == '' ) \$(\"input[id='xf_{$value}']\").val(data.{$kid[1]});\n";
				}
			}
		}
	}

	$add_tag = <<< HTML
	<link rel="stylesheet" href="engine/skins/images/mws-film/styles.css" type="text/css" />
	<script type="text/javascript" src="engine/skins/images/mws-film/jscripts.js"></script>
	<script type="text/javascript">
		function FilmRead( ) {
			var film_url = $("#film_url").val();
			if ( film_url != "" ) {
				NProgress.start();
				$.post("engine/ajax/controller.php?mod=mws-film", { film_url: film_url }, function(data) {
					if (data.result == 'ok') {
						{$dimdb}
						// yeni xfield sistemine uygun upload
						if ( $("div#xfupload_{$mws_film['film_img']}").length > 0 ) {
							if ( $("#uploadedfile_{$mws_film['film_img']}").length > 0 ) $("#uploadedfile_{$mws_film['film_img']}").remove();
							uploaded_html = '<div id="uploadedfile_{$mws_film['film_img']}">' +
									'<div class="uploadedfile">' +
										'<div class="info">film cover</div>' +
										'<div class="uploadimage"><img style="width:auto;height:auto;max-width:100px;max-height:90px;" src="' + $("#xf_{$mws_film['film_img']}").val() + '"></div>' +
										'<div class="info"><a href="#" onclick="xfimagedelete(\'{$mws_film['film_img']}\',\'' + $("#xf_{$mws_film['film_img']}").val().replace("{$config['http_home_url']}uploads/posts/", "") + '\');return false;">{$lang['xfield_xfid']}</a></div>' +
									'</div>' +
								'</div>';
							$("#xf_{$mws_film['film_img']}").val( $("#xf_{$mws_film['film_img']}").val().replace("{$config['http_home_url']}uploads/posts/", "") );
							$("div#xfupload_{$mws_film['film_img']} > div.qq-uploader").prepend( uploaded_html );
						}
					} else {
						DLEalert(data.error, '{$lng_site['error']}');
					}
				},'json').done(function() {
					NProgress.done();
				});
			}
			return false;
		}
		function ScreenRead( ) {
			var page_url = $("#page_url").val();
			if ( page_url != "" ) {
				NProgress.start();
				$.post("engine/ajax/controller.php?mod=mws-film", { page_url: page_url }, function(data) {
					if (data.result == 'ok') { var txt = $("textarea[id='xf_{$mws_film['film_screens']}']"); txt.val( txt.val() + "\\n" + data.html); $("#page_url").val(""); }
					else { DLEalert(data.error, '{$lng_site['error']}'); }
				},'json').done(function() { NProgress.done(); });
			}
			return false;
		}
	</script>
HTML;
	if ( $mws_film['read_trailer'] && ! empty( $mws_film['api_key'] ) ) {
		$tpl_replace = "\\1";
		$index = ( $mws_film['film_name'] == "_title_" ) ? $mws_film['film_namelocal'] : $mws_film['film_name'];
		$trailer = $mws_film['film_trailer'];
		$mtrailer = $mws_film['film_trailer_mobil'];
		$add_tag .= <<< HTML

		<style>
		#trailer_table {margin: 5px 0;clear: both; }
		#trailer_table ul {list-style: none;width: 760px;height:390px;}
		#trailer_table ul li {float:left;border: 1px solid #ccc; background: #eee;border-radius: 5px;margin: 5px;padding:3px;text-align: center;width: 170px;}
		#trailer_table ul li:hover{background-color: #eee;cursor: pointer;border: 1px solid #dedede;}
		</style>
		<script type="text/javascript">
			function TrailerTableClose( ) {
				$('#trailer_table').animate({height: '0'}, 500, function() {
					$('#trailer_table').hide();
					$('#trailer_close').hide();
				});
				return false;
			}
			function TrailerRead( ) {
				var title = $("input[id='title']").val();
				var title2 = $("input[id='xf_{$index}']").val();
				if ( title != "" || title2 != "" ) {
					NProgress.start();
					$.post("engine/ajax/controller.php?mod=mws-film", { film_name: title, film_name2: title2 }, function(data) {
						if (data.result == 'ok') {
							$('#trailer_close').fadeIn();
							$("#trailer_table").show().animate({height: '390'});
							$("#trailer_table").html(data.html);
							$("#trailer_table li").click(function() {
								TrailerTableClose( );
								$("input[id='xf_{$trailer}']").val( $(this).attr('link') );
								$("input[id='xf_{$mtrailer}']").val( $(this).attr('mlink') );
							});
						} else {
							DLEalert(data.error, '{$lng_site['error']}');
						}
					},'json').done(function() {
						NProgress.done();
					});
				}
				return false;
			}
		</script>
HTML;
	} else {
		$tpl_replace = "";
	}
	$tpl->set_block( "'\\[film-trailer\\](.*?)\\[/film-trailer\\]'si", $tpl_replace );
}

// admin:addnews
else if ( $_GET['mod'] == "addnews" && $_GET['action'] == "addnews" ) {
	$dimdb = "\n";
	$wysiwyg = $config['allow_admin_wysiwyg'];
	$tclist = unserialize( str_replace("'", '"', $mws_film['tclist'] ) );

	foreach($mws_film as $key => $value) {
		if ( strpos($key, "film_") !== false && ! empty($value) ) {
			$kid = explode("_", $key);
			if ( in_array( $kid[1], $tclist ) ) $dimdb .= "\t\t\t\tif ( \$(\"#tags-tokenfield\").val() == '' ) { \$(\"#tags-tokenfield\").val(data.{$kid[1]}).blur(); } else { var tag = \$(\"#tags-tokenfield\").val(); \$(\"#tags-tokenfield\").val(tag + ', ' + data.{$kid[1]}).blur(); }\n";
			if ( $value == "_tags_" ) $dimdb .= "\t\t\t\tif ( \$(\"#tags-tokenfield\").val() == '' ) { \$(\"#tags-tokenfield\").val(data.{$kid[1]}).blur(); } else { var tag = \$(\"#tags-tokenfield\").val(); \$(\"#tags-tokenfield\").val(tag + ', ' + data.{$kid[1]}).blur(); }\n";
			if ( $mws_film['overwrite'] ) {
				if ( $kid[1] == "story") {
					if ( $value == "_short_" ) {
						$dimdb .= ( $wysiwyg == "1" ) ? "\t\t\t\t\$(\"textarea[name='short_story']\").html(data.{$kid[1]});\n" : "\t\t\t\t\$(\"#short_story\").val(data.{$kid[1]});\n";
					} else if ( $value == "_full_" ) {
						$dimdb .= "\t\t\t\t\$(\"#full_story\").val(data.{$kid[1]});\n";
					} else {
						$dimdb .= "\t\t\t\t\$(\"input[id='xf_{$value}-tokenfield']\").val(data.{$kid[1]});\n";
						$dimdb .= "\t\t\t\t\$(\"input[id='xf_{$value}']\").val(data.{$kid[1]});\n";
					}
					// TinyMCE
					if ( $wysiwyg == "2" ) $dimdb .= "\t\t\t\ttinymce.activeEditor.execCommand('mceInsertContent', false, data.{$kid[1]});\n";
				}
				else if ( ( $kid[1] == "name" || $kid[1] == "namelocal" ) && $value == "_title_") $dimdb .= "\t\t\t\t\$(\"input[name='title']\").val(data.{$kid[1]});\n";
				else if ( $kid[1] == "screens") { $dimdb .= "\t\t\t\t\$(\"textarea[id='xf_{$value}']\"){$imethod}(data.{$kid[1]});\n";}
				else {
					$dimdb .= "\t\t\t\t\$(\"input[id='xf_{$value}-tokenfield']\").val(data.{$kid[1]});\n";
					$dimdb .= "\t\t\t\t\$(\"input[id='xf_{$value}']\").val(data.{$kid[1]});\n";
				}
			} else {
				if ( $kid[1] == "story") {
					if ( $value == "_short_" ) {
						$dimdb .= "\t\t\t\t\tif ( \$(\"#short_story\").val() == '' ) \$(\"#short_story\").val(data.{$kid[1]});\n";
					} else if ( $value == "_full_" ) {
						$dimdb .= "\t\t\t\t\tif ( \$(\"#full_story\").val() == '' ) \$(\"#full_story\").val(data.{$kid[1]});\n";
					} else {
						$dimdb .= "\t\t\t\t\$(\"textarea[id='xf_{$value}']\"){$imethod}(data.{$kid[1]});\n";
					}
					// TinyMCE
					if ( $wysiwyg == "2" ) $dimdb .= "\t\t\t\ttinymce.activeEditor.execCommand('mceInsertContent', false, data.{$kid[1]});\n";
				}
				else if ( $value == "_tags_" ) $dimdb .= "\t\t\t\t\tif ( \$(\"#tags-tokenfield\").val() == '' ) { \$(\"#tags-tokenfield\").val(data.{$kid[1]}); } else { var tag = \$(\"#tags-tokenfield\").val(); \$(\"#tags-tokenfield\").val(tag + ', ' + data.{$kid[1]}); }\n";
				else if ( $kid[1] == "screens") { $dimdb .= "\t\t\t\t\$(\"textarea[id='xf_{$value}']\"){$imethod}(data.{$kid[1]});\n";}
				else if ( ( $kid[1] == "name" || $kid[1] == "namelocal" ) && $value == "_title_") $dimdb .= "\t\t\t\t\tif ( \$(\"input[name='title']\").val() == '' ) \$(\"input[name='title']\").val(data.{$kid[1]});\n";
				else {
					$dimdb .= "\t\t\t\t\tif ( \$(\"input[id='xf_{$value}-tokenfield']\").val() == '' ) \$(\"input[id='xf_{$value}-tokenfield']\").val(data.{$kid[1]});\n";
					$dimdb .= "\t\t\t\t\tif ( \$(\"input[id='xf_{$value}']\").val() == '' ) \$(\"input[id='xf_{$value}']\").val(data.{$kid[1]});\n";
				}
			}
		}

	}

	echo <<< HTML
	<link rel="stylesheet" href="engine/skins/images/mws-film/styles.css" type="text/css" />
	<script type="text/javascript" src="engine/skins/images/mws-film/jscripts.js"></script>
	<script type="text/javascript">
		function FilmRead( ) {
			var film_url = $("#film_url").val();
			if ( film_url != "" ) {
				NProgress.start();
				$.post("engine/ajax/controller.php?mod=mws-film", { film_url: film_url }, function(data) {
					console.log( data );
					if ( data.result == 'ok' ) {
						{$dimdb}

						// IMDB için resim alanını göster
						if ( film_url.indexOf('imdb.com') !== -1 ) {
							$("#imdbScr").fadeIn();
						}
						// Ekran görüntülerini ekle
						screens = $("*[name='xfield[screens]']");
						if ( screens.length == 1 && screens.val() != '' ) {
							// Ekran görüntülerini satırlara böl
							data = screens.val().replace(/__EOL__/g, "\\n" );
							screens.val( data );
						}
						// Plugin alanlarını direkt kullan
						$.each( data, function( key, value ) {
							if ( key != 'result' && key != 'error' ) {
								field = $("*[name='xfield[" + key + "]']");
								if ( field.val() == '' ) {
									field.val( value.replace(/NEWLINE/g, '\\n') );
								}
								if ( key == "_title_" ) $("input[id='title']").val(value);
								else if ( key == "_short_story_" ) $("textarea[id='short_story']").val(value);
								else if ( key == "_full_story_" ) $("textarea[id='full_story']").val(value);
								console.log( key + " : " + value );
							}
						});
						// yeni xfield sistemine uygun upload
						if ( $("div#xfupload_{$mws_film['film_img']}").length > 0 ) {
							if ( $("#uploadedfile_{$mws_film['film_img']}").length > 0 ) $("#uploadedfile_{$mws_film['film_img']}").remove();
							uploaded_html = '<div id="uploadedfile_{$mws_film['film_img']}">' +
									'<div class="uploadedfile">' +
										'<div class="info">film cover</div>' +
										'<div class="uploadimage"><img style="width:auto;height:auto;max-width:100px;max-height:90px;" src="' + $("#xf_{$mws_film['film_img']}").val() + '"></div>' +
										'<div class="info"><a href="#" onclick="xfimagedelete(\'{$mws_film['film_img']}\',\'' + $("#xf_{$mws_film['film_img']}").val().replace("{$config['http_home_url']}uploads/posts/", "") + '\');return false;">{$lang['xfield_xfid']}</a></div>' +
									'</div>' +
								'</div>';
							$("#xf_{$mws_film['film_img']}").val( $("#xf_{$mws_film['film_img']}").val().replace("{$config['http_home_url']}uploads/posts/", "") );
							$("div#xfupload_{$mws_film['film_img']} > div.qq-uploader").prepend( uploaded_html );
						}
					} else {
						DLEalert(data.error, '{$lng_site['error']}');
					}
				},'json').done(function() {
					NProgress.done();
				});
			}
			return false;
		}
		function ScreenRead( ) {
			var page_url = $("#page_url").val();
			if ( page_url != "" ) {
				NProgress.start();
				$.post("engine/ajax/controller.php?mod=mws-film", { page_url: page_url }, function(data) {
					if (data.result == 'ok') {
						var new_data = data.html.replace(/__EOL__/g, "\\n" );
						var txt = $("textarea[id='xf_{$mws_film['film_screens']}']");
						if ( txt.val() != '' ) {
							txt.val( ( txt.val() + "\\n" + new_data ).replace( "\\n\\n", "\\n" ) );
						} else {
							txt.val(new_data);
						}
						$("#page_url").val("");
					}
					else { DLEalert(data.error, '{$lng_site['error']}'); }
				},'json').done(function() { NProgress.done(); });
			}
			return false;
		}
	</script>
HTML;
	if ( $mws_film['read_trailer'] && ! empty( $mws_film['api_key'] ) ) {
		$index = ( $mws_film['film_name'] == "_title_" ) ? $mws_film['film_namelocal'] : $mws_film['film_name'];
		$trailer = $mws_film['film_trailer'];
		$mtrailer = $mws_film['film_trailer_mobil'];
		echo <<< HTML
		<style>
		#trailer_table {margin: 5px;clear: both;border: 1px solid #fff;border-bottom: 1px solid #dedede;}
		#trailer_table ul {list-style: none;width: 1000px;height:280px;}
		#trailer_table ul li {float:left;border: 1px solid #dedede;margin: 5px;padding:3px;text-align: center;width: 180px;}
		#trailer_table ul li:hover{background-color: #eee;cursor: pointer;border-radius: 5px;border: 1px solid #dedede;}
		</style>
		<script type="text/javascript">
			function TrailerTableClose( ) {
				$('#trailer_table').animate({height: '0'}, 500, function() {
					$('#trailer_table').hide();
					$('#trailer_close').hide();
				});
				return false;
			}
			function TrailerRead( ) {
				var title = $("input[id='title']").val();
				var title2 = $("input[id='xf_{$index}']").val();
				if ( title != "" || title2 != "" ) {
					NProgress.start();
					$.post("engine/ajax/controller.php?mod=mws-film", { film_name: title, film_name2: title2 }, function(data) {
						console.log( data );
						if (data.result == 'ok') {
							$('#trailer_close').fadeIn();
							$("#trailer_table").show().animate({height: '300'});
							$("#trailer_table").html(data.html);
							$("#trailer_table li").click(function() {
								TrailerTableClose( );
								$("input[id='xf_{$trailer}']").val( $(this).attr('link') );
								$("input[id='xf_{$mtrailer}']").val( $(this).attr('mlink') );
							});
						} else {
							code = data['_response']['error']['code'];
							message = data['_response']['error']['message'];
							DLEalert(data.error + " <b>Error:</b> " + code + ": " + message, '{$lng_site['error']}');
						}
					},'json').done(function() {
						NProgress.done();
					});
				} else {
					DLEalert('{$lng_site['error_10']}', '{$lng_site['error']}');
				}
				return false;
			}
		</script>
HTML;
	}
}


// admin:editnews
else if ( $_GET['mod'] == "editnews" && $_GET['action'] == "editnews" ) {
	$dimdb = "\n";
	$tclist = unserialize( str_replace("'", '"', $mws_film['tclist'] ) );
	foreach($mws_film as $key => $value) {
		if ( strpos($key, "film_") !== false && ! empty($value) ) {
			$kid = explode("_", $key);
			if ( in_array( $kid[1], $tclist ) ) $dimdb .= "\t\t\t\tif ( \$(\"#tags-tokenfield\").val() == '' ) { \$(\"#tags-tokenfield\").val(data.{$kid[1]}).blur(); } else { var tag = \$(\"#tags-tokenfield\").val(); \$(\"#tags-tokenfield\").val(tag + ', ' + data.{$kid[1]}).blur(); }\n";
			if ( $value == "_tags_" ) $dimdb .= "\t\t\t\tif ( \$(\"#tags-tokenfield\").val() == '' ) { \$(\"#tags-tokenfield\").val(data.{$kid[1]}).blur(); } else { var tag = \$(\"#tags-tokenfield\").val(); \$(\"#tags-tokenfield\").val(tag + ', ' + data.{$kid[1]}).blur(); }\n";
			if ( $mws_film['overwrite'] ) {
				if ( $kid[1] == "story") {
					if ( $value == "_short_" ) {
						$dimdb .= "\t\t\t\t\$(\"#short_story\").val(data.{$kid[1]});\n";
					} else if ( $value == "_full_" ) {
						$dimdb .= "\t\t\t\t\$(\"#full_story\").val(data.{$kid[1]});\n";
					} else {
						$dimdb .= "\t\t\t\t\$(\"textarea[id='xf_{$value}']\"){$imethod}(data.{$kid[1]});\n";
					}
					// TinyMCE
					if ( $wysiwyg == "2" ) $dimdb .= "\t\t\t\ttinymce.activeEditor.execCommand('mceInsertContent', false, data.{$kid[1]});\n";
				}
				else if ( ( $kid[1] == "name" || $kid[1] == "namelocal" ) && $value == "_title_") $dimdb .= "\t\t\t\t\$(\"input[name='title']\").val(data.{$kid[1]});\n";
				else if ( $kid[1] == "screens") { $dimdb .= "\t\t\t\t\$(\"textarea[id='xf_{$value}']\"){$imethod}(data.{$kid[1]});\n";}
				else {
					$dimdb .= "\t\t\t\t\$(\"input[id='xf_{$value}-tokenfield']\").val(data.{$kid[1]});\n";
					$dimdb .= "\t\t\t\t\$(\"input[id='xf_{$value}']\").val(data.{$kid[1]});\n";
				}
			} else {
				if ( $kid[1] == "story") {
					if ( $value == "_short_" ) {
						$dimdb .= "\t\t\t\tif (\$(\"#short_story\").val() == '' ) \$(\"#short_story\").val(data.{$kid[1]});\n";
					} else if ( $value == "_full_" ) {
						$dimdb .= "\t\t\t\tif (\$(\"#full_story\").val() == '' ) \$(\"#full_story\").val(data.{$kid[1]});\n";
					} else {
						$dimdb .= "\t\t\t\t\$(\"textarea[id='xf_{$value}']\"){$imethod}(data.{$kid[1]});\n";
					}
					// TinyMCE
					if ( $wysiwyg == "2" ) $dimdb .= "\t\t\t\ttinymce.activeEditor.execCommand('mceInsertContent', false, data.{$kid[1]});\n";
				}
				else if ( ( $kid[1] == "name" || $kid[1] == "namelocal" ) && $value == "_title_") $dimdb .= "\t\t\t\tif (\$(\"input[name='title']\").val() == '' ) \$(\"input[name='title']\").val(data.{$kid[1]});\n";
				else if ( $kid[1] == "screens") { $dimdb .= "\t\t\t\t\$(\"textarea[id='xf_{$value}']\"){$imethod}(data.{$kid[1]});\n";}
				else {
					$dimdb .= "\t\t\t\tif ($(\"input[id='xf_{$value}-tokenfield']\").val() == '') \$(\"input[id='xf_{$value}']\").val(data.{$kid[1]});\n";
					$dimdb .= "\t\t\t\tif ($(\"input[id='xf_{$value}']\").val() == '') \$(\"input[id='xf_{$value}']\").val(data.{$kid[1]});\n";
				}
			}
			// TinyMCE
			if ( $wysiwyg == "2" ) $dimdb .= "\t\t\t\ttinymce.activeEditor.execCommand('mceInsertContent', false, data.{$kid[1]});\n";
		}
	}

	echo <<< HTML
	<link rel="stylesheet" href="engine/skins/images/mws-film/styles.css" type="text/css" />
	<script type="text/javascript" src="engine/skins/images/mws-film/jscripts.js"></script>
	<script type="text/javascript">
		function FilmRead( ) {
			var film_url = $("#film_url").val();
			if ( film_url != "" ) {
				NProgress.start();
				$.post("engine/ajax/controller.php?mod=mws-film", { film_url: film_url }, function(data) {
					if (data.result == 'ok') {
						{$dimdb}
						// IMDB için resim alanını göster
						if ( film_url.indexOf('imdb.com') !== -1 ) {
							$("#imdbScr").fadeIn();
						}
						// Ekran görüntülerini ekle
						screens = $("*[name='xfield[screens]']");
						if ( screens.length == 1 && screens.val() != '' ) {
							// Ekran görüntülerini satırlara böl
							data = screens.val().replace(/__EOL__/g, "\\n" );
							screens.val( data );
						}
						// Plugin alanlarını direkt kullan
						$.each( data, function( key, value ) {
							if ( key != 'result' && key != 'error' ) {
								field = $("*[name='xfield[" + key + "]']");
								if ( field.val() == '' ) {
									field.val( value.replace(/NEWLINE/g, '\\n') );
								}
								if ( key == "_title_" ) $("input[id='title']").val(value);
								else if ( key == "_short_story_" ) $("textarea[id='short_story']").val(value);
								else if ( key == "_full_story_" ) $("textarea[id='full_story']").val(value);
								console.log( key + " : " + value );
							}
						});
						// yeni xfield sistemine uygun upload
						if ( $("div#xfupload_{$mws_film['film_img']}").length > 0 ) {
							if ( $("#uploadedfile_{$mws_film['film_img']}").length > 0 ) $("#uploadedfile_{$mws_film['film_img']}").remove();
							uploaded_html = '<div id="uploadedfile_{$mws_film['film_img']}">' +
									'<div class="uploadedfile">' +
										'<div class="info">film cover</div>' +
										'<div class="uploadimage"><img style="width:auto;height:auto;max-width:100px;max-height:90px;" src="' + $("#xf_{$mws_film['film_img']}").val() + '"></div>' +
										'<div class="info"><a href="#" onclick="xfimagedelete(\'{$mws_film['film_img']}\',\'' + $("#xf_{$mws_film['film_img']}").val().replace("{$config['http_home_url']}uploads/posts/", "") + '\');return false;">{$lang['xfield_xfid']}</a></div>' +
									'</div>' +
								'</div>';
							$("#xf_{$mws_film['film_img']}").val( $("#xf_{$mws_film['film_img']}").val().replace("{$config['http_home_url']}uploads/posts/", "") );
							$("div#xfupload_{$mws_film['film_img']} > div.qq-uploader").prepend( uploaded_html );
						}
					} else {
						DLEalert(data.error, '{$lng_site['error']}');
					}
				},'json').done(function() {
					NProgress.done();
				});
			}
			return false;
		}
		function ScreenRead( ) {
			var page_url = $("#page_url").val();
			if ( page_url != "" ) {
				NProgress.start();
				$.post("engine/ajax/controller.php?mod=mws-film", { page_url: page_url }, function(data) {
					if (data.result == 'ok') {
						var new_data = data.html.replace(/__EOL__/g, "\\n" );
						var txt = $("textarea[id='xf_{$mws_film['film_screens']}']");
						if ( txt.val() != '' ) {
							txt.val( ( txt.val() + "\\n" + new_data ).replace( "\\n\\n", "\\n" ) );
						} else {
							txt.val(new_data);
						}
						$("#page_url").val("");
					}
				},'json').done(function() { NProgress.done(); });
			}
			return false;
		}
	</script>
HTML;
	if ( $mws_film['read_trailer'] && ! empty( $mws_film['api_key'] ) ) {
		$index = ( $mws_film['film_name'] == "_title_" ) ? $mws_film['film_namelocal'] : $mws_film['film_name'];
		$trailer = $mws_film['film_trailer'];
		$mtrailer = $mws_film['film_trailer_mobil'];
		echo <<< HTML
		<style>
		#trailer_table {margin: 5px;clear: both;border: 1px solid #fff;border-bottom: 1px solid #dedede;}
		#trailer_table ul {list-style: none;width: 1000px;height:280px;}
		#trailer_table ul li {float:left;border: 1px solid #dedede;margin: 5px;padding:3px;text-align: center;width: 180px;}
		#trailer_table ul li:hover{background-color: #eee;cursor: pointer;border-radius: 5px;border: 1px solid #dedede;}
		</style>
		<script type="text/javascript">
			function TrailerTableClose( ) {
				$('#trailer_table').animate({height: '0'}, 500, function() {
					$('#trailer_table').hide();
					$('#trailer_close').hide();
				});
				return false;
			}
			function TrailerRead( ) {
				var title = $("input[id='title']").val();
				var title2 = $("input[id='xf_{$index}']").val();
				if ( title != "" || title2 != "" ) {
					NProgress.start();
					$.post("engine/ajax/controller.php?mod=mws-film", { film_name: title, film_name2: title2 }, function(data) {
						if (data.result == 'ok') {
							$('#trailer_close').fadeIn();
							$("#trailer_table").show().animate({height: '300'});
							$("#trailer_table").html(data.html);
							$("#trailer_table li").click(function() {
								TrailerTableClose( );
								$("input[id='xf_{$value}']").val( $(this).attr('link') );
								$("input[id='xf_{$mtrailer}']").val( $(this).attr('mlink') );
							});
						} else {
							DLEalert(data.error, '{$lng_site['error']}');
						}
					},'json').done(function() {
						NProgress.done();
					});
				}
				return false;
			}
		</script>
HTML;
	}
}

else if ( $_GET['field'] == "short" && $_GET['action'] == "edit" ) { }

if ( $mws_film['read_trailer'] == "1" && ! empty( $mws_film['api_key'] ) ) {
	$film_reader_inc_addnews = '<!-- MWS Film Reader -->
	<div class="form-group">
		<label class="control-label col-md-2">' . $lng_site['film_url'] . '</label>
		<div class="col-md-10">
			<input type="text" class="form-control width-550 position-left" name="film_url" id="film_url">&nbsp;
			<button onclick="FilmRead(); return false;" class="btn btn-sm btn-success">' . $lng_site['read'] . '</button>&nbsp;
			<button onclick="TrailerRead(); return false;" class="btn btn-sm btn-primary">Trailer</button>
			<div style="display:none" id="imdbScr">
				<br />
				<input type="text" class="form-control width-550 position-left" name="page_url" id="page_url" />&nbsp;
				<button onclick="ScreenRead(); return false;" class="btn btn-sm btn-info" title="' . $lng_site['readimg_desc'] . '">' . $lng_site['readimg'] . '</button>&nbsp;
			</div>
		</div>
	</div>
	<div id="trailer_table" style="width: 98%; display: none"></div>
	<!-- MWS Film Reader -->';
} else {
	$film_reader_inc_addnews = '<!-- MWS Film Reader -->
	<div class="form-group">
		<label class="control-label col-md-2">' . $lng_site['film_url'] . '</label>
		<div class="col-md-10">
			<input type="text" class="form-control width-550 position-left" name="film_url" id="film_url">&nbsp;
			<button onclick="FilmRead(); return false;" class="btn btn-sm btn-success">' . $lng_site['read'] . '</button>&nbsp;
			<div style="display:none" id="imdbScr">
				<br />
				<input type="text" class="form-control width-550 position-left" name="page_url" id="page_url" />&nbsp;
				<button onclick="ScreenRead(); return false;" class="btn btn-sm btn-info" title="' . $lng_site['readimg_desc'] . '">' . $lng_site['readimg'] . '</button>&nbsp;
			</div>
		</div>
	</div>
	<!-- MWS Film Reader -->';
}

unset( $dimdb );

?>