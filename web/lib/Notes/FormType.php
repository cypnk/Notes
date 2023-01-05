<?php declare( strict_types = 1 );

namespace Notes;

enum FormType {
	
	case Validated;		// Should include nonce/token pairs
	case UnValidated;
	
	const FormLegend	= 
	'<form name="{name}" id="{id}" ' . 
		'class="{form_classes}" action="{action}" ' . 
		'method="{method}" enctype="{enctype}" {extras}>' . 
	'<legend class="{legend_classes}">{legend}</legend>{body} {more}</form>';
	
	const FormNoLegend	= 
	'<form  name="{name}" id="{id}" ' . 
		'class="{form_classes}" action="{action}" ' . 
		'method="{method}" enctype="{enctype}" {extras}>{body} {more}</form>';
	
	/**
	 *  Render current form in HTML with given inputs
	 */
	public function render( array $form ) : string {
		// Prepare placeholders
		$data = \Notes\Util::placeholders( $form );
		
		// Default placeholders
		static::baseDefaults( $data );
		
		// Ensure inputs
		$form['inputs']	??= [];
		
		// Append
		foreach( $form['inputs'] as $input ) {
			$data['{body}'] .= 
				static::inputRender( $input );
		}
		
		return empty( $data['{legend}'] ) ? 
			\strtr( static::FormNoLegend, $data ) : 
			\strtr( static::FormLegend, $data );
	}
	
	/**
	 *  Set default placeholder values
	 */
	public static baseDefaults( array &$data ) {
		// Required
		$data['{id}']			??= '';
		$data['{legend}']		??= '';
		
		// Operation
		$data['{action}']		??= '/';
		$data['{enctype}']		??= '';
		$data['{method}']		??= 'get';
		
		// Extras
		$data['{extras}']		??= '';
		$data['{more}']			??= '';
		
		// Default styling
		$data['{form_classes}']		??= 'pa4 black-80 measure';
		$data['{legend_classes}']	??= 'mb2 f4 measure';
		
		// Filters
		$data['{method}']		= 
		static::cleanFormMethodType( $data['{method}'] );
		
		$data['{enctype}']		= 
		static::cleanFormEnctype( $data['{enctype}'] );
	}
	
	/**
	 *  Filter form sending method type, defaults to get or post
	 *  
	 *  @param string	$v	Raw form method
	 *  @return string
	 */
	public static function cleanFormMethodType( string $v ) : string {
		static $fm = [ 'get', 'post' ];
		
		$v = \strtolower( trim( $v ) );
		return \in_array( $v, $fm ) ? $v : 'get';
	}
	
	/**
	 *  Form encoding type helper, defaults to 'application/x-www-form-urlencoded'
	 *  
	 *  @param string	$v	Raw encoding type
	 *  @return string
	 */
	public static function cleanFormEnctype( string $v ) : string {
		static $ec = 	=  [
			'application/x-www-form-urlencoded',
			'multipart/form-data',
			'text/plain'
		];
		
		$v = \strtolower( trim( $v ) );
		return \in_array( $v, $ec ) ?
			$v : 'application/x-www-form-urlencoded';
	}
	
	public static function inputRender( array $input ) : string {
		$input['type'] ??= '';
		
		$out = 
		match( \strtolower( $input['type'] ) ) {
			'text'		=> \Notes\InputType::Text,
			'email'		=> \Notes\InputType::Email,
			'radio'		=> \Notes\InputType::Radio,
			'search'	=> \Notes\InputType::Search,
			'select'	=> \Notes\InputType::Select,
			'checkbox'	=> \Notes\InputType::Checkbox,
			'password'	=> \Notes\InputType::Password,
			'textarea'	=> \Notes\InputType::Textarea,
			
			'hidden'	=> \Notes\InputType::Hidden,
			'button'	=> \Notes\InputType::Button,
			'reset'		=> \Notes\InputType::Button,
			'submit'	=> \Notes\InputType::Submit,
			
			default		=> \Notes\InputType::Other
		}
		$out->render( $input );
	}
	
}

	

