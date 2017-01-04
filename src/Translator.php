<?php


namespace CoreWine\View;

/**
 * Translate content page
 */
class Translator{

	/**
	 * Filename
	 */
	public $filename;

	/**
	 * Sub Path
	 */
	public $subPath;

	/**
	 * Relative Path
	 */
	public $relativePath;

	/**
	 * PARENT
	 */
	const PARENT_CONTENT = '{% PARENT_CONTENT %}';

	/**
	 * Return call to engine
	 *
	 * @return string
	 */
	public function getEngineCall(){
		return __NAMESPACE__."\Engine";
	}

	/**
	 * Construct
	 *
	 * @param string $filename
	 * @param string $subPath
	 * @param string $relativePath
	 * @return string
	 */
	public function __construct($filename,$subPath,$relativePath){
		$this -> filename = $filename;
		$this -> subPath = $subPath;
		$this -> relativePath = $relativePath;
	}

	/**
	 * Translate
	 *
	 * @param string $content
	 * @return string
	 */
	public function translate($content){



		$compiler = new Compiler($content);
		$compiler -> translate('@endcase*@default',"@endcase\n@default");
		$compiler -> translate('@switch(*)*@case',"@switch($1)\n@case");
		$compiler -> translate('@switch(*)*@default',"@switch($1)\n@default");
		$compiler -> translate('@endcase*@case',"@endcase\n@case");
		$compiler -> translate('@endcase*@default',"@endcase\n@default");
		$compiler -> process();
		$content = $compiler -> getContent();
		

		$compiler = new Compiler($content);

		$extends = false;

		# Temp
		if(preg_match('/@extends\(([^\}]*)\)/iU',$content,$r)){

			$compiler -> translate("@extends(".$r[1].")","<?php ".$this -> getEngineCall()."::startExtends($r[1]); ?>");

			$extends = true;

		}

		$compiler -> translate("@php(*)","<?php $1; ?>");

		# Set
		$compiler -> translate("@set(*,*)","<?php $1 = $2; ?>");

		# Comment
		$compiler -> translate("{#*#}","<?php /*$1*/ ?>");

		# Var
		$compiler -> translate("{{*}}","<?php echo $1; ?>");

		# If
		$compiler -> translate("@if(*)","<?php if($1){ ?>");
		$compiler -> translate("@else","<?php }else{ ?>");
		$compiler -> translate("@elseif(*)","<?php }else if($1){ ?>");
		$compiler -> translate("@endif","<?php } ?>");

		# Foreach
		$compiler -> translate("@foreach(*)","<?php foreach($1){ ?>");
		$compiler -> translate("@endforeach","<?php } ?>");

		# Switch
		$compiler -> translate("@switch(*)","<?php switch($1){ ?>");
		$compiler -> translate("@default","<?php default: ?>");
		$compiler -> translate("@case(*)","<?php case $1: ?>");
		$compiler -> translate("@endcase","<?php break; ?>");
		$compiler -> translate("@endswitch","<?php } ?>");

		# Switch fix (\n)

		# Embed
		$compiler -> translate("@embed(*,*)","<?php ".$this -> getEngineCall()."::startIncludes($1,$2); ?>");
		$compiler -> translate("@embed(*)","<?php ".$this -> getEngineCall()."::startIncludes($1,$1); ?>");
		$compiler -> translate("@endembed","<?php ".$this -> getEngineCall()."::endIncludes(); ?>");

		# Block
		$compiler -> translate("@block(*,*)","<?php ".$this -> getEngineCall()."::startBlock($1,$2); ?>");
		$compiler -> translate("@block(*)","<?php ".$this -> getEngineCall()."::startBlock($1); ?>");
		$compiler -> translate("@endblock","<?php ".$this -> getEngineCall()."::endBlock(); ?>");

		# Include
		$compiler -> translate("@include(*,*)","<?php ".$this -> getEngineCall()."::include($1,$2); ?>");
		$compiler -> translate("@include(*)","<?php ".$this -> getEngineCall()."::include($1); ?>");

		$compiler -> process();

		$content = $compiler -> getContent();

		if($extends)
			$content .= "<?php ".$this -> getEngineCall()."::endExtends(); ?>";

		
		

		return $content;
	}


}

?>