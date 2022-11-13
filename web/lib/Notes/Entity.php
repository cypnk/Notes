<?php declare( strict_types = 1 );

namespace Notes;

abstract class Entity implements Stringable {
	
	private readonly int $_id;
	
	private readonly string $_uuid;
	
	private readonly string $_created;
	
	private readonly string $_updated;
	
	public int $status;
	
	public int $sort_order;
	
	public string $setting_id;
	
	protected array $_settings		= [];
	
	protected static Data $data;
	
	protected static array $errors		= [];
	
	abstract public function save() : bool {}
	
	public function __destruct() {
		foreach ( $this->errors as $e ) {
			\messages( 'error', $e );
		}
	}
	
	public static function setData( Data $_data ) {
		static::$data = $_data;
	}
	
	protected function getData() {
		return static::$data ?? null;
	}
	
	protected function error( string $msg ) {
		static::$errors[] = [ 
			\get_called_class(), $msg 
		];
	}
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'id':
				$this->_id = ( int ) $value;
				break;
				
			case 'uuid':
				$this->_uuid = $value;
				break;
				
			case 'created':
				$this->_created = $value;
				break;
				
			case 'updated':
				$this->_updated = $value;
				break;
				
			case 'settings':
				// Configure settings format
				$this->_settings		= 
					static::formatSettings( $value );
				
				// Reset setting ID as hash
				$this->_setting_id		= 
				\hash( 
					'sha384', 
					\Notes\Util::encode( $this->_settings ) 
				);
				
				// Include ID in original setting data
				$this->_settings['setting_id']	= 
					$this->_setting_id;
				break;
		}
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'id':
				return $this->_id ?? 0;
				
			case 'uuid':
				return $this->_uuid ?? '';
				
			case 'created':
				return $this->_created ?? '';
				
			case 'updated':
				return $this->_updated ?? '';
				
			case 'settings':
				return $this->_settings ?? [];
			
			case 'setting_id':
				return $this->_setting_id ?? 
					$this->_settings['setting_id'] ?? '';
		}
		
		// Nothing else at this level
		return null;
	}
	
	public function __toString() {
		return 
		\Notes\Util::encode( 
			\array_merge( [ 
				"id"		=> $this->id, 
				"created"	=> $this->created,
				"updated"	=> $this->updated,
				"setting_id"	=> $this->setting_id
			], $this->_settings );
		);
	}
	
	public function __isset( $name ) {
		switch ( $name ) {
			case 'id':
				return isset( $this->_id );
			
			case 'uuid':
				return isset( $this->_uuid );
				
			case 'uuid':
				return isset( $this->_uuid );
				
			case 'created':
				return isset( $this->_created );
				
			case 'updated':
				return isset( $this->_updated );
		}
		return false;
	}
	
	public function setting( string $name, $default ) {
		return $this->_settings[$name] ?? $default;
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

