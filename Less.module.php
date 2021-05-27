<?php namespace ProcessWire;

/**
 * LESS parser module for ProcessWire
 * 
 * Usage
 * ~~~~~
 * // compile 2 less files to a css file
 * $less = $modules->get('Less');
 * $less->setOption('compress', true);
 * $less->addFile('/path/to/file1.less');
 * $less->addFile('/path/to/file2.less'); 
 * $less->saveCss('/path/to/file.min.css'); 
 * 
 * // access wikimedia less parser directly (it has many methods)
 * $parser = $less->parser();
 * $parser->parseFile('/path/to/file.less');
 * $css = $parser->getCss();
 * ~~~~~
 * 
 */

class Less extends WireData implements Module {

	public static function getModuleInfo() {
		return array(
			'title' => 'Less',
			'version' => 2,
			'summary' => 'Less Parser for ProcessWire',
			'author' => 'Bernhard Baumrock, Ryan Cramer',
			'icon' => 'css3',
			'singular' => false,
			'requires' => 'ProcessWire>=3.0.164',
		);
	}

	/**
	 * @var \Less_Parser|null
	 * 
	 */
	protected $parser = null;

	/**
	 * @var array
	 * 
	 */
	protected $options = array(
		'compress' => false,
	);

	/**
	 * Construct
	 * 
	 */
	public function __construct() {
		$this->set('compress', false);
		parent::__construct();
	}

	/**
	 * Init
	 * 
	 */
	public function init() { }

	/**
	 * Set one or more options from associative array
	 * 
	 * @param array $options
	 * @param bool $reset Remove previously set options rather than merging them?
	 * @return $this
	 * 
	 */
	public function setOptions(array $options, $reset = false) {
		$this->options = $reset ? $options : array_merge($this->options, $options);
		return $this;
	}

	/**
	 * Set one option
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 * 
	 */
	public function setOption($name, $value) {
		$this->options[$name] = $value;
		return $this;
	}

	/**
	 * Get currently set options
	 * 
	 * @return array
	 * 
	 */
	public function getOptions() {
		return $this->options;
	}
	
	/**
	 * Get instance of less parser
	 * 
	 * This returns the same instance every time unless you $reset
	 * 
	 * @param bool $reset
	 * @return \Less_Parser
	 * 
	 */
	public function parser($reset = false) {
		if(is_array($reset)) { 
			// options specified with parser call, undocumented, for compatibility with Bernhard's version
			$this->setOptions($reset); 
			$reset = false;
		}
		if(!$reset && $this->parser) return $this->parser;
		if(!class_exists('\Less_Parser', false)) {
			require_once(__DIR__ . '/wikimedia/less.php/lib/Less/Autoloader.php');
			\Less_Autoloader::register();
		}
		$this->parser = new \Less_Parser($this->options);
		return $this->parser;
	}

	/**
	 * Add a LESS file to parse 
	 * 
	 * @param string $file
	 * @param string $url Optional root URL of the file
	 * @return self
	 * @throws \Less_Exception_Parser
	 * 
	 */
	public function parseFile($file, $url = '') {
		$this->parser()->parsefile($file, $url);
		return $this;
	}

	/**
	 * Add a LESS file to parse (alias of parseFile method)
	 * 
	 * @param string $file
	 * @return self
	 * @throws \Less_Exception_Parser
	 * 
	 */
	public function addFile($file) {
		return $this->parseFile($file);
	}

	/**
	 * Add multiple LESS files to parse
	 *
	 * @param array $files
	 * @return self
	 * @throws \Less_Exception_Parser
	 *
	 */
	public function addFiles(array $files) {
		foreach($files as $file) {
			$this->addFile($file);
		}
		return $this;
	}

	/**
	 * Get compiled CSS
	 * 
	 * @return string
	 * @throws \Exception
	 * 
	 */
	public function getCss() {
		return $this->parser()->getCss();
	}

	/**
	 * Save to CSS file
	 * 
	 * @param string $file
	 * @param array $options
	 *  - `css` (string|null): CSS to save or omit to save CSS compiled from added .less files.
	 *  - `replacements` (array): Associative array of [ 'find' => 'replace' ] for saved CSS. 
	 * @return int|bool Number of bytes written or boolean false on fail
	 * @throws WireException
	 * 
	 */
	public function saveCss($file, array $options = array()) {
		$defaults = array(
			'css' => null, 
			'replacements' => array(),
		);
		$options = array_merge($defaults, $options);
		$files = $this->wire()->files;
		$file = $files->unixFileName($file);
		$css = $options['css'];
		if(empty($css)) $css = $this->parser()->getCss();
		if(empty($css)) return false;
		if(!empty($options['replacements'])) {
			$a = $options['replacements'];
			$css = str_replace(array_keys($a), array_values($a), $css);
		}
		return $files->filePutContents($file, $css);
	}

	/**
	 * Returns LESS parser compatible with lessphp API (https://github.com/leafo/lessphp)
	 * 
	 * Note: this returns a new instance on every call. Whatever you do with lessc is 
	 * separate from the methods in this module, so if using lessc you should only use the
	 * returned lessc instance and not the API of this module. 
	 * 
	 * @return \lessc
	 * 
	 */
	public function lessc() {
		if(!class_exists('\lessc')) {
			require_once(__DIR__ . '/wikimedia/less.php/lessc.inc.php'); 
		}
		return new \lessc();
	}
}
