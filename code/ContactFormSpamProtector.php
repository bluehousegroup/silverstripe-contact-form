<?php


abstract class ContactFormSpamProtector extends Object {


	protected $container;



	public function setContainer(ContactFormSpamProtection $container) {
		$this->container = $container;
		return $this;
	}



	public function isSpam($data, $form) {	}



	public function getMessage() {  }


	public function addToProxy(ContactForm $proxy) { }
}