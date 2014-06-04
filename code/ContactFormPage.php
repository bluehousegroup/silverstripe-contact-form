<?php



/**
 * A page type that allows CMS configuration of a contact form
 * 
 * @author Aaron Carlino <aaron@bluehousegroup.com>
 * @package ContactForm
 */
class ContactFormPage extends Page {

	static $singular_name = 'ContactForm Page';
	static $plural_name = 'ContactForm Pages';
	static $description = 'ContactForm Page is a simple contact page extension with form.';
	static $icon = '';
  

	private static $db = array (
		'To' => 'Varchar(255)',
		'Subject' => 'Varchar(255)',
		'IntroText' => 'Text',    
		'SuccessMessage' => 'HTMLText'
	);
	
	
	private static $defaults = array (
		'To' => 'you@example.com',
		'Subject' => 'New contact form',
		'IntroText' => 'A user has submitted a new contact form from your website. His/her information appears below.',
		'SuccessMessage' => 'Thank you for submitting the contact form!'
	);
	
	
	
	public function getCMSFields() {
		$f = parent::getCMSFields();
		$f->findOrMakeTab('Root.ContactForm', _t('ContactForm.TABCONTACTFORM','ContactForm'));
		$f->addFieldsToTab("Root.ContactForm", array(
		new TextField('To', _t('ContactForm.TO','Send form to (comma separated email addresses)')),
		new TextField('Subject', _t('ContactForm.SUBJECT', 'Subject of email')),
		new TextareaField('IntroText', _t('ContactForm.INTROTEXT','Email intro text')),
		new HtmlEditorField('SuccessMessage', _t('ContactForm.SUCCESSMESSAGE','Success message'))
		));
		
		return $f;
	}

  
}




class ContactFormPage_Controller extends Page_Controller {
  

	private static $allowed_actions = array (
		'Form'
	);
	
	
	
	/** 
	 * Creates a {@link ContactForm} object based on the data configured in the CMS.
	 * Note: does not render the form. Returns the proxy {@link ContactForm} object
	 *
	 * @return ContactForm
	 */
	public function Form() {
		return ContactForm::create("Form", $this->To, $this->Subject)
			->setSuccessMessage($this->SuccessMessage)
			->setIntroText($this->IntroText);
	}


}
