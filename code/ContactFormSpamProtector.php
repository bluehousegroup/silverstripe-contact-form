<?php



/**
 * An abstract class that defines a spam protection plugin to the {@link ContactForm} class.
 * Each decendent should define a isSpam() method to check the form data and determine if the
 * input is spammy.
 *
 * @author Aaron Carlino <aaron@bluehousegroup.com>
 * @package ContactForm
 */
abstract class ContactFormSpamProtector extends Object {


	
	/**
	 * @var boolean If true, log failed spam questions to the database
	 */
	protected static $log_spam_failures = true;


	
	
	/**
	 * @var int The number of times a spammer can fail before the form should no longer be rendered
	 *			A value of 0 means there is no limit.
	 */
	protected static $spam_failure_limit = 10;



	/**
	 * If true, log the spam failures to the database
	 *
	 * @param boolean
	 */
	public static function set_log_spam_failures($bool) {
		self::$log_spam_failures = $bool;
	}


	
	/**
	 * Sets the number of tolerable spam failures before the form stops being rendered
	 *
	 * @param int
	 */
	public static function set_spam_failure_limit($num) {
		self::$spam_failure_limit = $num;
	}




	/**
	 * Determine if an IP address is blocked by looking it up in the database and comparing to
	 * $spam_failure_limit
	 *
	 * @return boolean
	 */
	public static function ip_is_locked() {
		if(self::$log_spam_failures && self::$spam_failure_limit > 0) {
			$ip = Controller::curr()->request->getIP();
			return ContactFormSpamAttempt::get()->filter(array(
				'IPAddress' => $ip
			))->count() >= self::$spam_failure_limit;
		}
		return false;

	}




	/**
	 * Determine if the form data is spammy
	 *
	 * @param array The form data
	 * @param Form The Form object
	 * @return boolean
	 */
	public function isSpam($data, $form) {	}




	/**
	 * Gets the message to return to the form when the spam question is failed
	 *
	 * @return string	
	 */
	public function getMessage() {  }




	/**
	 * Initialize the spam protector, e.g. add fields to the form, load any requirements
	 *
	 * @param ContactForm
	 */
	public function initialize(ContactForm $proxy) { }




	
	/**
	 * Creates a failed spam attempt object witht the user's info
	 *
	 * @param SS_HTTPRequest
	 * @return ContactFormSpamAttempt
	 */
	public function createSpamAttempt(SS_HTTPRequest $r) {		
		$spam = ContactFormSpamAttempt::create(array(
			'IPAddress' => $r->getIP(),
			'URL' => $r->getURL(),
			'Notes' => $this->class
		));
		return $spam;
	}


	
	
	/**
	 * Logs a spam attempt to the database
	 *
	 * @param SS_HTTPRequest
	 */
	public function logSpamAttempt(SS_HTTPRequest $r) {
		$this->createSpamAttempt($r)->write();
	}
}