<?php declare( strict_types = 1 );
/**
 *  @file	/web/bootstrap.php
 *  @brief	Notes loader and environment constants
 */

// Path to this file's directory
define( 'PATH',	\realpath( \dirname( __FILE__ ) ) . '/' );

// Notes core class files
define( 'NOTES_LIB',	\PATH . 'lib/Notes/' );

// Notes extension modules
define( 'NOTES_MOD',	\PATH . 'lib/NotesModules/' );


// Storage directory. Must be writable (chmod -R 0755 on *nix)
// This configuration implies storage is outside the web root (recommended)
define( 'WRITABLE',	\realpath( \dirname( __FILE__, 2 ) ) . '/data/' );

// Content database
define( 'DATA',		\WRITABLE . 'documents.db' );

// Visitor session database
define( 'SESSIONS',	\WRITABLE . 'sessions.db' );

// Cached content views database
define( 'CACHE',	\WRITABLE . 'cache.db' );


// Error log file
define( 'ERRORS',	\WRITABLE . 'errors.log' );

// Notification log file
define( 'NOTICES',	\WRITABLE . 'notices.log' );


// Static files

// Uploaded and editable file directory
define( 'NOTES_FILES',	\WRITABLE . 'uploads/' );

// Temporary data directory
define( 'NOTES_TEMP',	\WRITABLE . 'temp/' );

// Print spool, outbox etc...
define( 'NOTES_JOBS',	\WRITABLE . 'jobs/' );



/**
 *  Environment preparation
 */
\date_default_timezone_set( 'UTC' );
\ignore_user_abort( true );
\ob_end_clean();




/**
 *  Isolated message holder
 *  
 *  @param string	$type		Message type, determines storage location
 *  @param string	$message	Log content body
 *  @param bool		$ret		Optional, returns stored log if true
 */
function messages( string $type, string $message, bool $ret = false ) {
	static $log	= [];
	
	if ( $ret && $message ) {
		return $log;
	}
	
	if ( !isset( $log[$type] ) ) {
		$log[$type] = [];	
	}
	
	// Clean message to file safe format
	$log[$type][] = 
	\preg_replace( 
		'/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F[\x{fdd0}-\x{fdef}\p{Cs}\p{Cf}\p{Cn}]/u', 
		'', 
		$message 
	);
}

/**
 *  Write messages to given error file
 */
function logToFile( string $msg, string $dest ) {
	\error_log( 
		\gmdate( 'D, d M Y H:i:s T', time() ) . "\n" . 
			$msg . "\n\n\n\n", 
		3, 
		$dest
	);
}

/**
 *  Internal error logger
 */
\register_shutdown_function( function() {
	$msgs = messages( '', '', true );
	if ( empty( $msgs ) ) {
		return;
	}
	
	foreach ( $msgs as $k => $v ) {
		switch ( $k ) {
			case 'error':
			case 'errors':
				foreach( $v as $m ) {
					logToFile( $m, \ERRORS );
				}
				break;
				
			case 'notice':
			case 'notices':
				foreach( $v as $m ) {
					logToFile( $m, \NOTICES );
				}
				break;
		}
	}
} );

/**
 *  Class loader
 */
\spl_autoload_register( function( $class ) {
	// Path replacements
	static $rpl	= [ '\\' => '/', '-' => '_' ];
	
	// Class prefix replacements
	static $prefix	= [
		'Notes\\'		=> \NOTES_LIB,
		'Notes\\Modules\\'	=> \NOTES_MOD
	];
	
	foreach ( $prefix as $k => $v ) {
		// Skip non-Notes classes
		if ( !\str_starts_with( $class, $k ) ) {
			continue;
		}
		
		// Build file path
		$file	= $v . 
		\strtr( \substr( $class, \strlen( $k ) ), $rpl ) . '.php';
		
		if ( \is_readable( $file ) ) {
			require $file;
		} else {
			messages( 'error', 'Unable to read file: ' . $file );
		}
		break;
	}
} );


