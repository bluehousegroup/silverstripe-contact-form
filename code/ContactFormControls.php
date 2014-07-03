<?php



/**
 * Decorates the page controller to provide a contact form handler on all pages
 *
 * @author Aaron Carlino <aaron@bluehousegroup.com>
 * @package ContactForm
 */
class ContactFormControls extends DataExtension {
	
	
	
	/**
	 * Handles the submission of the contact form. Checks spam and builds and sends the email
	 *
	 * @param array The form data
	 * @param Form The Form object	
	 */
	public function doContactFormSubmit($data,$form) {
		Session::set("FormData.{$form->FormName()}", $data);
		$proxy = $form->proxy;

		foreach($proxy->getSpamProtection() as $spam) {			
			if($spam->isSpam($data, $form)) {				
				$form->sessionMessage($spam->getMessage(),"bad");
				$spam->logSpamAttempt($this->owner->request);
				return $this->owner->redirectBack();
			}	
		}

		if($func = $proxy->getOnBeforeSend()) {		   
		     $result = $func($data,$form,$proxy);
		     if($result === false) {
		     	return $this->owner->redirectBack();		   
		     }
		}
	
			
		$this->sendEmail($data,$form);
		
		Session::clear("FormData.{$form->FormName()}");
		if($func = $proxy->getOnAfterSend()) {
		   $func($data, $form, $proxy);
		}

		if($proxy->getSuccessURL()) {
			return $this->owner->redirect($proxy->getSuccessURL());
		}
		else {
			if(Director::is_ajax()) {
				return new SS_HTTPResponse($proxy->getSuccessMessage());
			}
			$form->sessionMessage(strip_tags($proxy->getSuccessMessage()), 'good');
			return $this->owner->redirectBack();
		}

	

	}
	
	
	
	
	/**
	 * Sends the email, either with the native {@link Email} class or with Postmark
	 *
	 * @param array The form data
	 * @param Form The form object
	 */
	public function sendEmail($data,$form)  {
		$proxy = $form->proxy;

		$emailTo = $proxy->getToAddress();
		$emailSubject = $proxy->getMessageSubject();
		$replyTo = $proxy->getReplyTo();
		$emailTemplate = $proxy->getEmailTemplate();
		$fields = ArrayList::create(array());
		$uploadedFiles = array();

		foreach($form->Fields()->dataFields() as $field) {
			if(!in_array($field->getName(), $proxy->getOmittedFields())) {
				if($field instanceof CheckboxField) {
					$value = $field->value ? _t('ContactForm.YES','Yes') : _t('ContactForm.NO','No');
				}
				else if(class_exists("UploadifyField") && $field instanceof UploadifyField) {
					$uploadedFiles[] = $field->Value();
				}
				else if(is_array($field->Value())){
					$value = $field->Value();
				}
				else {
					$value = nl2br($field->Value());
				}
				if(is_array($value)) {
					$answers = ArrayList::create(array());
					foreach($value as $v) {
						$answers->push(ArrayData::create(array('Value' => $v)));
					}
					$answers->Checkboxes = true;
					$fields->push(ArrayData::create(array('Label' => $field->Title(), 'Values' => $answers)));
				}			
				else {
					$title = $field->Title() ? $field->Title() : $field->getName();
					$fields->push(ArrayData::create(array('Label' => $title, 'Value' => $value)));
				}
			}
		}

		$messageData = array(
			'IntroText' => $proxy->getIntroText(),
			'Fields' => $fields,
			'Domain' => Director::protocolAndHost()
		);
		Requirements::clear();
		$html = $this->owner->customise($messageData)->renderWith($emailTemplate);
		Requirements::restore();

		if($proxy->isPostmark()) {
			require_once(Director::baseFolder()."/contact_form/code/thirdparty/postmark/Postmark.php");
			$email = Mail_Postmark::compose()
				->subject($emailSubject)
				->messageHtml("$html");
			try {
				$emailArray = array_map('trim', explode(',',$emailTo));
				foreach($emailArray as $emailDestination){
					$email->addTo($emailDestination);
				}
			}
			catch(Exception $e) {
				$form->sessionMessage(_t('ContactForm.BADTOADDRESS','It appears there is no receipient for this form. Please contact an administrator.'),'bad');
				return $this->owner->redirectBack();
			}
			if($replyTo) {
				try {
					$email->replyTo($replyTo);
				}
				catch(Exception $e) {}
			}
    			
			foreach($uploadedFiles as $file_id) {
			    if($file = File::get()->byID($file_id)) {
			    	$email->addAttachment($file->getFullPath());
				}
			}		

		}
		else {
			//You can define MAIL_FROM_ADDRESS in mysite/_config.php
			$sender = null;
			if(defined('MAIL_FROM_ADDRESS')) $sender = MAIL_FROM_ADDRESS;
			$email = Email::create($sender, $emailTo, $emailSubject, $html);
			if($replyTo) {
				$email->replyTo($replyTo);
			}
			foreach($uploadedFiles as $file_id) {
			    if($file = File::get()->byID($file_id)) {
			    	$email->attachFile($file->getFullPath(), basename($file->Filename));
			    }
			}
		}
			
		$email->send();	

        foreach($uploadedFiles as $file_id) {
	        if($file = File::get()->byID($file_id)->first())
            $file->delete();
        }			
	}
	
}

