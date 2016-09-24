<?php


namespace CoreWine\View;

/**
 * Compiler
 */
class Compiler{

	protected $content;

	protected $replaces = [];

	public function __construct($content){
		$this -> parser = new Parser($content);
	}

	public function getContent(){
		return $this -> content;
	}

	public function process(){
		$this -> parser -> parse($this -> replaces);
		$this -> content = $this -> parser -> getContent();
	}

	public function translate($from,$to){
		$this -> replaces[$from] = $to;

	}
}

?>