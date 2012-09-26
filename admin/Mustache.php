<?php

/*
 * singleton class for pass data to Mustache to render
 */
abstract class ABT_Mustache {

	private static $engine;
	private static $dir;

	public static function init() {
		if ( !class_exists( 'Mustache_Loader_FilesystemLoader' ) )
			require dirname(__FILE__) . '/../mustache/AutoLoader.php';
		
		Mustache_Autoloader::register();
		
		self::$dir = dirname(__FILE__) . '/templates';
		$m_opts = array('extension' => 'html');
		
		self::$engine = new Mustache_engine(
			array(
				'loader' => new Mustache_Loader_FilesystemLoader(self::$dir, $m_opts),
				'partials_loader' => new Mustache_Loader_FilesystemLoader(self::$dir, $m_opts)
			)
		);
	}

	public static function render( $template, $data = null) {
		return self::$engine->render( $template, $data );
	}
	
	public static function template_path() {
		return self::$dir;
	}
}

ABT_Mustache::init();
