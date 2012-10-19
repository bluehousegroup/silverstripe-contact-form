<?php



class SimpleQuestionSpamProtector extends ContactFormSpamProtector {



	public static $forgiving = true;



	protected $questions = array ();



	protected $heading = "";



	public function getMessage() {
		return _t('ContactForm.SIMPLEQUESTIONFAIL','Please enter a valid response to the spam question.');
	}



	public function addQuestion($question, $answer) {
		$this->questions[] = array(
			'question' => $question,
			'answer'=> $answer
		);
		return $this;
	}



	public function setHeading($heading) {
		$this->heading = $heading;
		return $this;
	}




	public function addToProxy(ContactForm $proxy) {
		if(sizeof($this->questions)) {
			$rand = rand(0, sizeof($this->questions)-1);
			$q = $this->questions[$rand];
			$name = "SimpleQuestion_{$rand}";
			$proxy->addField(HeaderField::create($this->heading))
				  ->addField(TextField::create($name, $q['question']));		
			$proxy->addOmittedField($name);
		}
	}



	public function isSpam($data, $form) {
		foreach($data as $key => $val) {
			if(substr($key, 0, 15) == "SimpleQuestion_") {
				list($dummy, $index) = explode("_", $key);				
				$q = $this->questions[$index];			
				$userAnswer = self::$forgiving ? trim(strtolower($data['SimpleQuestion_'.$index])) : $val;
				$correctAnswer = self::$forgiving ? trim(strtolower($q['answer'])) : $q['answer'];
				if($userAnswer != $correctAnswer) {
					return true;
				}
			}
		}		
		return false;
	}


}