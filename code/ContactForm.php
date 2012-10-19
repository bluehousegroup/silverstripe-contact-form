<?php


class ContactForm extends Object {


	protected static $use_bootstrap = true;



	protected static $use_postmark = true;



	protected static $jquery_included = false;



	protected static $default_spam_protection;



	
	protected $useBootstrap = null;


	protected $usePostmark = null;


	protected $toAddress = null;


	protected $replyToField = "Email";


	protected $replyTo;



	protected $messageSubject = null;



	protected $validation = array ();



	protected $formName = null;



	protected $form = null;


	protected $spamProtection;



	protected $onBeforeSend;



	protected $onAfterSend;


	protected $successURL;



	protected $successMessage = null;



	protected $emailTemplate = "ContactPageEmail";


	protected $omittedFields = array ();


	protected $emailIntroText = "";


	public static function set_use_bootstrap($bool) {
		self::$use_bootstrap = $bool;
	}



	public static function set_jquery_included($bool = true) {
		self::$jquery_included = $bool;
	}



	public static function set_default_spam_protection(ContactFormSpamProtection $p) {
		self::$default_spam_protection = $p;
	}



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




	public function __construct($name, $to, $subject) {		
		$this->toAddress = $to;
		$this->messageSubject = $subject;	
		$formClass = $this->isBootstrap() ? "BootstrapForm" : "Form";		
		$spam = self::$default_spam_protection ? self::$default_spam_protection : ContactFormSpamProtection::create();		
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



	public function addOmittedField($name) {
		$this->omittedFields[] = $name;
		return $this;
	}



	public function addRequiredField($field, $params = array(), $message = null) {
		$params['required'] = true;
		$this->addField($field);
		$this->form->getValidator()->addRequiredField($field->getName());
		if(!$message) {
			$message = sprintf(_t('ContactForm.FIELDISREQUIRED','"%s" is required'),$field->Title());
		}
		$params['message'] = $message;
		$this->updateValidation($field->getName(), $params);
		return $this;
	}



	public function addFields() {
		$args = func_get_args();
		$fields = (sizeof($args) == 1 && is_array($args[0])) ? $args[0] : func_get_args();
		foreach($fields as $f) {
			$this->addField($f);
		}
		return $this;
	}




	public function isBootstrap() {
		if(!class_exists("BootstrapForm")) return false;

		if($this->useBootstrap !== null) {
			return $this->useBootstrap;
		}
		return self::$use_bootstrap;
	}



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



	public function getForm() {
		return $this->form;
	}



	public function getSpamProtector() {
		return $this->spamProtection;
	}



	public function setSpamProtection(ContactFormSpamProtection $s) {
		$this->spamProtection = $s;
		$this->spamProtection->setProxy($this);	
		return $this;
	}



	public function addSpamProtector(ContactFormSpamProtector $s) {
		$this->spamProtection->add($s);
		return $this;
	}



	public function setOnBeforeSend($func) {
		$this->onBeforeSend = $func;
		return $this;
	}



	public function getOnBeforeSend() {
		return $this->onBeforeSend;
	}



	public function setOnAfterSend($func) {
		$this->onAfterSend = $func;
		return $this;
	}



	public function getOnAfterSend() {
		return $this->onAfterSend;
	}



	public function getSuccessURL() {
		return $this->successURL;
	}



	public function setSuccessMessage($message) {
		$this->successMessage = $message;
		return $this;
	}


	public function getSuccessMessage() {
		return $this->successMessage;
	}



	public function setToAddress($to) {
		$this->toAddress = $to;
		return $this;
	}



	public function getToAddress() {
		return $this->toAddress;
	}



	public function setMessageSubject($subject) {
		$this->messageSubject = $subject;
		return $this;
	}



	public function getMessageSubject() {
		return $this->messageSubject;
	}



	public function setEmailTemplate($template) {
		$this->emailTemplate = $template;
		return $this;
	}



	public function getEmailTemplate() {
		return $this->emailTemplate;
	}



	public function setReplyToField($field) {
		$this->replyToField = $field;
		return $this;
	}


	public function setReplyTo($address) {
		$this->replyTo = $address;
		return $this;
	}



	public function getOmittedFields() {
		return $this->omittedFields;
	}



	public function getReplyTo() {
		if($this->replyTo) return $this->replyTo;
		if($this->replyToField) {
			if($field = $this->form->Fields()->dataFieldByName($this->replyToField)) {
				return $field->Value();
			}
		}
	}



	public function setIntroText($text) {
		$this->emailIntroText = $text;
		return $this;
	}



	public function getIntroText() {
		return $this->emailIntroText;
	}



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




	public function isPostmark() {
		if(!defined("POSTMARKAPP_API_KEY")) return false;

		if($this->usePostmark !== null) {
			return $this->usePostmark;
		}
		return self::$use_postmark;
	}




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
							$js .= "\n\t\t\t\t\t".$name.": \"" . addslashes($params['message'])."\",\n";				
						}
						$js .= "
				}
			});
		})
		})(jQuery);";

		return $js;		
	}

	public function render() {
		foreach($this->getSpamProtector()->getComponents() as $spam) {
			$spam->addToProxy($this);
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