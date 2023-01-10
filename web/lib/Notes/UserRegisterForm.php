<?php declare( strict_types = 1 );

namespace Notes;

class UserRegisterForm extends UserAuthForm {
	
	protected static string $form_name	= 'web_user_register';
	
	public static function newForm(
		\Notes\Controller	$ctrl,
		string			$action = ''
	) {
		return 
		static::loadForm( 
			$ctrl, 
			static::$form_name, 
			'\\Notes\\UserRegisterForm', 
			$action
		); 
	}
}

