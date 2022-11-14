<?php declare( strict_types = 1 );

namespace Notes;

class SHandler {
	
	protected readonly Controller $controller;
	
	public function __construct( Controller $ctrl ) {
		$this->controller = $ctrl;
		
		if ( \headers_sent() ) {
			\messages( 'error', 'Session handler created after headers sent' );
			return;
		}
		
		\session_set_save_handler(
			[ $this, 'sessionOpen' ], 
			[ $this, 'sessionClose' ], 
			[ $this, 'sessionRead' ], 
			[ $this, 'sessionWrite' ], 
			[ $this, 'sessionDestroy' ], 
			[ $this, 'sessionGC' ], 
			[ $this, 'sessionCreateID' ]
		);
	}
	
	public function __destruct() {
		if ( \session_status() === \PHP_SESSION_ACTIVE ) {
			\session_write_close();
		}
	}
	
	/**
	 *  Does nothing
	 */
	public function sessionOpen( $path, $name ) { return true; }
	public function sessionClose() { return true; }
	
	/**
	 *  Does nothing
	 */
	public function sessionOpen( $path, $name ) { return true; }
	public function sessionClose() { return true; }
	
	/**
	 *  Create session ID in the database and return it
	 *  
	 *  @return string
	 */
	public function sessionCreateID() {
		static $sql	= 
		"INSERT OR IGNORE INTO sessions ( session_id )
			VALUES ( :id );";
		
		$config	= $this->controller->getConfig();
		$db	= $this->controller->getData();
		$bt	= $config->setting( 'session_bytes', 'int' );
		$id	= \Notes\Util::genId( $bt );
		if ( $db->dataExec( $sql, [ ':id' => $id ], 'success', \SESSIONS ) ) {
			return $id;
		}
		
		// Something went wrong with the database
		\messages( 'error', 'Error writing to session ID to database' );
		die();
	}
	
	/**
	 *  Delete session
	 *  
	 *  @return bool
	 */
	public function sessionDestroy( $id ) {
		$sql	= "DELETE FROM sessions WHERE session_id = :id;";
		$db	= $this->controller->getData();
		if ( $db->dataExec( 
			$sql, [ ':id' => $id ], 'success', \SESSIONS 
		) ) {
			return true;
		}
		return false;
	}
	
	/**
	 *  Session garbage collection
	 *  
	 *  @return bool
	 */
	public function sessionGC( $max ) {
		$sql	= 
		"DELETE FROM sessions WHERE (
			strftime( '%s', 'now' ) - 
			strftime( '%s', updated ) ) > :gc;";
		$db	= $this->controller->getData();
		if ( $db->dataExec( $sql, [ ':gc' => $max ], 'success', \SESSIONS ) ) {
			return true;
		}
		return false;
	}
	
	/**
	 *  Read session data by ID
	 *  
	 *  @return string
	 */
	public function sessionRead( $id ) {
		static $sql	= 
		"SELECT session_data FROM sessions 
			WHERE session_id = :id LIMIT 1;";
		
		$db	= $this->controller->getData();
		$out	= 
		$db->dataExec( $sql, [ 'id' => $id ], 'column', \SESSIONS );
		
		$this->controller->run( 
			'sessionread', 
			[ 'id' => $id, 'data' => $out ]
		);
		
		return empty( $out ) ? '' : ( string ) $out;
	}
	
	/**
	 *  Store session data
	 *  
	 *  @return bool
	 */
	public function sessionWrite( $id, $data ) {
		static $sql	= 
		"REPLACE INTO sessions ( session_id, session_data )
			VALUES( :id, :data );";
		
		$db	= $this->controller->getData();
		if ( $db->dataExec( 
			$sql, [ ':id' => $id, ':data' => $data ], 'success', \SESSIONS 
		) ) {
			$this->controller->run( 
				'sessionwrite', 
				[ 'id' => $id, 'data' => $data ]
			);
			return true;
		}
		
		return false;
	}
}
		
