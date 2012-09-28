<?php

/*
 * singleton-ish class for passing data to Mustache to render
 */
abstract class ABT_Mustache {

	private static $engine;
	private static $dir;

	public static function init() {
		if ( !class_exists( 'Mustache_Autoloader' ) )
			require dirname(__FILE__) . '/../Mustache/Autoloader.php';
		
		Mustache_Autoloader::register();
		
		self::$dir = dirname(__FILE__) . '/templates';
		$m_opts = array('extension' => 'html');
		
		self::$engine = new Mustache_Engine(
			array(
				'loader' => new Mustache_Loader_FilesystemLoader(self::$dir, $m_opts),
				'partials_loader' => new Mustache_Loader_FilesystemLoader(self::$dir, $m_opts),
				'helpers' => array(
					'percent' => array(__class__, 'helper_percent'),
					'format_date' => array(__class__, 'helper_format_date')
				)
			)
		);
	}

	public static function render( $template, $data = null) {
		return self::$engine->render( $template, $data );
	}
	
	public static function template_path() {
		return self::$dir;
	}
	
	public static function helper_percent($num, $mustache) {
		return $mustache->render($num) * 100 . "%";
	}
	
	// formatter for dates
	public static function helper_format_date($date, $mustache) {
		// silly php bug strtotime not returning false for '0000..' mysql null val
		$date = strtotime($mustache->render($date));
		return $date > 0 ? date('D, M d g:h a', $date) : '--';
	}
}

ABT_Mustache::init();
