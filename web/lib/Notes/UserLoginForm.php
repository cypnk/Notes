<?php declare( strict_types = 1 );

namespace Notes;

class UserLoginForm extends UserAuthForm {
	
	protected static string $form_name	= 'web_user_login';
	
	public static function newForm(
		\Notes\Controller	$ctrl,
		string			$action = ''
	) {
		return 
		static::loadForm( 
			$ctrl, 
			static::$form_name, 
			'\\Notes\\UserLoginForm', 
			$action
		); 
	}
}

