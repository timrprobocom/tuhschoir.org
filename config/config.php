<?php
/**
 * Pico configuration
 *
 * This is the configuration file for {@link Pico}. It comes loaded with the
 * default values, which can be found in {@link Pico::getConfig()} (see
 * {@path "lib/Pico.php"}).
 *
 * To override any of the default settings below, copy this file to
 * {@path "config/config.php"}, uncomment the line, then make and
 * save your changes.
 *
 * @author  Gilbert Pellegrom
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.0
 */

/*
 * BASIC
 */
// $config['site_title'] = 'Pico';              // Site title
// $config['base_url'] = '';                    // Override base URL (e.g. http://example.com)
// $config['rewrite_url'] = null;               // A boolean indicating forced URL rewriting

/*
 * THEME
 */
// $config['theme'] = 'default';                // Set the theme (defaults to "default")
// $config['twig_config'] = array(              // Twig settings
//     'cache' => false,                        // To enable Twig caching change this to a path to a writable directory
//     'autoescape' => false,                   // Auto-escape Twig vars
//     'debug' => false                         // Enable Twig debug
// );

$config['theme'] = 'Travelify';
$config['site_title'] = 'Tualatin High School Choir Boosters';
$config['site_subtitle'] = 'Helping our students to have a great high school experience';
$config['site_year'] = date('Y');
$config['site_copyright'] = 'TuHS Choir Boosters';
$config['timezone'] = 'PST8PDT';
$config['date_format'] = '%B %e, %Y';

/*
 * CONTENT
 */
// $config['date_format'] = '%D %T';            // Set the PHP date format as described here: http://php.net/manual/en/function.strftime.php
// $config['pages_order_by'] = 'alpha';         // Order pages by "alpha" or "date"
// $config['pages_order'] = 'asc';              // Order pages "asc" or "desc"
// $config['content_dir'] = 'content-sample/';  // Content directory
// $config['content_ext'] = '.md';              // File extension of content files to serve

/*
 * TIMEZONE
 */
// $config['timezone'] = 'UTC';                 // Timezone may be required by your php install

/*
 * PLUGINS
 */
// $config['DummyPlugin.enabled'] = false;      // Force DummyPlugin to be disabled

/*
 * CUSTOM
 */
// $config['custom_setting'] = 'Hello';         // Can be accessed by {{ config.custom_setting }} in a theme


function add_folder(&$config, $dir)
{
    foreach( scandir($dir) as $sub )
    {
	if( $sub[0] == '.' )
	    continue;
	if( $sub == 'thumbs' )
	    continue;
	$path = "$dir/$sub";
	$url = str_replace("gallery/", '', $path);
	$name = str_replace( "/", "-", $url );
	if( is_dir($path) )
	{
	    $config['gallery'][$name] = array(
		'page' => "pictures/$url",
		'image_path' => $path,
		'thumb_path' => "$path/thumbs",
		'thumbnail_image_class' => 'image_frame',
		'flush' => 'flush'
	    );

	    add_folder( $config, $path );
	}
    }
}

$config['gallery'] = array();
add_folder($config, "gallery");

#echo "<pre>\n";
#print_r($config);
#echo "</pre>\n";

