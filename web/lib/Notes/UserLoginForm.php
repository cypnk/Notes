<?php declare( strict_types = 1 );

namespace Notes;

class UserLoginForm extends Form {
	
	public static function newForm(
		\Notes\Controller	$ctrl,
		string			$action = ''
	) {
		return 
		static::loadForm( 
			$ctrl, 
			'user login form', 
			'\\Notes\\UserLoginForm', 
			$action
		); 
	}
	
	public function render() : string {
		return $this->form_type->render( 
			$this->controller, $this->params['form'] 
		);
	}
}

