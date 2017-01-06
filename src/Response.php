<?php

namespace CoreWine\View;

use CoreWine\Http\Response\Response as BasicResponse;

use CoreWine\View\Exceptions\IncludeNotFoundException;

class Response extends BasicResponse{

	/**
	 * Set content
	 */
	public function sendBody(){

		foreach($GLOBALS as $n => $k){
			$$n = $k;
		}


		$s = Engine::startRoot();
		try{
			include $this -> getBody();
			Engine::endRoot();



		}catch(IncludeNotFoundException $e){
			ob_end_clean();
			$file = $e -> getTrace()[1];
			$filename = basename($file['file']);
			$filename = Engine::getFileFromStorage($filename);
			throw new IncludeNotFoundException($e -> getMessage()." in view: ".$filename.".html in {$file['line']}");

		}catch(\Exception $e){

			ob_end_clean();

			throw $e;

		}


	}
}