<?php declare( strict_types = 1 );

namespace Notes;

class Data {
	
	const DATA_TIMEOUT	= 10;
	
	/**
	 *  Database connection cache
	 *  @var array
	 */
	private array $db	= [];
	
	/**
	 *  PDO Statement cache
	 *  @var array
	 */
	private array $stmcache	= [];
	
	/**
	 *  Error storage
	 *  @var array
	 */
	private array $errors	= [];
	
	/**
	 *  Event controller
	 *  @var object
	 */
	protected readonly Controller $ctrl;
	
	public function __construct( Controller $ctrl ) {
		$this->ctrl = $ctrl;
	}
	
	public function __destruct() {
		$this->statement( null, null );
		$this->getDb( '', 'closeAll' );
		
		foreach ( $errors as $e ) {
			\messages( 'error', \get_called_class() . ' ' . $e );
		}
	}
	
	/**
	 *  Create database tables based on DSN
	 *  
	 *  @param object	$db	PDO Database object
	 *  @param string	$dsn	Database path associated with PDO object
	 */
	private function installSQL( \PDO $db, string $dsn ) {
		$file	= '';
		try {
			// Try to load installation SQL file at the same DSN location
			$file	= \file_get_contents( $dsn . '.sql' );
		} catch ( \Exception $e ) {
			$this->errors[] = 
				'Error retrieving data from ' . $dsn . '.sql ' . 
				' Message: ' . 
				$e->getMessage() ?? 'file_get_contents() exception.';
			return;
		}
		
		if ( false === $file || empty( $file ) ) {
			$this->errors[] = 
				'Error retrieving data from ' . $dsn . '.sql';
			return;
		}
		
		$lines	= \Notes\Util::lines( $file );
		if ( empty( $lines ) ) {
			return;
		}
		
		$parse	= [];
		// Filter SQL comments and lines starting PRAGMA
		foreach ( $lines as $l ) {
			if ( \preg_match( '/^(\s+)?(--|PRAGMA)/is', $l ) ) {
				continue;
			}
			$parse[] = $l;
		}
		
		// Separate into statement actions
		$qr	= \explode( '-- --', \implode( " \n", $parse ) );
		foreach ( $qr as $q ) {
			if ( empty( trim( $q ) ) ) {
				continue;
			}
			$db->exec( $q );
		}
	}
	
	/**
	 *  Get or create cached PDO Statements
	 *  
	 *  @param PDO		$db	Database connection
	 *  @param string	$sql	Query string or statement
	 *  @return mixed
	 */
	public function statement( ?\PDO $db, ?string $sql ) {
		if ( empty( $db ) && empty( $sql ) ) {
			// Dump cached statements
			\array_map( 
				function( $v ) { return null; }, 
				$this->stmcache 
			);
			return null;
		}
		
		if ( isset( $this->stmcache[$sql] ) ) {
			return $this->stmcache[$sql];
		}
		
		$this->stmcache[$sql] = $db->prepare( $sql );
		return $this->stmcache[$sql];
	}
	
	public function getDb( string $dsn, string $mode = 'get' ) {
		switch( $mode ) {
			case 'close':	
				if ( isset( $this->db[$dsn] ) ) {
					$this->db[$dsn] = null;
					unset( $this->db[$dsn] );
				}
				return;
			
			case 'closeall':
				foreach( $this->db as $k => $v  ) {
					$this->db[$k] = null;
					unset( $this->db[$k] );
				}
				return;
				
			default:
				if ( empty( $dsn ) ) {
					return null;
				}
		}
		
		if ( isset( $this->db[$dsn] ) ) {
			return $this->db[$dsn];
		}
		
		// First time? SQLite database will be created
		$first_run	= !\file_exists( $dsn );
		$opts	= [
			\PDO::ATTR_TIMEOUT		=> static::DATA_TIMEOUT,
			\PDO::ATTR_DEFAULT_FETCH_MODE	=> \PDO::FETCH_ASSOC,
			\PDO::ATTR_PERSISTENT		=> false,
			\PDO::ATTR_EMULATE_PREPARES	=> false,
			\PDO::ATTR_AUTOCOMMIT		=> false,
			\PDO::ATTR_ERRMODE		=> 
				\PDO::ERRMODE_EXCEPTION
		];
		
		try {
			$this->db[$dsn]	= 
			new \PDO( 'sqlite:' . $dsn, null, null, $opts );
		} catch ( \PDOException $e ) {
			$this->errors[] =
				'Error connecting to database ' . $dsn . 
				' Messsage: ' . $e->getMessage() ?? 'PDO Exception';
			die();
		}
		
		// Preemptive defense
		$this->db[$dsn]->exec( 'PRAGMA quick_check;' );
		$this->db[$dsn]->exec( 'PRAGMA trusted_schema = OFF;' );
		$this->db[$dsn]->exec( 'PRAGMA cell_size_check = ON;' );
		
		// Prepare defaults if first run
		if ( $first_run ) {
			$this->db[$dsn]->exec( 'PRAGMA encoding = "UTF-8";' );
			$this->db[$dsn]->exec( 'PRAGMA page_size = "16384";' );
			$this->db[$dsn]->exec( 'PRAGMA auto_vacuum = "2";' );
			$this->db[$dsn]->exec( 'PRAGMA temp_store = "2";' );
			$this->db[$dsn]->exec( 'PRAGMA secure_delete = "1";' );
			
			// Load and process SQL
			$this->installSQL( $this->db[$dsn], $dsn );
			
			// Instalation check
			$this->db[$dsn]->exec( 'PRAGMA integrity_check;' );
			$this->db[$dsn]->exec( 'PRAGMA foreign_key_check;' );
		}
		
		$this->db[$dsn]->exec( 'PRAGMA journal_mode = WAL;' );
		$this->db[$dsn]->exec( 'PRAGMA foreign_keys = ON;' );
		
		return $db[$dsn];
	}
	
	/**
	 *  Helper to get the result from a successful statement execution
	 *  
	 *  @param PDO		$db	Database connection
	 *  @param array	$params	Parameters 
	 *  @param string	$rtype	Return type
	 *  @param PDOStatement	$stm	PDO prepared statement
	 *  @return mixed
	 */
	public function getDataResult( 
		\PDO		$db, 
		array		$params, 
		string		$rtype, 
		\PDOStatement	$stm 
	) {
		$ok	= empty( $params ) ? 
				$stm->execute() : 
				$stm->execute( $params );
		
		switch ( $rtype ) {
			// Query with array return
			case 'results':
				return $ok ? $stm->fetchAll() : [];
			
			// Insert with ID return
			case 'insert':
				return $ok ? $db->lastInsertId() : 0;
			
			// Single column value
			case 'column':
				return $ok ? $stm->fetchColumn() : '';
			
			// Success status
			default:
				return $ok ? true : false;
		}
	}
	
	/**
	 *  Shared data execution routine
	 *  
	 *  @param string	$sql	Database SQL
	 *  @param array	$params	Parameters 
	 *  @param string	$rtype	Return type
	 *  @param string	$dsn	Database string
	 *  @return mixed
	 */
	public function dataExec(
		string		$sql,
		array		$params,
		string		$rtype,
		string		$dsn
	) {
		$db	= $this->getDb( $dsn );
		$res	= null;
		
		try {
			$stm	= $this->statement( $db, $sql );
			$res	= $this->getDataResult( $db, $params, $rtype, $stm );
			$stm->closeCursor();
			
		} catch( \PDOException $e ) {
			$stm	= null;
			$this->errors[] = $e->getMessage() ?? 'PDO Exception' ;
			return null;
		}
		
		$stm	= null;
		return $res;
	}
	
	/**
	 *  Update or insert multiple database rows at once with single SQL
	 *  
	 *  @param string	$sql	Database SQL update query
	 *  @param array	$params	Collection of query parameters
	 *  @param string	$rtype	Return type
	 *  @param string	$dsn	Database string
	 *  @return array		Result status
	 */
	public function dataBatchExec (
		string		$sql,
		array		$params,
		string		$rtype,
		string		$dsn		= ''
	) : array {
		if ( empty( $dsn ) ) {
			return [];
		}
		
		$db	= $this->getDb( $dsn );
		$res	= [];
		
		try {
			if ( !$db->beginTransaction() ) {
				return false;
			}
			
			$stm	= $this->statement( $db, $sql );
			foreach ( $params as $p ) {
				$res[]	= 
				$this->getDataResult( $db, $params, $rtype, $stm );
			}
			$stm->closeCursor();
			$db->commit();
		
		} catch( \PDOException $e ) {
			$this->errors[] = $e->getMessage() ?? 'PDO Exception';
		}
		
		return $res;
	}
	
	/**
	 *  Helper to turn a range of input values into an IN() parameter
	 *  
	 *  @example Parameters for [value1, value2] become "IN (:paramIn_0, :paramIn_1)"
	 *  
	 *  @param array	$values		Raw parameter values
	 *  @param array	$params		PDO Named parameters sent back
	 *  @param string	$prefix		SQL Prepended fragment prefix
	 *  @param string	$prefix		SQL Appended fragment suffix
	 *  @return string
	 */
	public function getInParam(
		array		$values, 
		array		&$params, 
		string		$prefix		= 'IN (', 
		string		$suffix		= ')'
	) : string {
		$sql	= '';
		$p	= '';
		$i	= 0;
		
		foreach ( $values as $v ) {
			$p		= ':paramIn_' . $i;
			$sql		.= $p .',';
			$params[$p]	= $v;
			
			$i++;
		}
		
		// Remove last comma and close parenthesis
		return $prefix . rtrim( $sql, ',' ) . $suffix;
	}
	
	/**
	 *  Get parameter result from database
	 *  
	 *  @param string	$sql	Database SQL query
	 *  @param array	$params	Query parameters
	 *  @param string	$dsn	Database string
	 *  @return array		Query results
	 */
	public function getResults(
		string		$sql, 
		array		$params		= [],
		string		$dsn		= ''
	) : array {
		if ( empty( $dsn ) ) {
			return [];
		}
		
		$res = $this->dataExec( $sql, $params, 'results', $dsn );
		
		return 
		empty( $res ) ? [] : ( \is_array( $res ) ? $res : [] );
	}
	
	/**
	 *  Create database update
	 *  
	 *  @param string	$sql	Database SQL update query
	 *  @param array	$params	Query parameters (required)
	 *  @param string	$dsn	Database string
	 *  @return bool		Update status
	 */
	public function setUpdate(
		string		$sql,
		array		$params,
		string		$dsn		= ''
	) : bool {
		if ( empty( $dsn ) ) {
			return false;
		}
		
		$res = $this->dataExec( $sql, $params, 'success', $dsn );
		return empty( $res ) ? false : true;
	}
	
	/**
	 *  Insert record into database and return last ID
	 *  
	 *  @param string	$sql	Database SQL insert
	 *  @param array	$params	Insert parameters (required)
	 *  @param string	$dsn	Database string
	 *  @return int			Last insert ID
	 */
	public function setInsert(
		string		$sql,
		array		$params,
		string		$dsn		= ''
	) : int {
		if ( empty( $dsn ) ) {
			return 0;
		}
	
		$res = $this->dataExec( $sql, $params, 'insert', $dsn );
		return 
		empty( $res ) ? 0 : ( \is_numeric( $res ) ? ( int ) $res : 0 );
	}
	
	/**
	 *  Get a single item row by ID
	 *  
	 *  @return array
	 */
	public function getSingle(
		int		$id,
		string		$sql,
		string		$dsn		= ''
	) : array {
		if ( empty( $dsn ) ) {
			return [];
		}
		
		$data	= $this->getResults( $sql, [ ':id' => $id ], $dsn );
		if ( empty( $data ) ) {
			return $data[0];
		}
		return [];
	}
}


