<?php

Object::add_extension('Page_Controller','ContactFormControls');


ContactForm::set_default_spam_protection(array(
		SimpleQuestionSpamProtector::create()
			->setHeading("Prove you're human by answering a simple question")
			->addQuestion("Is fire hot or cold?", "hot")
			->addQuestion("What color is a stop sign?", "red")
			->addQuestion("Is water wet or dry?","wet")
			->addQuestion("Is the world flat?", "no")
			->addQuestion("What animal says \"Meow?\"", "cat"),

		HoneyPotSpamProtector::create()
			->setName("WhatDoYouThink")
));

define('POSTMARKAPP_API_KEY','b54f74bf-6cc1-4057-9c50-e2f73f398353');
define('POSTMARKAPP_MAIL_FROM_ADDRESS', 'support@bluehousegroup.com');