<?php

namespace CoreWine\View;

use CoreWine\Http\Response\Response as BasicResponse;

class Response extends BasicResponse{

	/**
	 * Set content
	 */
	public function sendBody(){

		foreach($GLOBALS as $n => $k){
			$$n = $k;
		}

		$s = Engine::startRoot();

		
		ob_start();
		try{
			include $this -> getBody();
			$content = ob_get_contents();
			ob_end_clean();
			echo $content;
		}catch(\Exception $e){
			ob_end_clean();
			throw $e;
		}

		Engine::endRoot();
	}
}