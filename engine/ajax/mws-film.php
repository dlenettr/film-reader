<?php
/*
=============================================
 Name      : Film Reader v1.8.3
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : https://mehmethanoglu.com.tr
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

} else {
	echo json_encode( array( "result" => "no", "error"  => $lng_site['error_0'] ) );

}

?>