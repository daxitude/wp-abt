<?php

/*
 * singleton-ish class for pass data to Mustache to render
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
					'percent' => array(__class__, 'helper_percent')
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
}

ABT_Mustache::init();
