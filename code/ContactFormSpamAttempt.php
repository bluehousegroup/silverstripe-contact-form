<?php


/**
 * Logs a failed attempt at a spam question
 *
 * @author Aaron Carlino <aaron@bluehousegroup.com>
 * @package ContactForm
 */
class ContactFormSpamAttempt extends DataObject {
	

	private static $db = array (
		'IPAddress' => 'Varchar',
		'URL' => 'Text',
		'Notes' => 'Text'
	);


	
}
