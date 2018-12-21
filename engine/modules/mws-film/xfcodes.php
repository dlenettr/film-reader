<?php
/*
=============================================
 Name      : MWS Film Reader v1.8
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : https://dle.net.tr/
 License   : MIT License
=============================================
*/

if ( $dle_area == "short" ) {
	$xfieldsdata = xfieldsdataload( $row['xfields'] );
} else if ( $dle_area == "custom" ) {

} else if ( $dle_area == "full" ) {
	$xfieldsdata = xfieldsdataload( $row['xfields'] );
}

include ENGINE_DIR . "/data/mws-film.conf.php";

$types = array(
	"xfield"	=> array("xfsearch", "xfsearch&xf="),
	"tag"		=> array("tags", "tags&tag="),
	"custom"	=> array("film", "xfsearch&xf="),
	"automatic"	=> array( "", "xfsearch&xf=")
);

$tnames = unserialize( str_replace("'", '"', $mws_film['tnames'] ) );
$ttags = unserialize( str_replace("'", '"', $mws_film['tags'] ) );
$tlink = unserialize( str_replace("'", '"', $mws_film['tlink'] ) );

foreach( $ttags as $key => $value ) {
	$type = $types[ $value ];
	$link = $tlink[ $key ];
	if ( $value == "custom" ) $type[0] = $tnames[ $key ];
	if ( $value == "automatic" ) $type[0] = $key;
	if ( $key == "genres" ) { $key = "genre"; }
	else if ( $key == "actors" ) { $key = "stars"; }

	$strtags = $xfieldsdata[ $key ];
	$tags = explode( ",", str_replace("'", "'", $strtags ) ); unset( $strtags );
	$tags = array_map( "trim", $tags );

	$result = array();
	if ( $link == "1" ) {
		foreach ( $tags as $tag ) {
			$tag = str_replace( ".", "", $tag );
			if ( $config['allow_alt_url'] ) {
				$result[] = "<a href=\"" . $config['http_home_url'] . $type[0] . "/" . str_replace(" ", "+", strtolower( $tag ) ) . "\">" . $tag . "</a>";
			} else {
				$result[] = "<a href=\"{$PHP_SELF}?do=" . $type[1] . str_replace(" ", "+", strtolower( $tag ) ) . "\">" . $tag . "</a>";
			}
		}
	} else {
		$result = $tags;
	}
	$tpl->set( "{film-" . $key . "}", implode( ", ", $result ) );
}

?>