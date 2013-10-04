<?php



/**
 * A spam protector that prompts the user with a simple question to which any human
 * should know the answer.
 *
 * @author Aaron Carlino <aaron@bluehousegroup.com>
 * @package ContactForm
 */
class SimpleQuestionSpamProtector extends ContactFormSpamProtector {


	
	/**
	 * @var boolean If true, allow differences in case and spacing when evaluating the answer
	 */
	private static $forgiving = true;



	/**
	 * @var array A list of possible questions to ask
	 */
	protected $questions = array ();



	/**
	 * @var string A block of text that introduces the question, e.g. "Prove you're human."
	 */
	protected $heading = "";



	/**
	 * @var string The question that was asked to the user
	 */
	protected $questionGiven;



	/**
	 * @var string The answer the user provided to the question
	 */
	protected $answerReceived;



	/**
	 * Gets the message to put on the form when the answer is incorrect
	 *
	 * @return string
	 */
	public function getMessage() {
		return _t('ContactForm.SIMPLEQUESTIONFAIL','Please enter a valid response to the spam question.');
	}




	/**
	 * Adds a new question to the list of possible questions to ask on the form
	 *
	 * @param string The question
	 * @param string The answer to the question
	 * @return SimpleQuestionSpamProtector
	 */
	public function addQuestion($question, $answer) {
		$this->questions[] = array(
			'question' => $question,
			'answer'=> $answer
		);
		return $this;
	}




	/**
	 * Sets the intro text for the spam question
	 *
	 * @param string
	 * @return SimpleQuestionSpamProtector
	 */
	public function setHeading($heading) {
		$this->heading = $heading;
		return $this;
	}




	/**
	 * Sets up the spam question. Chooses a question at random and adds it to the form
	 *
	 * @param ContactForm
	 */
	public function initialize(ContactForm $proxy) {
		if(sizeof($this->questions)) {
			$rand = rand(0, sizeof($this->questions)-1);
			$q = $this->questions[$rand];
			$name = "SimpleQuestion_{$rand}";
			$proxy->addField(LabelField::create("SimpleSpamQuestion_label_{$rand}",$this->heading))
				  ->addField(TextField::create($name, $q['question']));		
			$proxy->addOmittedField($name);
		}
	}




	/**
	 * Determines if the form is spammy. Checks the answer to the random question
	 *
	 * @param array The form data
	 * @param Form The Form object
	 * @return boolean
	 */
	public function isSpam($data, $form) {
		foreach($data as $key => $val) {
			if(substr($key, 0, 15) == "SimpleQuestion_") {
				list($dummy, $index) = explode("_", $key);				
				$q = $this->questions[$index];				
				$userAnswer = self::$forgiving ? trim(strtolower($data['SimpleQuestion_'.$index])) : $val;				
				$correctAnswer = self::$forgiving ? trim(strtolower($q['answer'])) : $q['answer'];
				$this->questionGiven = $q['question'];
				$this->answerReceived = $userAnswer;			
				if($userAnswer != $correctAnswer) {
					return true;
				}
			}
		}		
		return false;
	}



	/**
	 * Logs a failed spam attempt. Records the question and the user's answer in the Notes field
	 *
	 * @param SS_HTTPRequest
	 */
	public function logSpamAttempt(SS_HTTPRequest $r) {
		$spam = $this->createSpamAttempt($r);
		$spam->Notes = "User answered: \"{$this->answerReceived}\" to question \"{$this->questionGiven}\"";
		$spam->write();
	}



}
