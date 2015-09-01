<?php



/**
 * Defines the spam protector that creates a "honey pot" on a form.
 * Honey pots are form fields that are hidden from the user's viewport. If they contain data
 * when the form is handled, the user can be identified as a bot.
 *
 * @author Aaron Carlino <aaron@bluehousegroup.com>
 * @package ContactForm
 */
class HoneyPotSpamProtector extends ContactFormSpamProtector {



	/**
	 * @var string The name of the honey pot field
	 */
	protected $name = "ContactFormHoney";




	/**
	 * Initializes the honey pot. Loads CSS, and adds the field
	 *
	 * @param ContactForm
	 * @return HoneyPotSpamProtector
	 */
	public function initialize(ContactForm $proxy) {
		Requirements::customCSS("
			#{$this->name} {position:absolute;left:-9999em;}
		");
		$proxy->addField(TextField::create($this->name, null)->setAttribute('tabindex','-1'));
		$proxy->addOmittedField($this->name);
		return $this;
	}	




	/**
	 * Determines if the form post is spammy. If the field has value, it's probably a bot.
	 *
	 * @param array The form data
	 * @param Form The Form object
	 * @return boolean
	 */
	public function isSpam($data, $form) {
		if(isset($data[$this->name]) && !empty($data[$this->name])) {
			return true;
		}
		return false;
	}




	/**
	 * Logs a spam attempt. Saves to the Notes field what the user entered in the honey pot
	 *
	 * @param SS_HTTPRequest
	 */
	public function logSpamAttempt(SS_HTTPRequest $r) {
		$spam = $this->createSpamAttempt($r);
		$spam->Notes = "User input: " . $r->requestVar($this->name);
		$spam->write();
	}




	/**
	 * Sets the name of the honey pot
	 *
	 * @param string
	 * @return HoneyPotSpamProtector
	 */
	public function setName($name) {
		$this->name = $name;
		return $this;
	}



}
