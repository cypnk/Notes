<?php declare( strict_types = 1 );

namespace Notes;

abstract class Controllable {
	
	/**
	 *  Read-only unique identifier
	 *  @var int
	 */
	private readonly int $_id;
	
	/**
	 *  Read-only current controllable name
	 *  @var string
	 */
	protected readonly string $name;
	
	/**
	 *  Main event controller
	 *  @var \Notes\Controller
	 */
	protected readonly object $controller;
	
	/**
	 *  Controllable parameters on execution
	 *  @var array
	 */
	protected array $_params	= [];
	
	/**
	 *  Error storage
	 *  @var array
	 */
	protected array $errors		= [];
	
	/**
	 *  Notification storage
	 *  @var array
	 */
	protected array $notices	= [];
	
	/**
	 *  Create new controllable with controller
	 *  
	 *  @param \Notes\Controller	$ctrl	Event controller
	 */
	public function __construct( \Notes\Controller $ctrl ) {
		$this->controller	= $ctrl;
	}
	
	public function __destruct() {
		foreach ( $this->errors as $e ) {
			\messages( 'error', \get_called_class() . ' ' . $e );
		}
		
		foreach ( $this->notices as $m ) {
			\messages( 'notice', \get_called_class() . ' ' . $m );
		}
	}
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'id':
				$this->_id = ( int ) $value;
				break;
			
			case 'params':
				$this->_params = 
				static::formatSettings( $value );
		}
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'id':
				return $this->_id ?? 0;
			
			case 'params':
				return $this->_params;
		}
		return null;
	}
	
	/**
	 *  Append error to running collection
	 *  
	 *  @param string	$msg	Detailed error message
	 */
	protected function error( string $msg ) {
		$this->errors[] = 
			\get_called_class() . ' ' . $msg;
	}
	
	/**
	 *  Current controllable's name (read-only)
	 *  
	 *  @return string
	 */
	public function getName() : string {
		return $this->name ?? '';
	}
	
	/** 
	 *  Currently set event controller
	 *  
	 *  @return \Notes\Controller
	 */
	public function getController() {
		return $this->controller;
	}
	
	/**
	 *  Get while optionally loading parameter object
	 *  
	 *  @param string	$name	Loaded/loading object name
	 *  @return mixed
	 */
	public function getControllerParam( string $name ) {
		$this->controller->addParam([ $name ]);
		
		return $this->controller->getParam( $name );
	}
	
	/**
	 *  Event parameters
	 *  
	 *  @return array
	 */
	public function getParams() : array {
		return $this->_params;
	}
	
	/**
	 *  Get running errors
	 *  
	 *  @return array
	 */
	public function getErrors() : array {
		return $this->errors;
	}
	
	/**
	 *  Get running notices
	 *  
	 *  @return array
	 */
	public function getNotices() : array {
		return $this->notices;
	}
	
	/**
	 *  Get current database handler
	 *  
	 *  @return mixed
	 */
	protected function getData() {
		return $this->controller->getParam( 'Data' ) ?? null;
	}
	
	/**
	 *  Helper to detect and parse a 'settings' data type
	 *  
	 *  @param string	$name		Setting name
	 *  @return array
	 */
	public static function formatSettings( $value ) : array {
		// Nothing to format?
		if ( \is_array( $value ) ) {
			return $value;
		}
		
		// Can be decoded?
		if ( 
			!\is_string( $value )	|| 
			\is_numeric( $value )
		) {
			return [];
		}
		$t	= \trim( $value );
		if ( empty( $t ) ) {
			return [];
		}
		if ( 
			\str_starts_with( $t, '{' ) && 
			\str_ends_with( $t, '}' )
		) {
			return \Notes\Util::decode( ( string ) $t );
		}
		
		return [];
	}
	
}
