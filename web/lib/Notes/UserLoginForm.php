<?php declare( strict_types = 1 );

namespace Notes;

class UserLoginForm extends Form {

	public function __construct( \Notes\Controller $ctrl, string $action = '' ) {
		parent::__construct( $ctrl );
		
		// Load form definition from database
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		
		$form	= 
		$db->dataExec( 
			'SELECT content FROM forms WHERE label = :label' 
			[ ':label' => 'web user login' ], 
			'result', 
			\DATA
		);
		
		if ( empty( $form ) ) {
			return;
		}
		
		$this->params	= $form[0];
		
		// Form type
		$this->form_type = 
		( $this->params['form']['validated'] ?? true ) ? 
			\Notes\FormType::Validated : 
			\Notes\FormType::UnValidated;
		
		// Override action
		if ( !empty( $action ) ) {
			$this->_params['form']['action']  = $action;
		}
		
		// TODO: Add meta key and anti-XSRF tokens
		// TODO: Replace language placeholders with definitions in config
	}
	
	public function render() : string {
		return $this->form_type->render( 
			$this->controller, $this->params['form'] 
		);
	}
}

