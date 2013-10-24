<?php


/**
 * Builds a simple contact form with spam protection. 
 * Integrates with Postmark <http://www.postmarkapp.com>
 * to send emails externally.
 *
 * Integrates with BootstrapForm {@link http://www.github.com/unclecheese/silverstripe-bootstrap-forms} when available.
 *
 * Note: Despite its name, this class does not create a {@link Form} object. Rather,
 * it is a proxy set of controls for a {@link Form}. This is mostly because the form
 * can transform into a BootstrapForm object at runtime.
 *
 * Because this class serves as a proxy to a form, it is necessary to return the object to the template
 * with the ->render() method.
 *
 * ex.
 * return ContactForm::create("MyForm", "you@example.com","Subject of message")
 *			->addField(TextField::create("YourName","Your name"))
 *			->render();
 *
 * @author Aaron Carlino <aaron@bluehousegroup.com>
 * @package ContactForm
 */			
class ContactForm extends Object {



	/**
	 * @var boolean A global setting to determine whether to use BootstrapForm when available
	 */
	protected static $use_bootstrap = true;



	/**
	 * @var boolean A global setting to determin whether to use Postmark when configured
	 */
	protected static $use_postmark = true;



	/**
	 * @var boolean If true, do not include the SilverStripe jQuery file
	 */
	protected static $jquery_included = false;



	/**
	 * @var array The default array of {@link ContactFormSpamProtector} objects
	 */
	protected static $default_spam_protection;



	/**
	 * @var boolean An instance level setting to determine whether to use BootstrapForm if available
	 */
	protected $useBootstrap = null;



	/**
	 * @var boolean An instance level setting to determin wheter to use Postmark if available
	 */
	protected $usePostmark = null;



	/**
	 * @var string The email address to which the form will be sent
	 */
	protected $toAddress = null;



	/**
	 * @var string The form field that contains the value to use as a dynamic Reply-To address
	 */
	protected $replyToField = "Email";



	/**
	 * @var string The reply-to address
	 */
	protected $replyTo;



	/**
	 * @var string The subject of the email that is sent by the form
	 */
	protected $messageSubject = null;



	/**
	 * @var array A list of validation settings for the form
	 */
	protected $validation = array ();


	
	/**
	 * @var string The name of the controller function that creates this form
	 */
	protected $formName = null;



	/**
	 * @var Form The {@link Form} object that this class manages
	 */
	protected $form = null;



	/**
	 * @var array A collection of {@link ContactFormSpamProtector} objects to use on the form
	 */
	protected $spamProtection = array ();



	/**
	 * @var function An anonymous function to call before sending the email, after spam protection is passed
	 */
	protected $onBeforeSend;



	/**
	 * @var function An anonymous function to call after sending the email.
	 */
	protected $onAfterSend;



	/**
	 * @var string A hard-coded URL to redirect to after the form is submitted
	 */
	protected $successURL;



	/**
	 * @var string A success message to display after the form has been submitted
	 */	
	protected $successMessage = null;


	
	/**
	 * @var string The template to use for the email
	 */	
	protected $emailTemplate = "ContactPageEmail";

	
	
	/**
	 * @var array A list of field names to omit from the email
	 */
	protected $omittedFields = array ();



	/**
	 * @var string The intro text to put in the email before the form data
	 */
	protected $emailIntroText = "";




	/**
	 * Sets the global setting to use or not use BootstrapForm when available
	 *
	 * @param boolean 
	 */
	public static function set_use_bootstrap($bool) {
		self::$use_bootstrap = $bool;
	}




	/**
	 * Sets the global setting that jQuery is included
	 *
	 * @param boolean
	 */
	public static function set_jquery_included($bool = true) {
		self::$jquery_included = $bool;
	}


	
	
	/**
	 * Sets the default spam protection
	 *
	 * @param array A list of {@link ContactFormSpamProtector} objects
	 */
	public static function set_default_spam_protection($p = array ()) {
		self::$default_spam_protection = $p;
	}


	
	
	/**
	 * A utility method that creates a label from a form field name
	 *
	 * @param string The form field name
	 * @return string
	 */
	public static function create_name_from_label($name) {
		// Remove everything in parentheses
		$name = preg_replace("/\([^\)]+\)/","",$name);

		// Remove anything non-alphanumeric
		$name = preg_replace('/[^A-Za-z0-9 ]/', '', $name);

		// Capitalize all the words
		$name = ucwords($name);

		// Remove all the spaces
		$name = str_replace(" ", "", $name);

		// Limit field names to 64 characters, for databases
		$name = substr($name, 0, 64);

		return $name;		
	}




	/**
	 * Constructor function for the ContactForm
	 *
	 * @param string The name of the function that creates this ContactForm object
	 * @param string The email address to which the form will send the email
	 * @param string The subject of the email
	 */
	public function __construct($name, $to, $subject) {		
		$this->toAddress = $to;
		$this->messageSubject = $subject;	
		$formClass = $this->isBootstrap() ? "BootstrapForm" : "Form";		
		$spam = self::$default_spam_protection ? self::$default_spam_protection : array();
		$this->setSpamProtection($spam);

		$this->form = Object::create($formClass,
			Controller::curr(),
			$name,
			FieldList::create(),
			FieldList::create(
				FormAction::create("doContactFormSubmit", _t('ContactForm.SEND','Send'))
			),
			RequiredFields::create()
		);
		$this->form->proxy = $this;
		$this->addOmittedField("SecurityID");
		parent::__construct();
		return $this;
	}



	
	/**
	 * Adds a field to the contact form
	 *
	 * @param mixed The form field. Can be in the form of a string, e.g. "Who are you?//Text"
	 * @return ContactForm
	 */
	public function addField($field) {
		if(!$field instanceof FormField) {			
			return $this->addFromString((string) $field);
		}
		else if($field instanceof EmailField) {
			$this->updateValidation($field->getName(), array ('email' => true));
		}		
		$this->form->Fields()->push($field);
		return $this;
	}




	/**
	 * Adds an omitted field to the list
	 *
	 * @param string The field name
	 * @return ContactForm
	 */
	public function addOmittedField($name) {
		$this->omittedFields[] = $name;
		return $this;
	}




	/**
	 * Adds a required field to the form
	 *
	 * @param mixed The form field to add.
	 * @param array The parameters for validation, ex:
	 *		array (
	 *			'email' => true,
	 *			'maxlength' => 35
	 *		);
	 * @param string The validation error message
	 * @return ContactForm
	 */
	public function addRequiredField($field, $message = null, $params = array()) {
		$params['required'] = true;
		$field->setTitle($field->Title() . " <span class=\"required\">*</span>");
		$this->addField($field);
		$this->form->getValidator()->addRequiredField($field->getName());
		if(!$message) {
			$message = sprintf(_t('ContactForm.FIELDISREQUIRED','"%s" is required'),$field->Title());
		}
		$params['message'] = $message;
		$this->updateValidation($field->getName(), $params);
		return $this;
	}

	
	public function setRequiredFields($fieldArray){
		if(is_array($fieldArray)){
			foreach($fieldArray as $fieldName => $validationMessage){
				$field = $this->form->Fields()->dataFieldByName($fieldName);
				if($field){
					$this->form->getValidator()->addRequiredField($field->getName());
					$params['required'] = true;
					if(!$validationMessage){
						$params['message'] = sprintf(_t('ContactForm.FIELDISREQUIRED','"%s" is required'),$field->Title());
					}else{
						$params['message'] = $validationMessage;
					}
					$this->updateValidation($field->getName(), $params);	
				}else{
					throw new Exception('No field found for "'.$fieldName.'"');
				}
			}
		}else{
			throw new Exception('setRequiredFields is expecting an array.  Use setRequredField if you modifing a single field.');
		}
		return $this;
	}

	public function setRequiredField($fieldName, $message = null){
		$field = $this->form->Fields()->datafieldByName($fieldName);
		if($field){
			$this->form->getValidator()->addRequiredField($field->getName());
			$params['required'] = true;
			if($message){
				$params['message'] = $message;
			}else{
				$params['message'] = sprintf(_t('ContactForm.FIELDISREQUIRED','"%s" is required'),$field->Title());
			}
			$this->updateValidation($field->getName(), $params);
			return $this;
		}else{
			throw new Exception('No field found for "'.$fieldName.'"');
		}
		
	}


	/**
	 * Adds a list of fields to the form. Accepts an arbitrary number of arguments.
	 *
	 * @return ContactForm
	 */
	public function addFields() {
		$args = func_get_args();
		$fields = (sizeof($args) == 1 && is_array($args[0])) ? $args[0] : func_get_args();
		foreach($fields as $f) {
			$this->addField($f);
		}
		return $this;
	}




	/**
	 * Given the global setting and instance level setting, determine if this form will use BootstrapForm
	 *
	 * @return boolean
	 */
	public function isBootstrap() {
		if(!class_exists("BootstrapForm")) return false;

		if($this->useBootstrap !== null) {
			return $this->useBootstrap;
		}
		return self::$use_bootstrap;
	}
	

	

	/**
	 * Given the global and instance-level settings, determine if this form will use Postmark
	 *
	 * @return boolean
	 */
	public function isPostmark() {
		if(!defined("POSTMARKAPP_API_KEY")) return false;

		if($this->usePostmark !== null) {
			return $this->usePostmark;
		}
		return self::$use_postmark;
	}



	

	/**
	 * Adds a form field given a string, e.g. "What is your name?//Text"
	 *
	 * @param string The text description of the field to add
	 * @return ContactForm
	 */
	public function addFromString($str) {	
		$parts = explode("//", $str);
		if(sizeof($parts) != 2) continue;
		list($label, $type) = $parts;
		$required = (substr($label, -1) == "*");
		$name = self::create_name_from_label($label);
		if(!class_exists($type) || !is_subclass_of($type, "FormField")) {
			$type .= "Field";
		}
		if(is_subclass_of($type, "FormField")) {
			if($required) {
				$this->addRequiredField(Object::create($type, $name, substr_replace($label ,"",-1)));
			}
			else {
				$this->addField(Object::create($type, $name, $label));	
			}
		}

		return $this;
	}




	/**
	 * Sets the form to use or not use Boostrap. If the form is already created, make sure to 
	 * create a new one if the class is different
	 *
	 * @param boolean
	 * @return ContactForm
	 */
	public function setUseBootstrap($bool) {
		$newClass = $bool ? "BootstrapForm" : "Form";
		if($this->form->class != $newClass) {
			$form = Object::create($newClass,
				Controller::curr(),
				$this->form->getName(),
				$this->form->getFields(),
				$this->form->getActions(),
				$this->form->getValidator()
			);
			$this->form = $form;
		}
		return $this;
	}



	
	/**
	 * Gets the {@link Form} object that this object is managing
	 *
	 * @return Form
	 */
	public function getForm() {
		return $this->form;
	}




	/**
	 * Sets the list of {@link ContactFormSpamProtector} objects to use on this form
	 *
	 * @param array A list of {@link ContactFormSpamProtector} objects
	 * @return ContactForm
	 */
	public function setSpamProtection($s) {
		$this->spamProtection = $s;		
		return $this;
	}




	/**
	 * Gets the spam protection list
	 *
	 * @return array
	 */
	public function getSpamProtection() {
		return $this->spamProtection;
	}




	/**
	 * Adds a {@link ContactFormSpamProtector} object to the form
	 *
	 * @param ContactFormSpamProtector
	 * @return ContactForm
	 */
	public function addSpamProtector(ContactFormSpamProtector $s) {
		if(!$s instanceof ContactFormSpamProtector) {
			user_error("ContactForm::addSpamProtector() -- Must be passed an instance of ContactFormSpamProtector", E_USER_ERROR);
		}
		$this->spamProtection[$s->class] = $s;
		return $this;
	}




	/**
	 * Adds an anonymous function to call before the form sends
	 *
	 * @param function
	 * @return ContactForm
	 */
	public function setOnBeforeSend($func) {
		$this->onBeforeSend = $func;
		return $this;
	}



	
	/**
	 * Gets the function to call before sending the form
	 *
	 * @return function
	 */
	public function getOnBeforeSend() {
		return $this->onBeforeSend;
	}



	
	/**
	 * Sets an anonymous function to call after the form sends
	 *
	 * @param function
	 * @return ContactForm
	 */
	public function setOnAfterSend($func) {
		$this->onAfterSend = $func;
		return $this;
	}




	/**
	 * Gets the function to call after the form sends
	 *
	 * @return function
	 */
	public function getOnAfterSend() {
		return $this->onAfterSend;
	}




	/**
	 * Gets the URL to redirect to after form is successfully submitted
	 *
	 * @return string
	 */
	public function getSuccessURL() {
		return $this->successURL;
	}
	
	
	
	
	/**
	 * Sets the URL to redirect to after form is successfully submitted
	 * 
	 * @param string
	 * @return ContactForm
	 * */
	public function setSuccessURL($url) {
		$this->successURL = $url;
		return $this;
	}




	/**
	 * Sets the message to display when the form is successfully submitted
	 *
	 * @param string
	 * @return ContactForm
	 */
	public function setSuccessMessage($message) {
		$this->successMessage = $message;
		return $this;
	}




	/**
	 * Gets the success message for the form
	 *
	 * @return string
	 */
	public function getSuccessMessage() {
		return $this->successMessage;
	}




	/**
	 * Sets the email address to which the form will be sent
	 *
	 * @param string
	 * @return ContactForm
	 */
	public function setToAddress($to) {
		$this->toAddress = $to;
		return $this;
	}



	/**
	 * Gets the address to which the form will be sent
	 *
	 * @return string
	 */
	public function getToAddress() {
		return $this->toAddress;
	}



	/**
	 * Sets the subject of the email
	 *
	 * @param string
	 * @return ContactForm
	 */
	public function setMessageSubject($subject) {
		$this->messageSubject = $subject;
		return $this;
	}



	/**
	 * Gets the subject of the email
	 *
	 * @return string
	 */
	public function getMessageSubject() {
		return $this->messageSubject;
	}



	
	/**
	 * Sets the template to use for the email
	 *
	 * @param string A name of a .ss template
	 * @return ContactForm
	 */
	public function setEmailTemplate($template) {
		$this->emailTemplate = $template;
		return $this;
	}




	/**
	 * Gets the template to use for the email
	 *
	 * @return string
	 */
	public function getEmailTemplate() {
		return $this->emailTemplate;
	}



	/**
	 * Sets the field to use for for dynamic reply-to
	 *
	 * @param string
	 * @return ContactForm
	 */
	public function setReplyToField($field) {
		$this->replyToField = $field;
		return $this;
	}



	/**
	 * Sets a hard-coded reply-to email address
	 *
	 * @param string
	 * @return ContactForm
	 */
	public function setReplyTo($address) {
		$this->replyTo = $address;
		return $this;
	}




	/**
	 * Gets the fields to omit from the email
	 *
	 * @return array
	 */
	public function getOmittedFields() {
		return $this->omittedFields;
	}




	/**
	 * Gets the reply-to email address given the dynamic and hard-coded options
	 *
	 * @return string
	 */
	public function getReplyTo() {
		if($this->replyTo) return $this->replyTo;
		if($this->replyToField) {
			if($field = $this->form->Fields()->dataFieldByName($this->replyToField)) {
				return $field->Value();
			}
		}
	}



	
	/**
	 * Sets the text to display in the email before all the form data
	 *
	 * @param string
	 * @return ContactForm
	 */
	public function setIntroText($text) {
		$this->emailIntroText = $text;
		return $this;
	}




	/**
	 * Gets the intro text for the email
	 *
	 * @return string
	 */
	public function getIntroText() {
		return $this->emailIntroText;
	}




	/**
	 * Updates the validation for a given field by merging new settings with old
	 *
	 * @param string The field name
	 * @param array A list of parameters to add
	 * @return ContactForm
	 */
	public function updateValidation($field, $params) {
		if(isset($this->validation[$field])) {
			$oldParams = $this->validation[$field];
			$newParams = array_merge($oldParams, $params);
			$this->validation[$field] = $newParams;
		}
		else {
			$this->validation[$field] = $params;
		}
		return $this;
	}





	/**
	 * Gets the JavaScript to use with jQuery validation
	 *
	 * @return string
	 */
	protected function getValidationJS() {
		$js = "
		(function(\$) {
		\$(function() {
			\$('#{$this->form->FormName()}').validate({
				rules: {";
						foreach($this->validation as $name => $params) {
							$js .= "\n\t\t\t\t\t".$name.": {\n";
							foreach($params as $index => $key) {
								if($index == "message") continue;
								if(is_bool($key)) {
									$key = $key ? "true" : "false";
								}
								elseif(is_string($key)) {
									$key = "\"{$key}\"";
								}
								$js .= "\t\t\t\t\t\t{$index}: {$key},\n";
							}
							$js .= "\t\t\t\t\t},\n";
						}

						$js .= "
				},
				messages: {";
						foreach($this->validation as $name => $params) {
							if(!isset($params['message'])) {
								$params['message'] = sprintf(_t('ContactForm.FIELDISREQUIRED','"%s" is required'),$name);
							}
							$js .= "\n\t\t\t\t\t".$name.": \"" . addslashes($params['message'])."\",\n";				
						}
						$js .= "
				}
			});
		})
		})(jQuery);";

		return $js;		
	}




	/**
	 * Renders the {@link Form} object that is managed by the ContactForm.
	 * Includes dependencies and sets up the spam
	 *
	 * @return Form
	 */
	public function render() {
		if(ContactFormSpamProtector::ip_is_locked()) {
			return Controller::curr()->httpError(400);
		}
		foreach($this->spamProtection as $spam) {
			$spam->initialize($this);
		}	
		if(!empty($this->validation)) {
			if(!self::$jquery_included) {
				Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
			}
			Requirements::javascript("contact_form/javascript/validation.js");			
			Requirements::customScript($this->getValidationJS());

		}
		if($data = Session::get("FormData.{$this->form->FormName()}")) {
			$this->form->loadDataFrom($data);
		}				
		return $this->form;
	}
}
