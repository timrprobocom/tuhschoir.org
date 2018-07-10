<?php

/**
 * JSON Calendar plugin - a template for plugins
 *
 * @author  Tim Roberts
 * @link    http://timr.probo.com
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.0.0
 */
final class CalendarPlugin extends AbstractPicoPlugin
{
    private $pico_config;
    private $file_dir;
    private $json;

    private $root_dir;
    private $pico_config_dir;
    private $themes_dir;

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
     * This event is triggered whether the plugin is enabled or not.
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
    }

    /**
     * Triggered after Pico has discovered the content file to serve
     *
     * @see    Pico::getBaseUrl()
     * @see    Pico::getRequestFile()
     * @param  string &$file absolute path to the content file to serve
     * @return void
     */
    public function onRequestFile(&$file)
    {
	$this->file_dir = dirname($file);
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
	if( empty($meta['calendar']) )
	    $this->enabled = false;
	else
	    $this->json = $meta['calendar'];
    }

    /**
     * Triggered after Pico has rendered the page
     *
     * @param  string &$output contents which will be sent to the user
     * @return void
     */
    public function onPageRendered(&$output)
    {
      $cal_json = $this->file_dir."/".$this->json;

      if (preg_match('/%calendar%/mi', $output, $match) && $match[0]) {
	$output = str_replace('%calendar%', $this->markup($cal_json), $output);
      }
    }

# Date options
# If end exists:
#   If start and end on different day, display as
#     Jan 1-3 or Jan 31-Feb 2  (no time)
#   If start and end on same day and same time, display as
#     Jan 1   All day
#   If start and end on same day, display as
#     Jan 1   8 AM - 8 PM
# Else
#   If start at midnight, display as
#     Jan 1   TBA
#   Else
#     Jan 1   8:00 AM

    protected function markup($calfile)
    {
	$db = json_decode(file_get_contents($calfile));
	$year = 0;
	$text = <<<END
    <style>
    table.cal td { border: none; padding: 2px; }
    </style>
    <table class='cal'>
    <tr><th>Date</th><th>Time</th><th>Event</th><th>Location</th></tr>
END;
	$now = time() - 365 * 86400;
	foreach( $db as $row )
	{
	    $starttm = strtotime($row->start);
	    $start = getdate($starttm);
	    $endtm = strtotime($row->end);
	    $end = getdate($endtm);
	    if( $starttm < $now && $endtm < $now )
		continue;
	    if( $row->end )
	    {
		if( $start['mon'] != $end['mon'] )
		{
		    $dt = date('M j',$starttm) . ' - ' . date('M j',$endtm);
		    $tm = "";
		}
		else if( $start['mday'] != $end['mday'] )
		{
		    $dt = date('M j',$starttm) . '-' . date('j',$endtm);
		    $tm = "";
		}
		else if( $start['hours'] != $end['hours'] )
		{
		    $dt = date('D M j', $starttm);
		    $tm = date('g:i A', $starttm) .' - '.  date('g:i A', $endtm);
		    $tm = str_replace(":00", "", $tm );
		}
		else
		{
		    $dt = date('D M j', $starttm);
		    $tm = 'All day';
		}
	    }
	    else
	    {
		if( $start['hours'] == 0 )
		{
		    $dt = date('D M j', $starttm);
		    $tm = "TBA";
		}
		else
		{
		    $dt = date('D M j', $starttm);
		    $tm = date('g:i A', $starttm);
		}
	    }
	    if( date('Y',$starttm) != $year )
	    {
		$year = date('Y',$starttm);
		$text .= "<tr><th colspan=4 style='text-align: center'>$year</th></tr>\n";
	    }
	    $text .= "<tr><td>$dt</td><td>$tm</td><td>$row->event</td><td>$row->location</td></tr>\n";
	}
	$text .= "</table>\n";
	return $text;
    }
}
