<?php declare( strict_types = 1 );

namespace Notes;

abstract class Provider extends Entity {
	
	/**
	 *  Short description of provider
	 *  @var string
	 */
	private readonly $_label;
	
	/**
	 *  Responsibility domain
	 *  @var string
	 */
	private readonly $_realm;
	
	/**
	 *  Domain scope limit (fungible)
	 *  @var string
	 */
	private readonly $_realm_scope;
	
	/**
	 *  Core setting list
	 *  @var array
	 */
	protected array $settings_base	= [
		'setting_id'	=> '',
		'realm'		=> 'http://localhost',
		'scope'		=> 'local'
	];
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'label':
				$this->_label		= 
				\Notes\Util::title( ( string ) $value );
				return;
			
			case 'realm':
				$this->_realm = 
				\Notes\Util::cleanUrl( ( string ) $value );
				
				return;
			
			case 'realm_scope':
				$this->_realm_scope	= 
				\Notes\Util::labelName( ( string ) $value );
				return;
			
			// Intercept settings
			case 'settings':
				// Configure settings format
				$this->_settings		= 
				\array_merge( 
					$this->settings_base, 
					static::formatSettings( $value )
				);
				
				// Reset setting ID as hash
				$this->_setting_id		= 
				\hash( 
					'sha384', 
					\Util::encode( $this->_settings ) 
				);
				
				// Include ID in original setting data
				$this->_settings['setting_id']	= 
					$this->_setting_id;
				return;
		}
		
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {		
		switch( $name ) {
			case 'label':
				return $this->_label ?? '';
			
			case 'realm':
				return $this->_realm ?? '';
			
			case 'realm_scope':
				return $this->_realm_scope ?? '';
		}
		
		return parent::__get( $name );
	}
}

