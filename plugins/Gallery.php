<?php

use Intervention\Image\ImageManager;

/**
 * Pico dummy plugin - a template for plugins
 *
 * You're a plugin developer? This template may be helpful :-)
 * Simply remove the events you don't need and add your own logic.
 *
 * @author  Klas Gidlöv
 * @link    http://gidlov.com/en/code/pico-gallery
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.2.2
 */
final class Gallery extends AbstractPicoPlugin
{

    private $requested_url;
    private $pico_config;
    private $gallery_config;
    private $single_image;
    private $requested_gallery;
    private $current_gallery;
    private $file;

    private $root_dir;
    private $content_dir;
    private $config_dir;
    private $themes_dir;

    private $pre_thumb;

    /**
     * This plugin is enabled by default?
     *
     * @see AbstractPicoPlugin::$enabled
     * @var boolean
     */
    protected $enabled = true;

    /**
     * This plugin depends on ...
     *
     * @see AbstractPicoPlugin::$dependsOn
     * @var string[]
     */
    protected $dependsOn = array();

    /**
     * Triggered after Pico has loaded all available plugins
     *
     * This event is triggered nevertheless the plugin is enabled or not.
     * It is NOT guaranteed that plugin dependencies are fulfilled!
     *
     * @see    Pico::getPlugin()
     * @see    Pico::getPlugins()
     * @param  object[] &$plugins loaded plugin instances
     * @return void
     */
    public function onPluginsLoaded(array &$plugins)
    {
        $this->root_dir = $this->__call('getRootDir', array());
        $this->pico_config_dir = $this->__call('getConfigDir', array());
        $this->themes_dir = $this->__call('getThemesDir', array());
    }

    /**
     * Triggered after Pico has read its configuration
     *
     * @see    Pico::getConfig()
     * @param  array &$config array of config variables
     * @return void
     */
    public function onConfigLoaded(array &$config)
    {
      $this->pico_config = $config;
      if (isset($config['gallery'])) {
        $this->gallery_config = $config['gallery'];
  		}
      if (null !== ($this->__call('getConfig', array('content_dir')))) {
        $this->content_dir = $this->__call('getConfig', array('content_dir'));
      }
    }

    /**
     * Triggered when Pico reads its known meta header fields
     *
     * @see    Pico::getMetaHeaders()
     * @param  string[] &$headers list of known meta header
     *     fields; the array value specifies the YAML key to search for, the
     *     array key is later used to access the found value
     * @return void
     */
    public function onMetaHeaders(array &$headers)
    {
        $headers['gallery'] = 'Gallery';
    }

    /**
     * Triggered after Pico has parsed the meta header
     *
     * @see    Pico::getFileMeta()
     * @param  string[] &$meta parsed meta data
     * @return void
     */
    public function onMetaParsed(array &$meta)
    {
	if( empty($meta['gallery']) )
	    $this->enabled = false;
    }

    /**
     * Triggered after Pico has evaluated the request URL
     *
     * @see    Pico::getRequestUrl()
     * @param  string &$url part of the URL describing the requested contents
     * @return void
     */
    public function onRequestUrl(&$url)
    {

# This is stupid.  Why should a gallery be limited to one URL?o

      if (!isset($this->gallery_config)) {
        return;
      }
      $this->requested_url = $url;
      $this->requested_url = ($this->requested_url == '') ? 'index' : $this->requested_url;
      $this->requested_gallery = array();
      foreach (array_keys($this->gallery_config) as $gallery_name) {
        if ($this->gallery_config[$gallery_name]['page'] == $this->requested_url) {
          $this->requested_gallery[] = $gallery_name;
        }
        $url_strip = str_replace($this->gallery_config[$gallery_name]['page'].'/', '', $this->requested_url);
        $url_part = explode('/', $url_strip);
        if ($url_part[0] == $gallery_name) {
          if (isset($url_part[1]) && $url_part[1] == 'flush' && isset($url_part[2]) && $url_part[2] == $this->gallery_config[$gallery_name]['flush']) {
            $this->flush($gallery_name);
            $url = '';
          } elseif (isset($url_part[1]) && is_file($this->gallery_config[$gallery_name]['image_path'].'/'.$url_part[1])) {
            $this->current_gallery = $gallery_name;
            $this->single_image = $this->gallery_config[$gallery_name]['image_path'].'/'.$url_part[1];
            unset($url_part[count($url_part)-1]);
            $this->file = $this->content_dir.$this->gallery_config[$gallery_name]['page'].$this->pico_config['content_ext'];
          }
        }
      }
    }

    /**
     * Triggered before Pico reads the contents of the file to serve
     *
     * @see    Pico::loadFileContent()
     * @see    DummyPlugin::onContentLoaded()
     * @param  string &$file path to the file which contents will be read
     * @return void
     */
    public function onContentLoading(&$file)
    {
      if ($this->file) {
        $file = $this->file;
      }
    }

    /**
     * Triggered after Pico has rendered the page
     *
     * @param  string &$output contents which will be sent to the user
     * @return void
     */
    public function onPageRendered(&$output)
    {
      if ($this->requested_gallery) {
        // Get the gallery.
        foreach ($this->requested_gallery as $gallery_name) {
          if (preg_match('/%'.$gallery_name.'%/mi', $output, $match) && $match[0]) {
            $output = str_replace('%'.$gallery_name.'%', $this->markup($gallery_name), $output);
          }
        }
      } elseif ($this->single_image && $this->current_gallery) {
        // Get the image.
        foreach (array_keys($this->gallery_config) as $gallery_name) {
          $replace = $gallery_name == $this->current_gallery ? $this->markup($this->current_gallery) : '';
          $output = str_replace('%'.$gallery_name.'%', $replace, $output);
        }
      }
    }

    protected function flush($name)
    {
	$image_path = $this->gallery_config[$name]['image_path'];
	$thumb_path = $this->gallery_config[$name]['thumb_path'];
	if (!is_dir($image_path)) {
	    return;
	}
	$files = glob($thumb_path.DIRECTORY_SEPARATOR.'*');
	foreach($files as $file) {
	    // Delete all files in the thumbnail folder.
	    if (is_file($file)) {
		unlink($file);
	    }
	}
	foreach(glob($image_path.'/{*.jpg,*.png,*.gif}', GLOB_BRACE) as $image) {
	    // Generate new thumbnails.
	    $size = isset($this->gallery_config[$name]['thumb_size']) ? $this->gallery_config[$name]['thumb_size'] : array(200, 200);
	    $file_name = basename($image);
	    $img = new ImageManager();
	    $img->make($image)
		->fit($size[0], $size[1])
		->save($thumb_path.DIRECTORY_SEPARATOR.'thumb_'.$file_name, 80);
	}
	header('Location: ' . $this->pico_config['base_url'].$this->gallery_config[$name]['page']);
    }

    protected function get($name, $config, $default = '')
    {
      if (isset($this->gallery_config[$name][$config])) {
        return $this->gallery_config[$name][$config];
      } else {
        return $default;
      }
    }


    protected function markup($name)
    {
	$return = '';
	$image_path = $this->gallery_config[$name]['image_path'];
	$thumb_path = $this->gallery_config[$name]['thumb_path'];
	if ($this->single_image) {
	    // Markup for a image
	    $return .= $this->get($name, 'before_image');
	    $return .= '<img src="'.$this->pico_config['base_url'].'/'.$this->single_image.'" alt="'.$this->get($name, 'alt_class').'" class="'.$this->get($name, 'image_class').'">'."\n";
	    $return .= $this->get($name, 'after_image');
	    //$return .= $after_image;
	    return $return;
	} else {
	    // Markup for a gallery.
	    $return .= $this->get($name, 'before_gallery');
	    $gallery = glob($image_path.'/{*.jpg,*.png,*.gif}', GLOB_BRACE);
	    if (isset($this->gallery_config[$name]['sort_by']) && $this->gallery_config[$name]['sort_by'] == 'random') {
		shuffle($gallery);
	    }
	    if (isset($this->gallery_config[$name]['order_by']) && $this->gallery_config[$name]['order_by'] == 'reverse') {
		array_reverse($gallery);
	    }
	    foreach($gallery as $image) {
		$alt_image = (isset($this->gallery_config[$name]['alt_image'])) ? $this->gallery_config[$name]['alt_image'] : pathinfo($image, PATHINFO_FILENAME);
		if (isset($this->gallery_config[$name]['exclude']) && in_array(substr($image, strrpos($image, '/')+1), $this->gallery_config[$name]['exclude'])) {
		    // Skip files in the exclude array.
		    continue;
		}
		$file_name = basename($image);
		$return .= $this->get($name, 'before_thumbnail');

		$return .= '<a href="'.$this->pico_config['base_url'].''.$this->requested_url.'/'.$name.'/'.$file_name.'" class="'.$this->get($name, 'thumbnail_link_class').'"><img src="'.$this->pico_config['base_url'].''.$thumb_path.'/'.'thumb_'.$file_name.'" alt="'.$alt_image.'" class="'.$this->get($name, 'thumbnail_image_class').'"></a>'."\n";
		$return .= $this->get($name, 'after_thumbnail');
	    }
	    $return .= $this->get($name, 'after_gallery');
	    return $return;
	}
    }
}
