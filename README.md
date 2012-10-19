# A contact form for SilverStripe 3.0

This module allows you to create a simple contact form to add to a page type. It is highly configurable since the creation of the form happens in the page controller.

## Features
* Integrates with [Postmark](http://www.postmarkapp.com) for bullet-proof email delivery
* Integrates with the [Boostrap Forms module](http://www.github.com/unclecheese/silverstripe-bootstrap-forms)
* Includes an API for automatic jQuery validation
* A spam protection API that can be extended with your own spam protection plugins
* Logs failed spam attempts to the database, and locks out repeat offending IPs


## The "Kitchen Sink" example
```php
<?php

public function ContactForm() {
  return ContactForm::create("ContactForm","you@example.com","You've received a new contact form!")
            ->addFields(
                TextField::create("Name","What is your name?"),
                EmailField::create("Email", "What is your email?")
            )
            // You can add fields as strings, too.
            ->addField("Your message//Textarea")
            ->setSuccessMessage("Thanks for submitting the form!")
            ->setSuccessURL($this->Link('success'))
            ->setOnBeforeSend(function($data, $form) {
                  // Do stuff here. Return false to refuse the form.
            })
            ->setEmailTemplate("MyCustomTemplate")
            ->addOmittedField("SomeField")
            ->setIntroText("Someone submitted a form. Here's the data.")
            ->addSpamProtector(
                SimpleQuestionSpamProtector::create()
                  ->addQuestion("What's the opposite of skinny?","fat")
                  ->addQuestion("Which is bigger, a lake or an ocean?","ocean")
            )
            ->render();
}

```

### What's with ->render()?

The ContactForm class is not actually a form. It is an object that serves as a proxy manager of a Form object. The ->render() method is critical to send the form to the template.

## ContactFormPage

The ContactForm module comes with a page type that allows you to easily create contact forms based on user input in the CMS. The ContactFormPage class has fields for the "To" address, success messaging, and more.

To create a page with a contact form, simply create a descendant of ContactFormPage and call the parent Form() method.

```php
<?php

class MyContactPage extends ContactFormPage {}

class MyContactPage_Controller extends ContactFormPage_Controller {

  public function Form() {
    return parent::Form()
        ->addFields(
          TextField::create("YourName","Your name"),
          EmailField::create("Email","Your email"),
          TextareaField::create("Message","Your message")
        )
        ->render();
  }
}

```

## Setting up Postmark

Simply add your API key and confirmed "from" address to your _config.php.

```php
<?php

define('POSTMARKAPP_API_KEY','xxxxxxxxxxxxxxxxxxxxx');
define('POSTMARKAPP_MAIL_FROM_ADDRESS', 'me@example.com');

```

Once these settings are in place, the ContactForm module will use Postmark as its delivery method.


## Integrating with Bootstrap Forms

If you have the [BootstrapForms](http://www.github.com/unclecheese/silverstripe-bootstrap-forms) module installed, the form will automatically render as a BootstrapForm, unless you tell it otherwise, using ->setUseBootstrap(false)

## Setting the default spam protection

You can set up default spam protection that every form will use, unless configured otherwise.

**_config.php**
```php
<?php

ContactForm::set_default_spam_protection(array(
  	SimpleQuestionSpamProtector::create()
			->setHeading("Prove you're human by answering a simple question")
			->addQuestion("Is fire hot or cold?", "hot")
			->addQuestion("What color is a stop sign?", "red")
			->addQuestion("Is water wet or dry?","wet")
			->addQuestion("Is the world flat?", "no")
			->addQuestion("What animal says \"Meow?\"", "cat"),

		HoneyPotSpamProtector::create()
			->setName("PleaseFillThisOutYouBot")
```

