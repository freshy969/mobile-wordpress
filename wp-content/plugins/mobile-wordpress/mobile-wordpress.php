<?php
/*
	Plugin Name: Mobile WordPress
	Plugin URI:  http://mwordpress.flexplat.com
	Description: A mobile detection and redirection plugin.  When end users access the web site from mobile devices, they will be redirected to Mobile WordPress theme instead of default one.
	Version:     1.0.0
	Author:      Rickey Gu
	Author URI:  http://flexplat.com
*/

require_once(ABSPATH . 'wp-content/plugins/mobile-wordpress/lib/detection.php');


class MWordPress
{
	private $id;
	private $themes;


	public function __construct()
	{
		$this->id = 'mobile-wordpress';
		$this->themes = wp_get_themes();
	}

	public function __destruct()
	{
	}


	public function skip()
	{
		if ( empty($this->themes[$this->id]) )
		{
			return true;
		}

		return false;
	}

	public function stylesheet()
	{
		$stylesheet = $this->themes[$this->id]->stylesheet;

		return $stylesheet;
	}

	public function template()
	{
		$template = $this->themes[$this->id]->template;

		return $template;
	}
}


function mwordpress_amphtml()
{
	$parts = parse_url(home_url());
	$current_uri = "{$parts['scheme']}://{$parts['host']}" . add_query_arg(NULL, NULL);

	echo '<link rel="amphtml" href="' . $current_uri . '" />' . "\n";
}


function mwordpress_main()
{
	$mwordpress = new MWordPress();

	if ( $mwordpress->skip() )
	{
		return;
	}

	$redirection = !empty($_GET['m-redirection']) ? $_GET['m-redirection'] : '';
	$style = !empty($_COOKIE['mwordpress_style']) ? $_COOKIE['mwordpress_style'] : '';

	if ( !empty($redirection) )
	{
		$device = $redirection != 'mobile' ? 'desktop' : 'mobile';
	}
	elseif ( !empty($style) )
	{
		$device = $style != 'mobile' ? 'desktop' : 'mobile';
	}
	else
	{
		$data = array();
		$data['user_agent'] = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$data['accept'] = !empty($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
		$data['profile'] = !empty($_SERVER['HTTP_PROFILE']) ? $_SERVER['HTTP_PROFILE'] : '';

		$device = mwordpress_get_device($data);

		$device = ( $device == 'desktop' || $device == 'bot' ) ? 'desktop' : 'mobile';
	}

	// make the cookie expires in a year time: 60 * 60 * 24 * 365 = 31,536,000
	setcookie('mwordpress_style', $device, 365 * DAYS_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);

	if ( $device != 'mobile' )
	{
		add_action( 'wp_head', 'mwordpress_amphtml', 0 );

		return;
	}

	add_filter( 'stylesheet', array($mwordpress, 'stylesheet') );
	add_filter( 'template', array($mwordpress, 'template') );
}
add_action( 'plugins_loaded', 'mwordpress_main', 1 );