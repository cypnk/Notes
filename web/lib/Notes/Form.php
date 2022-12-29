<?php declare( strict_types = 1 );

namespace Notes;

abstract class Form {
	
	/**
	 *  Form unique label
	 *  @var string
	 */
	public readonly string $name;
	
	/**
	 *  Form on-page locator
	 *  @var string
	 */
	public readonly string $id;
	
	/**
	 *  Form encoding type, defaults to 'application/x-www-form-urlencoded'
	 *  @var string
	 */
	public readonly string $enctype;
	
	/**
	 *  Basic form templates (uses Tachyons.css)
	 *  @var array
	 */
	protected static array $templates	= [
		// Basic form
		'form'		=>
		'<form name="{name}" id="{id}" 
			class="pa4 black-80 measure" action="{action}" 
			method="{method}" enctype="{enctype}">' . 
		'<legend class="mb2 f4 measure">{legend}</legend>{body}</form>',
		
		// No legend
		'form_nl'	=>
		'<form  name="{name}" id="{id}" 
			class="pa4 black-80 measure" action="{action}" 
			method="{method}" enctype="{enctype}">{body}</form>'
	];
	
	abstract public function render( array $data ) : string {}
	
	/**
	 *  Basic form constructor
	 *  
	 *  @param string	$_name		Form unique label
	 *  @param string	$_method	Submission method
	 *  @param string	$_enc		Content encoding type
	 *  @param string	$_id		Optional, on-page identifier
	 */
	public function __construct( 
		string	$_name, 
		string	$_method,
		string	$_enc, 
		?string	$_id = null 
	) {
		$this->name	= $_name;
		if ( empty( $_id ) ) {
			$this->id = $_name;
		}
		
		$this->method	= static::cleanFormMethodType( $_method );
		
		
		// Apply only valid enctypes
		$this->enctype = static::cleanFormEnctype( $_enc );
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
}

