<?php
/*
=============================================
 Name      : MWS Film Reader v1.8.1
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/
 License   : MIT License
=============================================
*/

if ( ! defined( 'DATALIFEENGINE' ) ) {
	header( "HTTP/1.1 403 Forbidden" );
	header( 'Location: ../../' );
	die( "Hacking attempt!" );
}

define( 'UPLOAD_DIR', ROOT_DIR . "/uploads/" );

include_once ENGINE_DIR . "/data/mws-film.conf.php";
include_once ROOT_DIR . "/language/" . $config['langs'] . "/mws-film.lng";
unset( $lng_inc );

// new settigs
$mws_film['req_name'] = "local";
// new settigs

function en_serialize( $value ) { return str_replace( '"', "'", serialize( $value ) ); }
function de_serialize( $value ) { return unserialize( str_replace("'", '"', $value ) ); }

function apply_change( $result ) {
	global $mws_film, $db;
	$_clist = de_serialize( $mws_film['clist'] );
		$_lines = explode( "\n", str_replace( array("\r\n","\\s"), array("\n", " "), $mws_film['rtext'] ) );
	$_cmap = array();
	foreach ( $_lines as $_line ) {
		$_temp = explode( "=>", $_line );
		$_cmap[ $_temp[0] ] = $_temp[1];
	} unset( $_temp, $_lines );
	foreach ( $result as $key => $val ) {
		if ( in_array( $key, $_clist ) ) {
			$result[ $key ] = $db->safesql( str_replace( array_keys( $_cmap ), array_values( $_cmap ), $val ) );
		}
	}
	return $result;
}

function alt_name( $text ) {
	global $db;
	$text = $db->safesql( stripslashes( $text ) );
	$text = mb_convert_case( $text, MB_CASE_LOWER, "UTF-8" );
	$map = array( 'ç' => 'c', 'ı' => 'i', 'ö' => 'o', 'ü' => 'u', 'ş' => 's', 'ğ' => 'g' );
	$map = array_merge( $map, array( "–" => "-", " " => "-", "/" => "-", "(" => "-", ")" => "-", "[" => "-", "]" => "-", "{" => "-", "}" => "-", "--" => "-" ) );
	$text = str_replace( array_keys( $map ), array_values( $map ), $text );
	return trim( $text );
}


$_clean_text = de_serialize( $mws_film['clean_text'] );

function clean_text( $txt ) {
	global $mws_film, $_clean_text;

	if ( in_array( "quotes", $_clean_text ) ) { $txt = str_replace( array( "\'", "'", '"', "\"" ), "", $txt ); }
	if ( in_array( "hyperlinks", $_clean_text ) ) { $txt = $txt; /* NOT COMPLETED */ }
	if ( in_array( "spaces", $_clean_text ) ) { $txt = str_replace( array( "\s\s", "\t" ), "", $txt ); }
	if ( in_array( "breaklines", $_clean_text ) ) { $txt = str_replace( array( "\r\n", "\r", "\n" ), "", $txt ); }
	$txt = str_replace( array("&#160;", "&nbsp;", chr( 194 ).chr( 160 )), array(""), $txt );

	return $txt;
}

function readPlugins() {
	global $mws_film;
	$plugins = array();
	$directory = ENGINE_DIR . "/classes/mws-film/plugins/";
	$files = array_diff( scandir( $directory ), array("..", ".") );
	foreach ( $files as $file ) {
		$handle = fopen( $directory . $file , "r");
		$data = fread( $handle, filesize($directory . $file) );
		fclose( $handle );
		$temp = explode("\n", str_replace( "\r", "", $data ) );
		$hash = "p_" . md5( $temp[0] . $temp[1] . $temp[2]);
		if ( $mws_film[ $hash ] ) {
			$plugins[ $hash ] = array("0" => $temp[5], "1" => $temp[6]);
		}
	}
	unset( $directory, $files, $handle, $data, $temp );
	return $plugins;
}

function save_img( $save, $output, $id_max ) {
	global $db, $_TIME, $member_id, $mws_film;
	$fp = fopen( UPLOAD_DIR . $save, "w") ; fwrite($fp, $output) ; fclose($fp); unset( $fp );
	$_save = str_replace("posts/", "", $save);
	if ( $mws_film['only_oneimg'] ) $db->query("DELETE FROM " . PREFIX . "_images WHERE images = '{$_save}'");
	$db->query("INSERT INTO " . PREFIX . "_images (images, news_id, author, date) VALUES ('{$_save}', '{$id_max}', '{$member_id['name']}', '{$_TIME}')");
	$db->free(); unset( $save, $output, $id_max );
}

function download_img( $url ) {
	global $mws_film, $db, $config, $member_id, $_TIME;

	if ( function_exists('curl_exec') ) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
		$output = curl_exec($ch);
		curl_close($ch);
	} else {
		$output = file_get_contents($url);
	}
	$url = strtok( $url, '?' );
	$save = "posts/screens/" . md5( $url ) . ".jpg";

	if ( !is_dir( UPLOAD_DIR . "posts/screens/" ) ) {
		@mkdir( UPLOAD_DIR . "posts/screens/", 0777 );
		@chmod( UPLOAD_DIR . "posts/screens/", 0777 );
	}

	$fp = fopen( UPLOAD_DIR . $save, "w") ; fwrite($fp, $output) ; fclose($fp); unset( $fp, $output );
	$id = $db->super_query("SELECT MAX(id) as max FROM " . PREFIX . "_post"); $id['max']++; $db->free();
	$id_max = $id['max'];
	$_save = str_replace("posts/", "", $save);
	$db->query("INSERT INTO " . PREFIX . "_images (images, news_id, author, date) VALUES ('{$_save}', '{$id_max}', '{$member_id['name']}', '{$_TIME}')");
	require_once ENGINE_DIR . '/classes/thumb.class.php';
	$img = new thumbnail(UPLOAD_DIR . $save);
	if ( $mws_film['resizeimg'] ) $img->scale($size = intval($mws_film['resizesize']) * 2, intval($mws_film['resizetype']));
	if ( $mws_film['insertwm'] ) $img->insert_watermark(intval( $config['max_watermark'] ));
	$img->jpeg_quality($quality = intval( $config['jpeg_quality'] ));
	$img->save(UPLOAD_DIR . $save);
	unset( $img, $id, $id_max, $_save );

	return $config['http_home_url'] . "uploads/" . $save . "__EOL__";
}

function detectUrl( $url ) {
	$plugins = readPlugins();
	$found = False;
	foreach( $plugins as $content ) {
		if ( strpos($url, $content["0"]) != false ) {
			$found = $content["1"];
			break;
		}
	}
	return $found;
	unset( $plugins );
}

function LinkCorrect( $link, $which = "" ) {
	if ( strpos( $which, "imdb" ) !== false ) {
		$film_url = parse_url( $link );
		$film_url['path'] .= ( substr( $film_url['path'], -1 ) == "/" ) ? "" : "/";
		return $film_url['scheme'] . "://" . $film_url['host'] . $film_url['path'];
	} else {
		return $link;
	}
}

function downCover( $url, $which = "" ) {
	global $db, $_TIME, $member_id, $config, $mws_film;
	$addwm = False;

	if ( filter_var($url, FILTER_VALIDATE_URL) !== False ) {
		if ( function_exists('curl_exec') ) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
			curl_setopt($ch, CURLOPT_TIMEOUT, 120);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
			$output = curl_exec($ch);
			curl_close($ch);
		} else {
			$output = file_get_contents($url);
		}

		$url = strtok( $url, '?' );

		$_ext = explode( ".", $url );
		$_ext = end( $_ext );

		if ( $_ext == "png" ) {
			if ( substr( bin2hex( $output ), 0, 8 ) != "89504e47" ) {
				if ( ! empty( $mws_film['default_cover'] ) ) {
					return $mws_film['default_cover'];
				} else {
					return $config['http_home_url'] . "templates/" . $config['skin'] . "/dleimages/no_image.jpg";
				}
			}
		} else if ( $_ext == "jpg" || $_ext == "jpeg" ) {
			if ( substr( bin2hex( $output ), 0, 20 ) != "ffd8ffe000104a464946" && substr( bin2hex( $output ), 0, 20 ) != "ffd8ffe1001845786966" ) {
				if ( ! empty( $mws_film['default_cover'] ) ) {
					return $mws_film['default_cover'];
				} else {
					return $config['http_home_url'] . "templates/" . $config['skin'] . "/dleimages/no_image.jpg";
				}
			}
		}

		if ( !is_dir( UPLOAD_DIR . "posts/covers/" ) ) {
			@mkdir( UPLOAD_DIR . "posts/covers/", 0777 );
			@chmod( UPLOAD_DIR . "posts/covers/", 0777 );
		}

		$save = "posts/covers/" . md5( $db->safesql( $url ) ) . "." . $_ext;

		if ( !empty( $output ) ) {
			$id = $db->super_query("SELECT MAX(id) as max FROM " . PREFIX . "_post"); $id['max']++; $db->free();
			if ( file_exists( UPLOAD_DIR . $save ) ) {
				if ( $mws_film['overwriteimg'] ) save_img( $save, $output, $id['max'] ); $addwm = True;
				$res = $config['http_home_url'] . "uploads/" . $save;
			} else {
				save_img( $save, $output, $id['max'] ); $addwm = True;
				$res = $config['http_home_url'] . "uploads/" . $save;
			}
		} else {
			$res = $config['http_home_url'] . "templates/" . $config['skin'] . "/dleimages/no_image.jpg";
		}
		if ( $addwm == True || ( $mws_film['insertwm'] ) || ( $mws_film['resizeimg'] ) ) {
			require_once ENGINE_DIR . '/classes/thumb.class.php';
			$img = new thumbnail(UPLOAD_DIR . $save);

			// if ( ! empty( $which ) && $which == "imdb.class.php" ) {  }

			if ( $mws_film['orderw'] ) {
				if ( $mws_film['insertwm'] ) $img->insert_watermark(intval( $config['max_watermark'] ));
				if ( $mws_film['resizeimg'] ) $img->scale($size = intval($mws_film['resizesize']), intval($mws_film['resizetype']));
			} else {
				if ( $mws_film['resizeimg'] ) $img->scale($size = intval($mws_film['resizesize']), intval($mws_film['resizetype']));
				if ( $mws_film['insertwm'] ) $img->insert_watermark(intval( $config['max_watermark'] ));
			}

			$img->jpeg_quality( $quality = intval( $config['jpeg_quality'] ) );
			$img->save(UPLOAD_DIR . $save);
			unset( $img );
		}
		unset( $output, $_ext, $save );
		return $res;
	} else {
		return "";
	}
}

function getURLContent( $url ) {
	if ( function_exists('curl_exec') ) {
		//echo "[curl]<br>";
		$ch = curl_init( $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_HEADER, false );
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0");
		curl_setopt($ch, CURLOPT_ENCODING, "utf-8" );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120 );
		curl_setopt($ch, CURLOPT_TIMEOUT, 120 );
		curl_setopt($ch, CURLOPT_MAXREDIRS, 2 );
		$output  = curl_exec( $ch );
		curl_close( $ch );
	} else {
		//echo "[fgc]<br>";
		$context = stream_context_create( array(
			'http' => array(
			'ignore_errors'=> true,
			'method'=> 'GET'
			)
		));
		$output = file_get_contents( $url, false, $context );
	}
	return $output;
}


function add_request_post( $url ) {
	global $db, $_TIME, $_IP, $member_id, $config, $mws_film, $lng_site, $debug;

	$url = $db->safesql( $url );
	$which = detectUrl( $url );

	if ( $which != False ) {

		$url = LinkCorrect( $url );

		require_once ENGINE_DIR . '/classes/mws-film/' . $which;
		$reader = new FilmReader();
		$results = $reader->get( $url );

		$_RESULT = array();
		$_ERROR = array();
		$_LIMIT = 0; // +1

		$_checked = false;

		// Kullanıcı sadece 1 istek yapabilir.
		if ( $mws_film['req_multi'] == "0" ) {
			$check1 = $db->super_query("SELECT COUNT(user_id) as c FROM " . PREFIX . "_mws_film_requests WHERE user_id = '{$member_id['user_id']}'");
			if ( isset( $check1['c'] ) && $check1['c'] > $_LIMIT ) {
				$_ERROR[] = $lng_site['error_5'];
				$_checked = true;
			}
		}

		// Kullanıcı günde sadece x istek yapabilir.
		else if ( $mws_film['req_multi'] == "1" ) {
			$_LIMIT = intval( $mws_film['req_multi_rlimit'] );
			$check1 = $db->super_query("SELECT COUNT(user_id) as c FROM " . PREFIX . "_mws_film_requests WHERE user_id = '{$member_id['user_id']}' AND FROM_UNIXTIME( date ) > ( NOW() - INTERVAL {$mws_film['req_multi_dlimit']} DAY )");
			if ( isset( $check1['c'] ) && $check1['c'] >= $_LIMIT ) {
				$_ERROR[] = $lng_site['error_5'];
				$_checked = true;
			}
		}

		if ( $_checked == false ) {
			if ( $mws_film['req_name'] == "local" ) {
				$alt_name = ( empty( $results['namelocal'] ) ) ? alt_name( $results['name'] ) : alt_name( $results['namelocal'] );
				$_RESULT['title'] = ( empty( $results['namelocal'] ) ) ? $results['name'] : $results['namelocal'];
			} else {
				$alt_name = ( empty( $results['name'] ) ) ? alt_name( $results['namelocal'] ) : alt_name( $results['name'] );
				$_RESULT['title'] = ( empty( $results['name'] ) ) ? $results['namelocal'] : $results['name'];
			}
			$alt_name = $db->safesql( $alt_name );
			$_RESULT['title'] = $db->safesql( $_RESULT['title'] );

			$_added = false;
			if ( $mws_film['req_same'] ) {
				$search_term = "|" . $results['url'] . "|";
				$check2 = $db->super_query("SELECT id, title FROM " . PREFIX . "_post WHERE approve = '1' AND xfields LIKE '%{$search_term}%' LIMIT 1");
				if ( isset( $check2['id'] ) ) {
					$_added = true;
					$_ERROR[] = str_replace( "{link}", "<a href=\"/index.php?newsid={$check2['id']}\">{$check2['title']}</a>", $lng_site['error_9'] );
				}
			}

			if ( $_added == false ) {
				$check3 = $db->super_query("SELECT * FROM " . PREFIX . "_mws_film_requests WHERE url = '{$results['url']}' LIMIT 0,1");
				if ( isset( $check3['id'] ) ) {

					$check5 = $db->super_query("SELECT * FROM " . PREFIX . "_mws_film_requestslog WHERE rid = '{$check3['id']}' AND user_id = '{$member_id['user_id']}'");
					if ( isset( $check5['user_id'] ) && $mws_film['req_multic'] == "0" ) {

						$_ERROR[] = $lng_site['error_5'];

					} else {
						$db->query("INSERT INTO " . PREFIX . "_mws_film_requestslog ( rid, user_id ) VALUES ( '{$check3['id']}', '{$member_id['user_id']}')");
						$db->query("UPDATE " . PREFIX . "_mws_film_requests SET count=count+1 WHERE url = '{$results['url']}'");
						$_RESULT['stats'] = array( "count" => $check3['count'] + 1, "added" => $check3['added'], "id" => $check3['id'] );
						if ( $check3['added'] == "1" ) $_RESULT['stats']['text'][] = str_replace( "{link}", "<a href=\"/index.php?newsid={$check3['post_id']}\">{$check3['title']}</a>", $lng_site['error_9'] );
						else $_RESULT['stats']['text'][] = $lng_site['error_6'];
					}

				} else {

					if ( ! empty( $results['img'] ) ) $results['img'] = downCover( $results['img'], $which );
					$_RESULT['img'] = $results['img'];
					$_RESULT['url'] = $results['url'];
					$_RESULT['year'] = $results['year'];
					$_RESULT['type'] = ( isset( $results['type'] ) ) ? $results['type'] : "";
					$_RESULT['stats'] = array( "count" => "1", "added" => "0" );

					if ( $mws_film['req_achange'] ) {
						$results = apply_change( $results );
					}
					$results = array_map( "clean_text", $results );

					$xfields = array();
					foreach( $mws_film as $key => $value ) {
						if ( strpos( $key, "film_" ) !== false && ! empty( $value ) ) {
							$kid = explode("_", $key);
							$kid = $kid[1];
							if ( $value == "_short_" ) $short_story = $db->safesql( $results[ $kid ] );
							else if ( $value == "_full_" ) $full_story = $db->safesql( $results[ $kid ] );
							else if ( ! empty( $results[ $kid ] ) ) {
								if ( $value == "story" ) $results[ $kid ] = stripslashes( $results[ $kid ] );
								$xfields[] = $value . "|" . $db->safesql( $results[ $kid ] );
							}
						}
					}
					$filecontents = implode( "||", $xfields );

					$tags = array();
					$tclist = de_serialize( $mws_film['tclist'] );
					foreach ( $tclist as $tag ) {
						$_tags = explode(",", $db->safesql( trim( str_replace( array("|"," "), ",", $results[ $tag ] ) ) ) );
						$tags = array_merge($tags, $_tags);
					}
					$tags = array_unique( array_filter( $tags ) );

					unset( $results, $xfields, $tclist );

					$p = array(
						"author" => $member_id['name'], "author_id" => $member_id['user_id'], "catalog_url" => "", "allow_rating" => "1", "allow_comm" => "1",
						"approve" => "0", "allow_main" => "1", "news_fixed" => "0", "allow_br" => "1",
						"category_list" => $mws_film['req_cat'], "keywords" => "", "descr" => ""
					);

					$title = ( empty( $title ) ) ? $_RESULT['title'] : $title;
					$short_story = ( ! isset( $short_story ) ) ? "" : $short_story;
					$full_story = ( ! isset( $full_story ) ) ? "" : $full_story;

					$added_time = $_TIME;
					$thistime = date( "Y-m-d H:i:s", $added_time );

					$xtags = implode(", ", $tags);
					$db->query( "INSERT INTO " . PREFIX . "_post (date, autor, short_story, full_story, xfields, title, keywords, descr, category, alt_name, allow_comm, approve, allow_main, fixed, allow_br, symbol, tags) values ('{$thistime}', '{$p['author']}', '{$short_story}', '{$full_story}', '{$filecontents}', '{$title}', '{$p['keywords']}', '{$p['descr']}', '{$p['category_list']}', '{$alt_name}', '{$p['allow_comm']}', '{$p['approve']}', '{$p['allow_main']}', '{$p['news_fixed']}', '{$p['allow_br']}', '{$p['catalog_url']}', '{$xtags}')" );

					$post_id = $db->insert_id();
					$db->query( "INSERT INTO " . PREFIX . "_post_extras (news_id, allow_rate, user_id) VALUES('{$post_id}', '{$p['allow_rating']}', '{$p['author_id']}')" );
					$db->query( "UPDATE " . PREFIX . "_users set news_num=news_num+1 where name = '{$p['author']}'" );

					foreach( $tags as $tag ) { $db->query( "INSERT INTO " . PREFIX . "_tags (news_id, tag) VALUES('{$post_id}', '{$tag}')" ); }
					$db->free();

					$db->query("INSERT INTO " . PREFIX . "_mws_film_requests (user_id, user_name, autor, post_id, url, img, title, year, count, active, ip, date, type) VALUES ('{$member_id['user_id']}', '{$member_id['name']}', '{$member_id['name']}', '{$post_id}', '{$_RESULT['url']}', '{$_RESULT['img']}', '{$_RESULT['title']}', '{$_RESULT['year']}', '1', '1', '{$_IP}', '{$_TIME}', '{$_RESULT['type']}')");
					$_RESULT['id'] = $db->insert_id();

					$db->query("INSERT INTO " . PREFIX . "_mws_film_requestslog ( rid, user_id ) VALUES ( '{$_RESULT['id']}', '{$p['author_id']}')");
					$db->free();
					unset($p, $title, $filecontents, $short_story, $full_story, $alt_name, $post_id, $added_time, $thistime, $tags, $tag);
				}
			}
		}
	} else {
		$_ERROR[] = $lng_site['error_1'];
	}
	$_RESULT['error'] = $_ERROR; unset( $_ERROR );
	return $_RESULT;
}


if ( isset( $_POST['film_url'] ) ) {

	$which = detectUrl( $db->safesql( $_POST['film_url'] ) );

	if ( $which != False ) {
		$_POST['film_url'] = LinkCorrect( $_POST['film_url'] );
		require_once ENGINE_DIR . '/classes/mws-film/' . $which;
		$reader = new FilmReader();
		$results = $reader->get( $db->safesql( $_POST['film_url'] ) );
		$story = trim( $db->safesql( $results['story'] ) );
		if ( $mws_film['downcover'] ) {
			try {
				if ( $mws_film['imgsize'] && ! empty( $results['orgimg'] ) ) {
					$results['img'] = downCover( $results['orgimg'], $which ); unset( $results['orgimg'] );
				} else {
					$results['img'] = downCover( $results['img'], $which );
				}
			} catch ( Exception $e) {
				$results['img'] = $config['http_home_url'] . "templates/" . $config['skin'] . "/dleimages/no_image.jpg";
			}
		}
		if ( $mws_film['achange'] ) {
			$results = apply_change( $results );
		}

		if ( isset( $results['screens'] ) && $mws_film['screens_down'] ) {
			$count = 0;
			foreach( $results['screens'] as $_img ) {
				if ( intval( $mws_film['screens_count'] ) == $count ) break;
				$scr .= download_img( $_img );
				$count++;
			}
			$results['screens'] = $scr;
		}

		$results['story'] = stripslashes( $story );
		if ( $mws_film['rating_sep'] == "0" ) {
			$results['ratinga'] = str_replace(".", ",", $results['ratinga']);
		} else {
			$results['ratinga'] = str_replace(",", ".", $results['ratinga']);
		}
		unset( $reader, $story );
		$results['result'] = "ok";
		$results['error'] = "";
		$results = array_map( "clean_text", $results );
	} else {
		$results['result'] = "no";
		$results['error'] = $lng_site['error_1'];
	}
	echo json_encode( $results );
}


else if ( isset( $_POST['film_name'] ) OR isset( $_POST['film_name2'] ) ) {
	$film_name = ( ! empty( $_POST['film_name'] ) ) ? $db->safesql( $_POST['film_name'] ) : $db->safesql( $_POST['film_name2'] );
	$query = urlencode( $film_name . " " . $mws_film['trailer_term'] );
	if ( ! empty( $mws_film['api_key'] ) ) {
		$url = "https://www.googleapis.com/youtube/v3/search?q=" . $query . "&key=" . $mws_film['api_key'] . "&part=snippet&maxResults=" . intval( $mws_film['trailer_max'] ) . "&fields=items(id%2Csnippet)";
		$result = json_decode( getURLContent( $url ), true );
		unset( $max, $film_name, $url, $query);
		$char_limit = 25;
		$results = array( '_response' => $result );
		if ( $result ) {
			$results["html"] = "<ul>";
			foreach( $result['items'] as $item ) {
				if ( ! empty( $item['id']['videoId'] ) ) {
					$_title = str_replace( "\\", "", strip_tags( $db->safesql( $item['snippet']['title'] ) ) );
					$_title_s = (dle_strlen( $_title, $config['charset']) > $char_limit) ? dle_substr( $_title, 0, $char_limit, $config['charset'] )."." : $_title;
					$_link = "https://www.youtube.com/watch?v=" . $item['id']['videoId'];
					$_mlink = $item['id']['videoId'];
					if ( $mws_film['trailertag'] == "short" ) {
						$_link = explode( "?v=", $_link ); $_link = $_link[1];
						$_mlink = explode( "?v=", $_mlink ); $_mlink = $_mlink[1];
					}
					$_thumb = $item['snippet']['thumbnails']['high']['url'];
					$results["html"] .= "<li link='{$_link}' mlink='{$_mlink}'>{$_title_s}<br><img src='{$_thumb}' height='90' alt='{$_title}' title='{$_title}' /></li>";
				}
			}
			$results["html"] .= "</ul>";
			$results['error'] = "";
			$results["result"] = "ok";
			if ( count( $result['items'] ) == 0 ) {
				$results['error'] = $lng_site['error_11'];
				$results["result"] = "no";
			}
		} else {
			$results["result"] = "no";
			$results['error'] = $lng_site['error_3'];
		}
	} else {
		$results["result"] = "no";
		$results['error'] = $lng_site['error_12'];
	}
	unset( $result );
	echo json_encode( $results );

} else if ( isset( $_POST['htaccess'] ) ) {
	$ttags = de_serialize( $mws_film['tags'] );
	$tnames = de_serialize( $mws_film['tnames'] );
	$tlink = de_serialize( $mws_film['tlink'] );
	$htaccess = "";
	foreach( $ttags as $key => $value ) {
		if ( $value == "custom" && $tlink[ $key ] ) {
			$htaccess .= "RewriteRule ^" . $tnames[ $key ] . "/([^/]+)$ index.php?do=xfsearch&xf=$1 [L]\n";
		}
	}
	echo "# Film Reader\n{$htaccess}# Film Reader";
	unset( $ttags, $tnames, $tlink, $htaccess );


} else if ( isset( $_POST['add_htaccess'] ) ) {

	$handle = fopen( ROOT_DIR . "/.htaccess", "r");
	$data = fread( $handle, filesize( ROOT_DIR . "/.htaccess" ) );
	fclose( $handle );

	$handle = fopen( ROOT_DIR . "/.htaccess_backup_" . md5( $data ), "w");
	fwrite( $handle, $data );
	fclose( $handle );

	$newdata = str_replace( "RewriteEngine On", "RewriteEngine On\n\n# Film Reader\nRewriteRule ^requests(/?)$ index.php?do=film-requests [L]\nRewriteRule ^requests/page/([0-9]+)/(/?)$ index.php?do=film-requests&page=$1\nRewriteRule ^requests/page/([0-9]+)/([A-Za-z]+)/([A-Za-z]+)(/?)$ index.php?do=film-requests&page=$1&order=$2&by=$3 [L]\n# Film Reader\n", $data );

	$handle = fopen( ROOT_DIR . "/.htaccess", "w");
	fwrite( $handle, $newdata );
	fclose( $handle );

	echo "OK";

} else if ( isset( $_POST['add_xfields'] ) ) {

	$handle = fopen( ENGINE_DIR . "/data/xfields.txt", "r");
	$data = fread( $handle, filesize( ENGINE_DIR . "/data/xfields.txt" ) );
	fclose( $handle );

	$handle = fopen(ENGINE_DIR . "/data/xfields.txt_backup_" . md5( $data ), "w");
	fwrite( $handle, $data );
	fclose( $handle );

	$newdata = trim( trim( $data ) . "\n" . $lng_site['xfields'] );

	$handle = fopen( ENGINE_DIR . "/data/xfields.txt", "w");
	fwrite( $handle, $newdata );
	fclose( $handle );

	echo "OK";

} else if ( isset( $_POST['film_request'] ) ) {

	if ( isset( $_POST['req_url'] ) ) {

		require_once ENGINE_DIR . '/classes/templates.class.php';

		$req_url = $db->safesql( $_POST['req_url'] );
		$data = add_request_post( $req_url );

		if ( isset( $data['error'] ) && count( $data['error'] ) > 0 ) {
			echo json_encode( array( "result" => "ok", "error"  => implode("<br>", $data['error'] ) ) );
		} else {
			$tpl = new dle_template();
			$tpl->dir = ROOT_DIR . '/templates/' . totranslit( $config['skin'], false, false );
			$tpl->load_template("mws-film/requests.tpl");
			$tpl->set( "{title}", $data['title'] );
			$tpl->set( "{year}", $data['year'] );
			$tpl->set( "{url}", $data['url'] );
			$tpl->set( "{id}", $data['id'] );
			$tpl->set( "{req-link}", "add_request('{$data['id']}');");
			$tpl->set( "{author}", $member_id['name'] );
			$tpl->set( "{date}", date("Y-m-d", $_TIME) );
			$tpl->set( "{count}", "1" );
			$tpl->set( "{navigation}", "" );
			$tpl->set( "{img-src}", $data['img'] );
			$tpl->compile("mws-film/requests");
			$tpl->clear();

			$data['stats']['text'] = implode("<br>", $data['stats']['text'] );
			echo json_encode( array( "result" => "ok", "html" => $tpl->result["mws-film/requests"], "stats" => $data['stats'], "error"  => implode("<br>", $data['error'] ) ) );
		}
	}

	else if ( isset( $_POST['add_req'] ) ) {

		$rid = $db->safesql( $_POST['add_req'] );

		$check4 = $db->super_query("SELECT * FROM " . PREFIX . "_mws_film_requestslog WHERE rid = '{$rid}' AND user_id = '{$member_id['user_id']}'");
		if ( isset( $check4['user_id'] ) && $mws_film['req_multic'] == "0" ) {

			$result["error"] = $lng_site['error_5'];

		} else {

			$row = $db->super_query("SELECT * FROM " . PREFIX . "_mws_film_requests WHERE id='{$rid}'");

			if ( isset( $row['post_id'] ) ) {
				$result = array("error" => "");

				$db->query("UPDATE " . PREFIX . "_mws_film_requests SET count=count+1 WHERE id='{$rid}'");
				$db->query("INSERT INTO " . PREFIX . "_mws_film_requestslog ( rid, user_id ) VALUES ( '{$rid}', '{$member_id['user_id']}')");
				$result["count"] = $row['count'] + 1;
				$result["result"] = "ok";
			} else {
				$result["error"] = $lng_site['error_8'];
			}
		}
		echo json_encode( $result );
	}
	else if ( isset( $_POST['req_id'] ) && isset( $_POST['req_type'] ) ) {

		$_POST['req_id'] = $db->safesql( $_POST['req_id'] );

		if ( $_POST['req_id'] != "*" ) {
			$req = $db->super_query("SELECT * FROM " . PREFIX . "_mws_film_requests WHERE id = '{$_POST['req_id']}'");
			if ( $_POST['req_type'] == "del" ) {
				$db->query("DELETE FROM " . PREFIX . "_mws_film_requests WHERE id = '{$_POST['req_id']}'");
				if ( $mws_film['req_del_post'] ) {
					$db->query( "DELETE FROM " . PREFIX . "_post WHERE id = '{$req['post_id']}'" );
					$db->query( "DELETE FROM " . PREFIX . "_post_extras WHERE news_id = '{$req['post_id']}'" );
					$db->query( "DELETE FROM " . PREFIX . "_comments WHERE post_id = '{$req['post_id']}'" );
					$db->query( "DELETE FROM " . PREFIX . "_post_log WHERE news_id = '{$req['post_id']}'" );
					$db->query( "DELETE FROM " . PREFIX . "_tags WHERE news_id = '{$req['post_id']}'" );
					$db->query( "DELETE FROM " . PREFIX . "_logs WHERE news_id = '{$req['post_id']}'" );
					$db->query( "UPDATE " . PREFIX . "_users SET news_num=news_num-1 WHERE name = '{$req['autor']}'" );
					$row = $db->super_query( "SELECT images FROM " . PREFIX . "_images WHERE news_id = '{$req['post_id']}'" );
					$listimages = explode( "|||", $row['images'] );
					if ( $row['images'] != "" ) foreach ( $listimages as $dataimages ) {
						$url_image = explode( "/", $dataimages );
						if ( count( $url_image ) == 2 ) {
							$folder_prefix = $url_image[0] . "/";
							$dataimages = $url_image[1];
						} else {
							$folder_prefix = "";
							$dataimages = $url_image[0];
						}
						@unlink( ROOT_DIR . "/uploads/posts/" . $folder_prefix . $dataimages );
						@unlink( ROOT_DIR . "/uploads/posts/" . $folder_prefix . "thumbs/" . $dataimages );
					}
					$db->query( "DELETE FROM " . PREFIX . "_images WHERE news_id = '{$req['post_id']}'" );
					$db->query("DELETE FROM " . PREFIX . "_mws_film_requests WHERE id = '{$req['id']}'");
					$db->query("DELETE FROM " . PREFIX . "_mws_film_requestslog WHERE rid = '{$req['id']}'");
				}
				echo "ok";
			} else if ( $_POST['req_type'] == "dls" ) {
				if ( $mws_film['req_del_post'] ) {
					$db->query( "DELETE FROM " . PREFIX . "_post WHERE id = '{$req['post_id']}'" );
					$db->query( "DELETE FROM " . PREFIX . "_post_extras WHERE news_id = '{$req['post_id']}'" );
					$db->query( "DELETE FROM " . PREFIX . "_comments WHERE post_id = '{$req['post_id']}'" );
					$db->query( "DELETE FROM " . PREFIX . "_post_log WHERE news_id = '{$req['post_id']}'" );
					$db->query( "DELETE FROM " . PREFIX . "_tags WHERE news_id = '{$req['post_id']}'" );
					$db->query( "DELETE FROM " . PREFIX . "_logs WHERE news_id = '{$req['post_id']}'" );
					$db->query( "UPDATE " . PREFIX . "_users SET news_num=news_num-1 WHERE name = '{$req['autor']}'" );
					$row = $db->super_query( "SELECT images FROM " . PREFIX . "_images WHERE news_id = '{$req['post_id']}'" );
					$listimages = explode( "|||", $row['images'] );
					if ( $row['images'] != "" ) foreach ( $listimages as $dataimages ) {
						$url_image = explode( "/", $dataimages );
						if ( count( $url_image ) == 2 ) {
							$folder_prefix = $url_image[0] . "/";
							$dataimages = $url_image[1];
						} else {
							$folder_prefix = "";
							$dataimages = $url_image[0];
						}
						@unlink( ROOT_DIR . "/uploads/posts/" . $folder_prefix . $dataimages );
						@unlink( ROOT_DIR . "/uploads/posts/" . $folder_prefix . "thumbs/" . $dataimages );
					}
					$db->query( "DELETE FROM " . PREFIX . "_images WHERE news_id = '{$req['post_id']}'" );
				}
				if ( $mws_film['req_notify'] && ! empty( $req['user_id'] ) ) {
					$req_notify_type = de_serialize( $mws_film['req_notify_type'] );
					if ( $mws_film['req_notifyall'] ) {
						$_userids = array();
						$db->query("SELECT r.user_id, u.email, u.name FROM " . PREFIX . "_mws_film_requestslog as r LEFT JOIN " . PREFIX . "_users as u ON r.user_id = u.user_id WHERE r.rid = '{$_POST['req_id']}'");
						while ( $u = $db->get_row() ) { $_userids[] = $u; }
						$db->free();
						foreach ( $_userids as $_userid ) {
							if ( ! empty( $_userid['name'] ) && ! empty( $_userid['email'] ) ) {
								$_message = str_replace(
									array("\r\n", "{user-name}", "{add-name}", "[film-link]", "[/film-link]", "{film-name}" ),
									array("<br>", $_userid['name'], $req['autor'], "<a href=\"index.php?newsid={$req['post_id']}\" target=\"_blank\">", "</a>", $req['title'] ),
									$mws_film['req_notify2_text']
								);
								$_message = $db->safesql( $_message );
								$_title = str_replace( array("{add-name}", "{film-name}"), array($req['autor'], $req['title']), $db->safesql( $mws_film['req_notify2_title'] ) );
								if ( in_array( "pm", $req_notify_type ) ) {
									$db->query( "INSERT INTO " . PREFIX . "_pm (subj, text, user, user_from, date, pm_read, folder) VALUES ('{$_title}', '{$_message}', '{$_userid['user_id']}', '', '{$_TIME}', '0', 'inbox')" );
									$db->query( "UPDATE " . PREFIX . "_users SET pm_unread = pm_unread + 1, pm_all = pm_all+1 WHERE user_id = '{$_userid['user_id']}'" );
								}
								if ( in_array( "mail", $req_notify_type ) ) {
									require_once ENGINE_DIR . '/classes/mail.class.php';
									$mail = new dle_mail( $config );
									$mail->send( $_userid['email'], $_title, strip_tags( $_message ) );
									unset( $mail );
								}
							}
						}
						$db->query("DELETE FROM " . PREFIX . "_mws_film_requests WHERE id = '{$_POST['req_id']}'");
						$db->query("DELETE FROM " . PREFIX . "_mws_film_requestslog WHERE rid = '{$_POST['req_id']}'");
						unset( $_message, $_title, $_userids );
					} else {
						$mws_film['req_notify2_text'] = str_replace(
							array("\r\n", "{user-name}", "{add-name}", "[film-link]", "[/film-link]", "{film-name}" ),
							array("<br>", $req['user_name'], $req['autor'], "<a href=\"index.php?newsid={$req['post_id']}\" target=\"_blank\">", "</a>", $req['title'] ),
							$mws_film['req_notify2_text']
						);
						$mws_film['req_notify2_title'] = str_replace( array("{add-name}", "{film-name}"), array($req['autor'], $req['title']), $db->safesql( $mws_film['req_notify2_title'] ) );
						$mws_film['req_notify2_text'] = $db->safesql( $mws_film['req_notify2_text'] );
						if ( in_array( "pm", $req_notify_type ) ) {
							$db->query( "INSERT INTO " . PREFIX . "_pm (subj, text, user, user_from, date, pm_read, folder) VALUES ('{$mws_film['req_notify2_title']}', '{$mws_film['req_notify2_text']}', '{$req['user_id']}', '', '{$_TIME}', '0', 'inbox')" );
							$db->query( "UPDATE " . PREFIX . "_users SET pm_unread = pm_unread + 1, pm_all = pm_all+1 WHERE user_id = '{$req['user_id']}'" );
						}
						if ( in_array( "mail", $req_notify_type ) ) {
							include_once ENGINE_DIR . '/classes/mail.class.php';
							$user = $db->super_query("SELECT email FROM " . PREFIX . "_users WHERE user_id = '{$req['user_id']}'");
							$mail = new dle_mail( $config );
							$mail->send( $user['email'], $mws_film['req_notify2_title'], strip_tags( $mws_film['req_notify2_text'] ) );
						}
						$db->query("DELETE FROM " . PREFIX . "_mws_film_requests WHERE id = '{$_POST['req_id']}'");
						$db->query("DELETE FROM " . PREFIX . "_mws_film_requestslog WHERE rid = '{$_POST['req_id']}'");
					}
				}
				echo "ok";

			} else if ( $_POST['req_type'] == "add" ) {
				$db->query("UPDATE " . PREFIX . "_post SET approve = 1 WHERE id = '{$req['post_id']}'");
				$db->query("UPDATE " . PREFIX . "_mws_film_requests SET added = 1 WHERE id = '{$req['id']}'");
				if ( $mws_film['req_add_as'] ) {
					$db->query( "UPDATE " . PREFIX . "_post SET autor = '{$member_id['name']}' WHERE id = '{$req['post_id']}'" );
					$db->query( "UPDATE " . PREFIX . "_mws_film_requests SET autor = '{$member_id['name']}' WHERE id = '{$req['id']}'" );
					$db->query( "UPDATE " . PREFIX . "_post_extras SET user_id='{$member_id['user_id']}' WHERE news_id = '{$req['post_id']}'" );
					$db->query( "UPDATE " . PREFIX . "_users SET news_num=news_num+1 WHERE name = '{$member_id['name']}'" );
					if ( $member_id['name'] != $req['user_name'] ) $db->query( "UPDATE " . PREFIX . "_users set news_num=news_num-1 WHERE name = '{$req['user_name']}'" );
				}
				$db->free();
				if ( $mws_film['req_notify'] && ! empty( $req['user_id'] ) ) {
					$req_notify_type = de_serialize( $mws_film['req_notify_type'] );
					if ( $mws_film['req_notifyall'] ) {
						$_userids = array();
						$db->query("SELECT  r.user_id, u.email, u.name FROM " . PREFIX . "_mws_film_requestslog as r LEFT JOIN " . PREFIX . "_users as u ON r.user_id = u.user_id WHERE r.rid = '{$_POST['req_id']}'");
						while ( $u = $db->get_row() ) { $_userids[] = $u; }
						$db->free();
						foreach ( $_userids as $_userid ) {
							if ( ! empty( $_userid['name'] ) && ! empty( $_userid['email'] ) ) {
								$_message = str_replace(
									array("\r\n", "{user-name}", "{add-name}", "[film-link]", "[/film-link]", "{film-name}" ),
									array("<br>", $_userid['name'], $req['autor'], "<a href=\"index.php?newsid={$req['post_id']}\" target=\"_blank\">", "</a>", $req['title'] ),
									$mws_film['req_notify_text']
								);
								$_message = $db->safesql( $_message );
								$_title = str_replace( array("{add-name}", "{film-name}"), array($req['autor'], $req['title']), $db->safesql( $mws_film['req_notify_title'] ) );
								if ( in_array( "pm", $req_notify_type ) ) {
									$db->query( "INSERT INTO " . PREFIX . "_pm (subj, text, user, user_from, date, pm_read, folder) VALUES ('{$_title}', '{$_message}', '{$_userid['user_id']}', '', '{$_TIME}', '0', 'inbox')" );
									$db->query( "UPDATE " . PREFIX . "_users SET pm_unread = pm_unread + 1, pm_all = pm_all+1 WHERE user_id = '{$_userid['user_id']}'" );
								}
								if ( in_array( "mail", $req_notify_type ) ) {
									require_once ENGINE_DIR . '/classes/mail.class.php';
									$mail = new dle_mail( $config );
									$mail->send( $_userid['email'], $_title, strip_tags( $_message ) );
									unset( $mail );
								}
							}
						}
						unset( $_message, $_title, $_userids );
					} else {
						$mws_film['req_notify_text'] = str_replace(
							array("\r\n", "{user-name}", "{add-name}", "[film-link]", "[/film-link]", "{film-name}" ),
							array("<br>", $req['user_name'], $member_id['name'], "<a href=\"index.php?newsid={$req['post_id']}\" target=\"_blank\">", "</a>", $req['title'] ),
							$mws_film['req_notify_text']
						);
						$mws_film['req_notify_title'] = str_replace( array("{add-name}", "{film-name}"), array($member_id['name'], $req['title']), $db->safesql( $mws_film['req_notify_title'] ) );
						$mws_film['req_notify_text'] = $db->safesql( $mws_film['req_notify_text'] );

						if ( in_array( "pm", $req_notify_type ) ) {
							$db->query( "INSERT INTO " . PREFIX . "_pm (subj, text, user, user_from, date, pm_read, folder) VALUES ('{$mws_film['req_notify_title']}', '{$mws_film['req_notify_text']}', '{$req['user_id']}', '', '{$_TIME}', '0', 'inbox')" );
							$db->query( "UPDATE " . PREFIX . "_users SET pm_unread = pm_unread + 1, pm_all = pm_all+1 WHERE user_id = '{$req['user_id']}'" );
						}
						if ( in_array( "mail", $req_notify_type ) ) {
							include_once ENGINE_DIR . '/classes/mail.class.php';
							$user = $db->super_query("SELECT email FROM " . PREFIX . "_users WHERE user_id = '{$req['user_id']}'");
							$mail = new dle_mail( $config );
							$mail->send( $user['email'], $mws_film['req_notify_title'], strip_tags( $mws_film['req_notify_text'] ) );
						}
					}
				}
				echo "ok";
			}
		} else {
			if ( $_POST['req_type'] == "delall" ) {
				$db->query("DELETE FROM " . PREFIX . "_mws_film_requests");
				echo "ok";
			}
		}
	}

} else if ( isset( $_POST['page_url'] ) ) {
	$result = array();
	$html = getURLContent( $_POST['page_url'] );
	$dom = new DOMDocument();
	$dom->loadHTML( $html );
	$x = new DOMXPath( $dom );
	// <meta itemprop="image" content
	// <meta name="twitter:image" content
	$result['html'] = download_img( $x->query('//meta[@itemprop="image"]')->item(0)->getAttribute('content') );
	$result['result'] = "ok";
	echo json_encode( $result );
	unset( $html, $dom, $x );

} else if ( isset( $_POST['flush_logs'] ) && $_POST['flush_logs'] == "do" ) {

	$db->query("TRUNCATE TABLE " . PREFIX . "_mws_film_requestslog");
	echo "ok";

} else {
	echo json_encode( array( "result" => "no", "error"  => $lng_site['error_0'] ) );

}

?>