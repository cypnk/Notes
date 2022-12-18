<?php declare( strict_types = 1 );

namespace Notes;

class Config extends Entity {
	
	/**
	 *  Configuration placeholder replacements
	 *  @var array
	 */
	private readonly array $replacements;
	
	/**
	 *  Configuration application scope
	 *  @var string
	 */
	public readonly string $_realm;
	
	/**
	 *  Current client request
	 *  @var object
	 */
	public readonly Request $request;
	
	/**
	 *  Overriden configuration during runtime
	 *  @var array
	 */
	private $options		= [];
	
	/**
	 *  Configuration begins with request and default realm settings
	 */
	public function __construct( Controller $ctrl ) {
		parent::__construct( $ctrl );
		
		$this->request	= new \Notes\Request( $ctrl );
		$this->replacements	= [
			'{path}'	=> \PATH,
			'{notes_lib}'	=> \NOTES_LIB,
			'{modules_lib}'	=> \NOTES_MOD,
			'{public}'	=> \NOTES_PUBLIC,
			'{store}'	=> \WRITABLE,
			'{files}'	=> \NOTES_FILES,
			'{temp}'	=> \NOTES_TEMP,
			'{jobs}'	=> \NOTES_JOBS,
			'{data_db}'	=> \DATA,
			'{cache_db}'	=> \CACHE,
			'{session_db}'	=> \SESSIONS,
			'{notice_log}'	=> \NOTICES,
			'{error_log}'	=> \ERRORS,
			'{max_log}'	=> \MAX_LOG_SIZE,
			'{mail_from}'	=> \MAIL_FROM,
			'{mail_receive}'	=> \MAIL_RECEIVE,
			'{host}'	=> $this->request->getHost(),
			'{method}'	=> $this->request->getMethod()
		];
		
		$this->loadRealm( $this->request );
		$realm		= $this->setting( 'realm', '' );
		
		// No realm specified, default to web
		if ( empty( $realm ) ) {
			$realm = $this->request->website();
		}
		$this->realm	= $realm;
	}
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'realm':
				$this->_realm = 
				\Notes\Util::cleanUrl( ( string ) $value );
				return;
		}
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'realm':
				return $this->_realm ?? '';
		}
		
		return::__get( $name );
	}
	
	public function realmName() : string {
		static $name;
		if ( isset( $name ) ) {
			return $name;
		}
		
		$name = 
		\Notes\Util::normal(
			\Notes\Util::bland( $this->realm, true )
		);
		return $name;
	}
	
	public function loadRealm( Request $request ) {
		if ( isset( $this->_realm ) ) {
			return;
		}
		
		// Build path tree
		$path	= [ $request->getHost() ];
		$tree	= 
		\array_map( function( $v ) use $path {
			$path[] = $path[count( $path ) - 1] . 
				\Notes\Util::slashPath( $v );
		}, explode( '/', $request->getURI() ) );
		
		$db	= $this->getData();
		
		// Get globals
		$sql	= 'SELECT settings FROM configs WHERE realm = "";';
		$res	= $db->getResults( $sql );
		
		foreach ( $res as $r ) {
			$this->overrideDefaults( 
				\Notes\Util::decode( $r['settings'] ) 
			) ;
		}
		
		// Get by realm, if any
		$frag	= $db->getInParam( $tree, $params );
		$sql	= 
		"SELECT settings FROM configs WHERE realm {$frag} 
			ORDER by realm ASC;";
		
		$res	= $db->getResults( $sql );
		$data	= 
		foreach ( $res as $r ) {
			$this->overrideDefaults( 
				\Notes\Util::decode( $r['settings'] ) 
			);
		}
	}
	
	/**
	 *  @brief Brief description
	 *  
	 *  @return Return description
	 *  
	 *  @details More details
	 */
	public function getRequest() {
		return $this->request;
	}
	
	/**
	 *  Overriden configuration, if set
	 * 
	 *  @return array
	 */
	public function getConfig() : array {
		return $this->options;
	}
	
	public function save() : bool {
		$db		= $this->getData();
		
		// Format for saving
		$settings	= \Notes\Util::encode( $this->settings );
		
		// If generated, it came from the database
		if ( !empty( $this->realm ) ) {
			return 
			$db->setUpdate( 
				"UPDATE configs SET settings = :settings 
					WHERE id = :id LIMIT 1;",
				[ 
 					':settings'	=> $settings, 
					':id'		=> $this->id 
				],
				\DATA
			);
		}
		
		
		$id = 
		$db->setInsert( 
			"INSERT INTO config ( settings ) 
				VALUES ( :settings );", 
			[ ':settings' => $settings ], 
			\DATA
		);
		
		if ( $id ) {
			$this->id = $id;
			return true;
		}
		return false;
	}
	
	/**
	 *  Replace relative paths and other placeholder values
	 *  
	 *  @param mixed	$settings	Raw configuration
	 *  @return mixed
	 */
	public function placeholders( $settings ) {
		// Replace if string
		if ( \is_string( $settings ) ) {
			return 
			\strtr( $settings, $this->replacements );
		
		// Keep going if an array
		} elseif ( \is_array( $settings ) ) {
			foreach ( $settings as $k => $v ) {
				$settings[$k] = 
					$this->placeholders( $v );
			}
		}
		
		// Everything else as-is
		return $settings;
	}
	
	/**
	 *  Override default configuration with new runtime defaults
	 *  E.G. From database
	 * 
	 *  @param array	$options	New configuration
	 */
	public function overrideDefaults( array $options ) {
		$this->options = 
		\array_merge( $this->options, $options );
		
		// Change static placeholders
		foreach ( $this->options as $k => $v ) {
			$this->options[$k] = 
				$this->placeholders( $v );
		}
	}
	
	/**
	 *  Get configuration setting or default value
	 *  
	 *  @param string	$name		Configuration setting name
	 *  @param string	$type		String, integer, or boolean
	 *  @param string	$filter		Optional parse function
	 *  @return mixed
	 */
	public function setting( 
		string		$name, 
		string		$type		= 'string',
		string		$filter		= ''
	) {
		if ( !isset( $this->options[$name] ) ) { 
			return null;
		}
		
		switch( $type ) {
			case 'int':
			case 'integer':
				return ( int ) $this->options[$name];
				
			case 'bool':
			case 'boolean':
				return ( bool ) $this->options[$name];
			
			case 'json':
				$json	= $this->options[$name];
				
				return 
				\is_array( $json ) ? 
					$json : 
					\Notes\Util::decode( ( string ) $json );
			
			case 'list':
			case 'listlower':
				$items	= $this->options[$name];
				$lower	= 
				( 0 == \strcmp( $type, 'listlower' ) ) ? 
					true : false;
				
				return 
				\is_array( $items ) ? 
					\array_map( 'trim', $items ) : 
					\Notes\Util::trimmedList( 
						( string ) $items, $lower 
					);
				
			case 'lines':
				$lines	= $this->options[$name];
				
				return 
				\is_array( $lines ) ? 
					$lines : 
					\Notes\Util::lineSettings( 
						( string ) $lines, 
						$filter
					);
			
			// Core configuration setting fallback
			default:
				return $this->options[$name];
		}
	}
	
	
	/**
	 *  Helper to determine if given hash algo exists or returns default
	 *  
	 *  @param string	$token		Configuration setting name
	 *  @param string	$default	Defined default value
	 *  @param bool		$hmac		Check hash_hmac_algos() if true
	 *  @return string
	 */
	public function hashAlgo(
		string	$token, 
		string	$default, 
		bool	$hmac		= false 
	) : string {
		static $algos	= [];
		$t		= $token . ( string ) $hmac;
		if ( isset( $algos[$t] ) ) {
			return $algos[$t];
		}
		
		$ht		= $this->setting( $token ) ?? $default;
		
		$algos[$t]	= 
			\in_array( $ht, 
				( $hmac ? \hash_hmac_algos() : \hash_algos() ) 
			) ? $ht : $default;
			
		return $algos[$t];	
	}

}
