<?php


namespace CoreWine\View;

use CoreWine\Component\Debug;

/**
 * Element of structure page
 */
class Structure{

	public $name;
	public $type;
	public $childs = [];
	public $parent;
	public $prev;
	public $next;
	public $inner;
	public $content = '';
	public $overwrite = true;
	public $source;
	public $vars = [];

	public function __construct($name,$type){
		$this -> name = $name;
		$this -> type = $type;
	}

	public function setType($type){
		$this -> type = $type;
	}

	public function setVars($vars){
		$this -> vars = $vars;
	}

	public function getVars(){
		return (array)$this -> vars;
	}

	public function getName(){
		return $this -> name;
	}

	public function getType(){
		return $this -> type;
	}
	
	public function setNameNested(){

		if(!$this -> getParent())
			return;

		$this -> name = $this -> getParent() -> getName().".".$this -> name;
	}

	public function addChild($structure){
		$child = $this -> getLastChild();
		if($child != null){
			$child -> next = $structure;
			$structure -> prev = $child;
		}
		$this -> childs[$structure -> name] = $structure;

	}

	public function isRoot(){

		return in_array($this -> getType(),[Engine::STRUCTURE_ROOT,Engine::STRUCTURE_ROOT_EXTENDED]);
	}

	public function findChildByName($name){

		# Get parent extends or root


		foreach((array)$this -> childs as $child){
			Debug::add($this -> getNesting()."[findChildByName] Searching for {$name} in ".$child -> getNesting()); 

			if($grandson = $child -> findChildByName($name)){
				Debug::add($child -> getNesting()."[findChildByName] Found {$name}");

				return $grandson;
			}


			if($child -> name == $name){
				Debug::add($child -> getNesting()."[findChildByName] Found {$name}");

				return $child;
			}
		}



		return null;
	}

	public function findChildOfParentByName($name){

		$structure = $this;
		Debug::add($this -> getNesting()."[findChildByName] Getting parent extends or root: ".$structure -> getNesting()); 

		while(!$structure -> isRoot() && $structure -> getParent() && !$structure -> getParent() -> isRoot()){
			Debug::add($structure -> getNesting()."[findChildByName] Moving up "); 

			$structure = $structure -> getParent();
		}

		Debug::add($this -> getNesting()."[findChildByName] Getting parent extends or root: ".$structure -> getNesting()); 
		
		return $structure -> findChildByName($name);
	}

	public function setSource($source){
		$this -> source = $source;
	}

	public function getSource(){
		return $this -> source;
	}

	public function getLastChild(){
		return end($this -> childs);
	}

	public function getNext(){
		return $this -> next;
	}

	public function getNextOrParent(){
		return $this -> next ? $this -> next : $this -> getParent();
	}

	public function setInner($structure){
		$this -> inner = $structure;
	}

	public function getInner(){
		return $this -> inner;
	}

	public function getPrev(){
		return $this -> prev;
	}

	public function setParent($structure){
		$this -> parent = $structure;
	}

	public function getParent(){
		return $this -> parent;
	}


	public function setContent($content){
		$this -> content = $content;
	}


	public function getContent(){
		return $this -> content;
	}

	public function concatContent($content){
		$this -> content .= $content;
	}

	public function removeStructure($index){
		unset($this -> elements[$index]);
	}

	public function setOverwrite($overwrite){
		$this -> overwrite = $overwrite;
	}

	public function getOverwrite(){
		return $this -> overwrite;
	}

	public function __tostring(){
		return "Name: ".$this -> name."\n\t\tChilds: ".implode(array_keys($this -> childs),', ').";\n\n";
	}

	public function getNesting(){
		$structure = $this;
		$r = '';
		do{
			$structure = $structure -> getParent();
			if($structure)
				$r .= "-";
		}while($structure);

		switch($this -> getType()){
			case 'EXTENDS':
				return $r."@".$this -> getType()."(".$this -> getSource().",".$this -> getName().")";

			break;
			case 'BLOCK':
				return $r."@".$this -> getType()."(".$this -> getName().") ";

			break;
			default:
				return $r."@".$this -> getType()."(".$this -> getName().") [".$this -> getSource()."] ";

			break;
		}
	}

	public function getChilds(){
		return $this -> childs;
	}
}

?>