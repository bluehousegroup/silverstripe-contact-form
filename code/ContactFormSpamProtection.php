<?php


class ContactFormSpamProtection extends Object {



	protected $components;



	protected $proxy;


	public function __construct() {
		$this->components = ArrayList::create(array());
		foreach(func_get_args() as $arg) {
			if($arg instanceof ContactFormSpamProtector) {
				$this->add($arg);
			}
		}
		return $this;
	}




	public function getByType($type) {
		foreach($this->components as $c) {
			if($c->class == $type) {
				return $c;
			}
		}
	}



	public function add(ContactFormSpamProtector $spam) {
		$spam->setContainer($this);
		$this->components->push($spam);		
		return $this;
	}



	public function getComponents() {
		return $this->components;
	}



	public function setProxy(ContactForm $f) {
		$this->proxy = $f;		
		return $this;
	}



}