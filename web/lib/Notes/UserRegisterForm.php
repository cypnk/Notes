<?php declare( strict_types = 1 );

namespace Notes;

class UserRegisterForm extends Form {
	
	public static function newForm(
		\Notes\Controller	$ctrl,
		string			$action = ''
	) {
		return 
		static::loadForm( 
			$ctrl, 
			'web_user_register', 
			'\\Notes\\UserRegisterForm', 
			$action
		); 
	}
	
	public function render() : string {
		return $this->form_type->render( 
			$this->controller, $this->params['form'] 
		);
	}
}

