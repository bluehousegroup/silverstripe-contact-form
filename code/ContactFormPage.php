<?php

class ContactFormPage extends Page {
  

  static $db = array (
    'To' => 'Varchar(255)',
    'Subject' => 'Varchar(255)',
    'IntroText' => 'Text',    
    'SuccessMessage' => 'HTMLText'
  );
  
  
  static $defaults = array (
    'To' => 'you@example.com',
    'Subject' => 'New contact form',
    'IntroText' => 'A user has submitted a new contact form from your website. His/her information appears below.',
    'SuccessMessage' => 'Thank you for submitting the contact form!'
  );
  


  public function getCMSFields() {
    $f = parent::getCMSFields();
    $f->addFieldsToTab("Root.ContactForm", array(
      new TextField('To','Send form to (comma separated email addresses)'),
      new TextField('Subject','Subject of email'),
      new TextareaField('IntroText','Email intro text'),
      new HtmlEditorField('SuccessMessage','Success message')
    ));
    
    return $f;
  }
  
  
}




class ContactFormPage_Controller extends Page_Controller {
  

  static $allowed_actions = array (
  	'Form'
  );
  

  public function Form() {
    return ContactForm::create("Form", $this->To, $this->Subject)
      ->setSuccessMessage($this->SuccessMessage)
      ->setIntroText($this->IntroText);
  }


}