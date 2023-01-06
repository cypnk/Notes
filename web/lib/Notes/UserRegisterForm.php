<?php declare( strict_types = 1 );

namespace Notes;

class UserRegisterForm extends Form {
	
	// TODO: Pull form definition stored in database
	const Definition	=<<<JSON
{ 
	"form" : {
		"legend"	: "{lang:forms:register:legend}",
		"name"		: "register",
		"method"	: "post",
		"enctype"	: "application\/x-www-form-urlencoded",
		"action"	: "{action}",
		"inputs" : [ {
			"name"		: "username",
			"type"		: "text",
			"label"		: "{lang:forms:register:name}",
			"special"	: "{lang:forms:register:namespecial}",
			"desc"		: "{lang:forms:register:namedesc}",
			"required"	: "required"
		}, {
			"name" 		: "password",
			"type"		: "password",
			"label"		: "{lang:forms:register:pass}",
			"special"	: "{lang:forms:register:passspecial}",
			"desc"		: "{lang:forms:register:passdesc}",
			"required"	: "required"
		}, {
			"name" 		: "password-repeat",
			"type"		: "password",
			"label"		: "{lang:forms:register:passrpt}",
			"special"	: "{lang:forms:register:passrptpecial}",
			"desc"		: "{lang:forms:register:passrptdesc}",
			"required"	: "required"
		}, {
			"name"		: "rem",
			"type"		: "checkbox",
			"label"		: "{lang:forms:register:rem}"
		}, {
			"name"		: "register",
			"type"		: "submit",
			"value"		: "{lang:forms:register:submit}"
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

