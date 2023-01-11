<?php declare( strict_types = 1 );

namespace Notes;

abstract class UserAuthForm extends Form {
	
	/**
	 *  Common username property
	 *  @var string
	 */
	public string $username;
	
	/**
	 *  Current form label in database
	 *  @param string
	 */
	protected string $form_name	= 'web_user_auth';

	public function render() : string {
		$this->placeholders = 
		[ 
			'{username}'	=> $this->username ?? ''
		];
		return parent::render();	
	}
}
