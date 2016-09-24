<?php


namespace CoreWine\View;


class Parser{


	protected $content;

	public $vars = [];

	public function __construct($content){
		$this -> content = $content;
	}

	public function getContent(){
		return $this -> content;
	}

	public function parse($replaces){

		$replaces_from = [];
		$replaces_to = [];
		$replaces_from_p = [];
		$replaces_to_p = [];


		foreach($replaces as $from => $to){

			$part_from = explode("*",$from);

			if(count($part_from) <= 1){
				unset($replaces[$from]);
				$replaces_to_p[] = $to;
				$replaces_from_p[] = $from;
			}

		}

		$replaces_n = 0;


		$string_in = "";
		$string_last_char_opened = "";

		$buffer_word = [];
		$buffer_word_n = [];
		$part_from_n = [];


		$last_char = '';
		$escape_count = 0;

		$all_replace_buffer = [];

		$replace_buffer = [];
		$replace_to_array = [];
		$replace_to_array_n = [];


		$char_brackets = [
			'()' => 0,
			'[]' => 0,
			'{}' => 0,
		];

		$content = str_split($this -> getContent());

		for($i = 0;$i < count($content);$i++){


			if(!isset($this -> vars[$replaces_n])){
				$this -> vars[$replaces_n]['replace_to_array'] = [];
				$this -> vars[$replaces_n]['replace_to_array_n'] = 0;
				$this -> vars[$replaces_n]['all_replace_buffer'] = '';
				$this -> vars[$replaces_n]['all_replace_enabled'] = false;
				$this -> vars[$replaces_n]['replace_buffer'] = '';
				$this -> vars[$replaces_n]['part_from_n'] = 0;
				$this -> vars[$replaces_n]['part_from_n_type'] = 0;
				$this -> vars[$replaces_n]['buffer_word'] = '';
				$this -> vars[$replaces_n]['buffer_word_n'] = 0;
				$this -> vars[$replaces_n]['part_from_types'] = [];
			}

			$char = $content[$i];

			$this -> debug("\n\n");
			$this -> debug("-- CHAR: ".$char."\n");

			# Am i in a string?
			if($this -> vars[$replaces_n]['all_replace_enabled'] == true){
			# This char open/close string
			if($char == "'" || $char == '"'){

				# Escaping is considered only when string_in is true
				if($string_in === true){


					# Is actual count escaping a valid escaping ?
					$escaping = $escape_count % 2;

					# Close "string in" only if the the char is the same as the opened and not escaping

					if($escaping == false && $char == $string_last_char_opened){
						$this -> debug("-- OUT STRING --\n");
						$string_in = false;
						$string_last_char_opened = null;
					}

				}else{
					$this -> debug("-- IN STRING --\n");
					$string_in = true;
					$string_last_char_opened = $char;
				}
			

			}


			# Only when string in check for escaping
			if($string_in){

				# If char is \
				if($char == "\\"){

					# Inc escaping counter
					$escape_count++;
				}
			}

			# Reset escape_count if > 0 and char is not escaping
			if($escape_count > 0 && $char != "\\"){
				$escape_count = 0;
			}
			}

			
			# In need to count char only when i in * and not in a string
			if($this -> vars[$replaces_n]['all_replace_enabled'] == true && !$string_in){

				# I'm not in a string, so i can count all brackets
				# How this will work?
				# Keep it simple. Count the opened and closed

				if($char == '(')
					$char_brackets['()']++;

				if($char == '[')
					$char_brackets['[]']++;

				if($char == '{')
					$char_brackets['{}']++;
			}




			# Detect first in with $part_from and $part_to

			# I need to know when replace the $from in $to
			# $part_from is an array that contains all the rule 
			# $part_from[$part_from_n] contains the actual position of rule (can be multiple)
			# $part_from[$part_from_n][$buffer_word_n] Actual position of char of the rule/word


			$brackets_opened = false;

			$this -> debug("-- brackets : ".json_encode($char_brackets)."\n");
			if(($this -> vars[$replaces_n]['all_replace_enabled'] == true && !$string_in) || $this -> vars[$replaces_n]['all_replace_enabled'] == false){

				foreach($char_brackets as $brackets){

					if($brackets > 0){
						$brackets_opened = true;
						break;
					}
				}

			}

			# In need to count char only when i in * and not in a string
			if((isset($this -> vars[$replaces_n]['all_replace_enabled']) && $this -> vars[$replaces_n]['all_replace_enabled'] == true) && !$string_in){

				# I'm not in a string, so i can count all brackets
				# How this will work?
				# Keep it simple. Count the opened and closed

				if($char == ')')
					$char_brackets['()']--;

				if($char == ']')
					$char_brackets['[]']--;

				if($char == '}')
					$char_brackets['{}']--;

			}

			$this -> debug("-- is a brackets open? : ".($brackets_opened ? 1 : 0)."\n");
			if((!$brackets_opened && $this -> vars[$replaces_n]['all_replace_enabled'] == true && !$string_in) || $this -> vars[$replaces_n]['all_replace_enabled'] == false){

				$found = false;

				$this -> debug("-- vars: ".json_encode($this -> vars)."\n");


						$found_char = false;

				# Now i need to search throught all "replaces" available.
				# In order to know which one will be used to the next operation 
				# i need to do some if
				foreach($replaces as $from => $to){

					if($found_char == true)
						break;

					$part_from = explode("*",$from);

					# Take the first occurrence of set array that correspond to tue current buffer
					# If no element correspond, clear buffer
					
					$this -> debug("	-- part_from: ".json_encode($part_from)."\n");

					# I'm searching for a replace that must have a "char" with current index searched
					if(isset($part_from[$this -> vars[$replaces_n]['part_from_n']]) && isset($part_from[$this -> vars[$replaces_n]['part_from_n']][$this -> vars[$replaces_n]['buffer_word_n']])){


						# Need to search foer every $part_from_n exists

						$part_from_n = $this -> getAllElementsFromVar('part_from_n');
						for($n1 = 0; $n1 < count($part_from_n) ; $n1++){

							$k1 = $part_from_n[$n1];
							$n3 = $this -> vars[$n1]['buffer_word_n'];

							if(isset($part_from[$k1][$n3])){

								$this -> debug("	-- part from[$k1][$n3] ".$part_from[$k1][$n3]."\n");

								if($char == $part_from[$k1][$n3]){

									$this -> debug("	-- part from found: ".(substr($part_from[$k1],0,$n3+1))." == {$this -> vars[$n1]['buffer_word']}{$char}\n");

									if(substr($part_from[$k1],0,$n3+1) == $this -> vars[$n1]['buffer_word'].$char && $this -> inPart($part_from,$this -> vars[$replaces_n]['part_from_types'],$k1)){
										$this -> debug("-- FIND IN: {$part_from[$part_from_n[$n1]]}\n");
										$this -> debug("-- n1 => k1: $n1 => $k1: \n");

										$found_char = true;
										$last_from = $from;
										$last_to = $to;
										break;
									}
								}

							}
						}

					}
				}


				if(!$found_char){
					$this -> vars[$replaces_n]['buffer_word'] = '';
					$this -> vars[$replaces_n]['buffer_word_n'] = 0;

					$this -> debug("-- IS NOT EQUAL \n");

					if($this -> vars[$replaces_n]['all_replace_enabled'] == true){

						$this -> vars[$replaces_n]['replace_buffer'] .= $char;

						if(!isset($this -> vars[$replaces_n]['all_replace_buffer'])){
							$this -> vars[$replaces_n]['all_replace_buffer'] = '';
						}
						$this -> vars[$replaces_n]['all_replace_buffer'] .= $char;

						$this -> debug("-- BUFFER *: ".$this -> vars[$replaces_n]['all_replace_buffer']."\n");
						$this -> debug("-- BUFFER REPLACE: ".$this -> vars[$replaces_n]['replace_buffer']."\n");
					}else{


						foreach($replaces as $from => $to){		

							$n1 = 0;
							$k1 = 0;
							$n3 = $this -> vars[$n1]['buffer_word_n'];

							if(isset($part_from[$k1][$n3])){
								if($char == $part_from[$k1][$n1]){

									$this -> debug("-- FIND SPECIAL IN: {$part_from[$k1]}\n");
									$this -> debug("-- n1 => k1: $n1 => $k1: \n");

									$found_char = true;
									$last_from = $from;
									$last_to = $to;

								}
							}

						}

						# Ops.. This isn't the all searched.. Damm. Reset all buffer
						$this -> vars[$replaces_n]['replace_to_array'] = [];
						$this -> vars[$replaces_n]['replace_to_array_n'] = 0;
						$this -> vars[$replaces_n]['all_replace_buffer'] = '';
						$this -> vars[$replaces_n]['all_replace_enabled'] = false;
						$this -> vars[$replaces_n]['replace_buffer'] = '';
						$this -> vars[$replaces_n]['part_from_n'] = 0;
						$this -> vars[$replaces_n]['part_from_n_type'] = 0;
						$this -> vars[$replaces_n]['part_from_types'] = [];

						$replaces_n--;

						$this -> debug(" -- DEC REPLACES_N: ".$replaces_n."\n");
						if($replaces_n < 0)
							$replaces_n = 0;
					}
				}

				if($found_char){

					$this -> debug("-- CHECK:SUCCESS FIRST CHAR --\n");

					

					$part_from = explode("*",$last_from);

					# In this situation, without brackets and not in a string, i have found the next "char" that start the next part_from
					if($this -> vars[$replaces_n]['all_replace_enabled'] == true){

						$this -> vars[$replaces_n]['all_replace_enabled'] = false;

						# Now i need to check if the "char" is the next part or the first part of a second term
						# Count is ZERO, so it's the first part of a new term
						# Example fun_a(fun_b(5))
						# 'f' of fun_b rapresent a new char, so i need to increment and move

						if($k1 == 0){
							$replaces_n++;
							$this -> vars[$replaces_n]['replace_to_array'] = [];
							$this -> vars[$replaces_n]['replace_to_array_n'] = 0;
							$this -> vars[$replaces_n]['all_replace_buffer'] = '';
							$this -> vars[$replaces_n]['all_replace_enabled'] = false;
							$this -> vars[$replaces_n]['replace_buffer'] = '';
							$this -> vars[$replaces_n]['part_from_n'] = 0;
							$this -> vars[$replaces_n]['part_from_n_type'] = 0;
							$this -> vars[$replaces_n]['buffer_word'] = '';
							$this -> vars[$replaces_n]['buffer_word_n'] = 0;
							$this -> vars[$replaces_n]['part_from_types'] = [];
							$char_brackets['()']=0;
							$char_brackets['[]']=0;
							$char_brackets['{}']=0;

							$this -> debug(" -- INC REPLACES_N: ".$replaces_n."\n");

						}else{

							# Now turn off "ALL CHARACTER"
							$this -> vars[$replaces_n]['all_replace_enabled'] = false;

							# Now i can take the buffer of all_replace
							$this -> vars[$replaces_n]['replace_to_array'][$this -> vars[$replaces_n]['replace_to_array_n']++] = $this -> vars[$replaces_n]['all_replace_buffer'];
							$this -> debug(" -- SET REPLACE TO ARRAY: ".$replaces_n.": ".json_encode($all_replace_buffer)."\n");

							$this -> vars[$replaces_n]['all_replace_buffer'] = '';
						}
					}

					if($k1 == 0){

					}

					$this -> vars[$replaces_n]['part_from_n_type'] = $last_from;

					if(!isset($this -> vars[$replaces_n]['part_from_types'])){
						$this -> vars[$replaces_n]['part_from_types'] = [];
					}
					$this -> vars[$replaces_n]['part_from_types'][$this -> vars[$replaces_n]['part_from_n']] = $part_from[$k1];
					


					$this -> vars[$replaces_n]['replace_buffer'] .= $char;

					$this -> vars[$replaces_n]['buffer_word'] .= $char;

					$this -> debug("-- BUFFER WORD: ".$this -> vars[$replaces_n]['buffer_word']);
					$this -> debug("\n");

					$this -> vars[$replaces_n]['buffer_word_n']++;

					# If word has reached the full string

					$this -> debug("-- CLOSE: ".$this -> vars[$replaces_n]['buffer_word']." == ".$part_from[$this -> vars[$replaces_n]['part_from_n']]."\n");
					if($this -> vars[$replaces_n]['buffer_word'] == $part_from[$this -> vars[$replaces_n]['part_from_n']]){

						$this -> debug("-- YEAH MAN. WE ARE IN \n");
						$this -> debug("-- Uhm... Is this final ? ".(count($part_from) -1)." == ".$this -> vars[$replaces_n]['part_from_n']."\n");
						
						# Is this the end of "replaces" ?
						# for example: "fun_a()" have two parts "fun_a(",")"
						# This if check if we are in the last part
						# If we aren't in the last part we are in the middle

						if((count($part_from) - 1) == $this -> vars[$replaces_n]['part_from_n']){

							$replace_to = null;

							foreach($replaces as $tt => $kk){
								if($tt == $this -> vars[$replaces_n]['part_from_n_type']){
									$this -> debug("-- FIND TO REPLACE $tt => $kk\n");
									$tto = $kk;
								}
							}
							$replace_to = $tto;
							foreach($this -> vars[$replaces_n]['replace_to_array'] as $n => $element){
								$replace_to = str_replace(("\$".($n+1)),$element,$replace_to);
							}

							$replaces_to[] = $replace_to;
							$replaces_from[] = $this -> vars[$replaces_n]['replace_buffer'];

							if($replaces_n - 1 >= 0){

								if(!isset($this -> vars[$replaces_n - 1]['all_replace_buffer'])){
									$this -> vars[$replaces_n - 1]['all_replace_buffer'] = '';
								}
								if(!isset($this -> vars[$replaces_n - 1]['replace_buffer'])){
									$this -> vars[$replaces_n - 1]['replace_buffer'] = '';
								}

								$this -> vars[$replaces_n - 1]['all_replace_enabled'] = true;
								$this -> vars[$replaces_n - 1]['all_replace_buffer'] .= $replace_to;
								$this -> vars[$replaces_n - 1]['replace_buffer'] .= $replace_to;
							}


							$this -> debug("-- ADDED {$this -> vars[$replaces_n]['replace_buffer']} => $replace_to");

							
							unset($this -> vars[$replaces_n]);

							$char_brackets['()']=0;
							$char_brackets['[]']=0;
							$char_brackets['{}']=0;
							
								
							# I have ended, Back to zero;
							$replaces_n--;

							$this -> debug(" -- DEC REPLACES_N: ".$replaces_n."\n");
							if($replaces_n < 0)
								$replaces_n = 0;

						}else{

							# Now turn on "ALL CHARACTER". Disable only when the brackets are all to zero and the next char appear
							$this -> vars[$replaces_n]['all_replace_enabled'] = true;

							$this -> debug("-- ENABLING 'ALL CHARACTER'\n");

							# Next part
							$this -> vars[$replaces_n]['part_from_n'] += 1;
							/*
							if(!in_array(0,$part_from_n)){
								$replace_to_array[] = [];
								$replace_to_array_n[] = 0;
								$all_replace_buffer[] = '';
								$all_replace_enabled[] = false;
								$replace_buffer[] = '';
								$part_from_n[] = 0;
								$part_from_n_type[] = 0;
								$buffer_word[] = '';
								$buffer_word_n[] = 0;
							}
							*/
							$this -> vars[$replaces_n]['buffer_word'] = '';
							$this -> vars[$replaces_n]['buffer_word_n'] = 0;

						}



					}

					$found = true;

				}

				
			}else{

				if($this -> vars[$replaces_n]['all_replace_enabled'] == true){
					$this -> vars[$replaces_n]['replace_buffer'] .= $char;
						if(!isset($this -> vars[$replaces_n]['all_replace_buffer'])){
							$this -> vars[$replaces_n]['all_replace_buffer'] = '';
						}
					$this -> vars[$replaces_n]['all_replace_buffer'] .= $char;

					$this -> debug("-- BUFFER *: ".$this -> vars[$replaces_n]['all_replace_buffer']."\n");
					$this -> debug("-- BUFFER REPLACE: ".$this -> vars[$replaces_n]['replace_buffer']."\n");
				}else{

					if(!$string_in){
						
						# Ops.. This isn't the all searched.. Damm. Reset all buffer (Again x2). Wait a moment OOP
						$this -> vars[$replaces_n]['replace_to_array'] = [];
						$this -> vars[$replaces_n]['replace_to_array_n'] = 0;
						$this -> vars[$replaces_n]['all_replace_buffer'] = '';
						$this -> vars[$replaces_n]['all_replace_enabled'] = false;
						$this -> vars[$replaces_n]['replace_buffer'] = '';
						$this -> vars[$replaces_n]['part_from_n'] = 0;
						$this -> vars[$replaces_n]['part_from_n_type'] = 0;
						$this -> vars[$replaces_n]['part_from_types'] = [];


						$replaces_n--;
						$this -> debug(" -- DEC REPLACES_N: ".$replaces_n."\n");

						if($replaces_n < 0)
							$replaces_n = 0;
					}
				}	

			}


				$this -> debug("-- vars: ".json_encode($this -> vars)."\n");


			$last_char = $char;
		}

		$this -> replaceContent($replaces_from,$replaces_to);
		$this -> replaceContent($replaces_from_p,$replaces_to_p);

		return;
	}

	public function replaceContent($from,$to){
		$this -> content = str_replace($from,$to,$this -> content);
	}

	public function debug($mex){
		//echo $mex;

	}

	public function getAllElementsFromVar($name){
		$return = [];
		foreach($this -> vars as $var){
			$return[] = $var[$name];
		}
		return $return;
	}
		
	public function inPart($parts,$all,$n){
		
		$n--;

		if($n < 0)
			return true;


		for($i = 0; $i <= $n;$i++){

			if($parts[$i] != $all[$i]){
				return false;
			}

		}

		return true;
	}
}

?>