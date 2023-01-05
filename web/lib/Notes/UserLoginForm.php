<?php declare( strict_types = 1 );

namespace Notes;

class UserLoginForm extends Form {
	
	// TODO: Pull form definition stored in database
	const Definition	=<<<JSON
{ 
	"form" : {
		"legend"	: "{lang:forms:login:legend}",
		"name"		: "login",
		"method"	: "post",
		"enctype"	: "application\/x-www-form-urlencoded",
		"action"	: "{action}",
		"inputs" : [ {
			"name"		: "username",
			"type"		: "text",
			"label"		: "{lang:forms:login:name}",
			"special"	: "{lang:forms:login:namespecial}",
			"desc"		: "{lang:forms:login:namedesc}",
			"required"	: "required"
		}, {
			"name" 		: "password",
			"type"		: "password",
			"label"		: "{lang:forms:login:pass}",
			"special"	: "{lang:forms:login:passspecial}",
			"desc"		: "{lang:forms:login:passdesc}",
			"required"	: "required"
		}, {
			"name"		: "rem",
			"type"		: "checkbox",
			"label"		: "{lang:forms:login:rem}"
		}, {
			"name"		: "login",
			"type"		: "submit",
			"value"		: "{lang:forms:login:submit}"
		} ]
	}
}
JSON;

	public function __construct( \Notes\Controller $ctrl, string $action ) {
		parent::__construct( $ctrl );
		
		// Load current definition
		$this->params = static::Definition;
		
		// Labeled type
		$this->form_type = \Notes\FormType::FormLegend;
		
		// Override action
		$this->_params['form']['action']  = $action;
		
		// TODO: Add meta key and anti-XSRF tokens
		// TODO: Replace language placeholders with definitions in config
	}
	
	public function render() : string {
		return $this->form_type->render( $this->params['form'] );
	}
}

