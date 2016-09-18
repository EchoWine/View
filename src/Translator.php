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

		$content = $this -> t_block($content);
		$content = $this -> t_include($content);
		$content = $this -> t_comments($content);
		// $content = $this -> t_array($content);
		$content = $this -> t_switch($content);
		$content = $this -> t_if($content);
		$content = $this -> t_for($content);
		$content = $this -> t_print($content);

		return $content;
	}

	/**
	 * Translate comments
	 *
	 * @param string $content
	 * @return string
	 */
	public function t_comments($content){

		$content = preg_replace('/{# (\s|.*)#}/iU','<?php /* $1 */; ?>',$content);
		return $content;
	}


	/**
	 * Translate block
	 *
	 * @param string $content
	 * @return string
	 */
	public function t_block($content){

		if(preg_match('/@extends\(([^\}]*)\)/iU',$content,$r)){

			$content = str_replace('@extends('.$r[1].")","<?php ".$this -> getEngineCall()."::startExtends($r[1]); ?>",$content);

			$content .= "<?php ".$this -> getEngineCall()."::endExtends(); ?>";

		}

		$content = preg_replace('/@embed\(([^\s]*),([^\}]*)\)/iU','<?php '.$this -> getEngineCall().'::startIncludes($1,$2); ?>',$content,-1,$count_adv);
		$content = preg_replace('/@embed\(([^\}]*)\)/iU','<?php '.$this -> getEngineCall().'::startIncludes($1,$1); ?>',$content,-1,$count_basic);
		$content = preg_replace('/@endembed/iU','<?php '.$this -> getEngineCall().'::endIncludes(); ?>',$content,-1,$count_close);

		if($count_adv + $count_basic != $count_close){
			throw new Exceptions\IncludesException(
				"The count of openend includes doesn't correspond with closed one. ".
				"Opened: ".($count_adv + $count_basic)."; Closed: $count_close"
			);
		}

		
		$content = preg_replace('/{{parent}}/',Translator::PARENT_CONTENT,$content);


		$content = preg_replace('/@block\(([^\}]*)\)/iU',"<?php ".$this -> getEngineCall()."::startBlock($1); ?>",$content,-1,$count_open);
		$content = preg_replace('/@endblock/',"<?php ".$this -> getEngineCall()."::endBlock(); ?>",$content,-1,$count_close);

		if($count_open != $count_close){
			throw new Exceptions\BlockException(
				"The count of openend blocks doesn't correspond with closed one. ".
				"Opened: $count_open; Closed: $count_close"
			);
		}

		return $content;
	}

	/**
	 * Translate Include
	 *
	 * @param string $content
	 * @return string
	 */
	public function t_include($content){


		$content = preg_replace('/@include\((.*),(.*)\)/i','<?php '.$this -> getEngineCall().'::include($1,$2); ?>',$content);
		$content = preg_replace('/@include\((.*)\)/i','<?php '.$this -> getEngineCall().'::include($1); ?>',$content);

		return $content;
	}


	/**
	 * Translate array
	 *
	 * @param string $content
	 * @return string
	 */
	public function t_array($content){

		return $content;

		# array
		preg_match_all('/{{([^\}]*)}}/iU',$content,$r);
		foreach($r[0] as $n => $k){
			$i = preg_replace('/\.([\w]*)/','[\'$1\']',$k);
			$content = str_replace($k,$i,$content);
		}
		return $content;
	}

	/**
	 * Translate for
	 *
	 * @param string $content
	 * @return string
	 */
	public function t_for($content){

		# for 
		preg_match_all('/@foreach\((.*) as (.*)\)/i',$content,$r);
		
		foreach($r[0] as $n => $k){

			$content = str_replace("{$k}",'<?php foreach((array)'.$r[1][$n].' as '.$r[2][$n].'){ ?>',$content);
		}

		$content = preg_replace('/@endforeach/i','<?php } ?>',$content);

		return $content;
	}

	/**
	 * Translate switch
	 *
	 * @param string $content
	 * @return string
	 */
	public function t_switch($content){

		
		$content = preg_replace('/@switch\((.*)\)([^\@]*)@case/i',"@switch($1)\n@case",$content);
		$content = preg_replace('/@switch\((.*)\)([^\@]*)@default/i',"@switch($1)\n@default",$content);
		$content = preg_replace('/@endcase([^\@]*)@case/i',"@endcase\n@case",$content);
		$content = preg_replace('/@endcase([^\@]*)@default/i',"@endcase\n@default",$content);
		

		# switch
		preg_match_all('/\@switch\((.*)\)/i',$content,$r);
	
		foreach($r[0] as $n => $k){
			$content = str_replace($k,'<?php switch('.$r[1][$n].'){ ?>',$content);
		}

		$content = preg_replace('/\@default/i','<?php default: ?>',$content);
		preg_match_all('/\@case\((.*)\)/i',$content,$r);
	
		foreach($r[0] as $n => $k)
			$content = str_replace($k,'<?php case '.$r[1][$n].': ?>',$content);


		$content = preg_replace(
			[
				'/@endswitch/i',
				'/@endcase/i',
			],
			[
				'<?php } ?>',
				'<?php break; ?>',
			],
			$content
		);

		return $content;
	}

	/**
	 * Convert all {{$foo}} into echo $foo
	 *
	 * @param string $content
	 * @return string
	 */
	public function t_if($content){



		# if
		$content = preg_replace('/\@if\((.*)\)/i','<?php if($1){ ?>',$content);
		
	
		
		# else if
		preg_match_all('/\@elseif\((.*)\)/i',$content,$r);
	
		foreach($r[0] as $n => $k)
			$content = str_replace($k,'<?php }else if('.$r[1][$n].'){ ?>',$content);


		$content = preg_replace(
			[
				'/@endforeach/i',
				'/@endif/i',
				'/@else/i',
			],
			[
				'<?php } ?>',
				'<?php } ?>',
				'<?php }else{ ?>'
			],
			$content
		);

		return $content;
	}

	/**
	 * Convert all {{$foo}} into echo $foo
	 *
	 * @param string $content
	 * @return string
	 */
	public function t_print($content){

		# variables
		preg_match_all('/{{([^\}]*)}}/iU',$content,$r);
		foreach($r[1] as $n => $k){

			# Count row
			preg_match_all('/\n/',explode($k,$content)[0],$r);
			$r = count($r[0])+1;

			$v = preg_replace('/\.([\w]*)/','',$k);

			# Check if defined
			/*if(!in_array($v,self::$contenthecked) && !isset($GLOBALS[$v])){
				$e = new stdClass();
				$e -> message = "Undefined variable {$v}";
				$e -> row = $r;
				$e -> file = basename($f);
				self::$error[] = $e;
			}*/

			$content = str_replace('{{'.$k.'}}','<?php echo '.$k.'; ?>',$content);
		}

		return $content;

	}
}

?>