<?php declare( strict_types = 1 );
/**
 *  @file	/web/bootstrap.php
 *  @brief	Notes loader and environment constants
 */

// Prevent direct calls
if ( 0 == \strcmp(
	\basename( \strtolower( $_SERVER['SCRIPT_NAME'] ), '.php' ), 
	'bootstrap' 
) ) { 
	\ob_end_clean();
	die();
};



/**
 *  Begin configuration
 */

// Path to this file's directory
define( 'PATH',	\realpath( \dirname( __FILE__ ) ) . '/' );

// Notes core class files
define( 'NOTES_LIB',	\PATH . 'lib/Notes/' );

// Notes extension modules
define( 'NOTES_MOD',	\PATH . 'lib/NotesModules/' );

// Static files served to public
define( 'NOTES_PUBLIC',	\PATH . 'public/' );


// Storage directory. Must be writable (chmod -R 0755 on *nix)
// This configuration implies storage is outside the web root (recommended)
define( 'WRITABLE',	\realpath( \dirname( __FILE__, 2 ) ) . '/data/' );

// Content database
define( 'DATA',		\WRITABLE . 'documents.db' );

// Visitor session database
define( 'SESSIONS',	\WRITABLE . 'sessions.db' );

// Event log database
define( 'LOGS',		\WRITABLE . 'logs.db' );

// Cached content views database
define( 'CACHE',	\WRITABLE . 'cache.db' );


// Error log file
define( 'ERRORS',	\WRITABLE . 'errors.log' );

// Notification log file
define( 'NOTICES',	\WRITABLE . 'notices.log' );

// Maximum log file size before rolling over (in bytes)
define( 'MAX_LOG_SIZE',		5000000 );

// Sender email used to notify of notices/errors
define( 'MAIL_FROM',		'domain@localhost' );

// Destination email for notices/errors
define( 'MAIL_RECEIVE',		'domain@localhost' );


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
 *  Exception recording helper
 *  
 *  @param Exception	$e	Thrown error
 *  @param string	$msg	Optional override of default error format
 */
function logException( \Exception $e, ?string $msg = null ) {
	$msg ??= 'Error: {msg} File: {file} Line: {line}';
	
	\messages( 
		'error', 
		\strtr( $msg, [
			'{msg}'		=> $e->getMessage(),
			'{file}'	=> $e->getFile(),
			'{line}'	=> $e->getLine()
		] )
	);
}

/**
 *  Check log file size and rollover, if needed
 *  
 *  @param string	$file	Log file name
 */
function logRollover( string $file ) {
	// Nothing to rollover
	if ( !\file_exists( $file ) ) {
		return;
	}
	
	$fs	= \filesize( $file );
	
	// Empty file
	if ( false === $fs ) {
		return;
	}
	
	if ( $fs > \MAX_LOG_SIZE ) {
		$f = \rtrim( \dirname( $file ), '/\\' ) . 
			\DIRECTORY_SEPARATOR . 
			\basename( $file ) . '.' . 
			\gmdate( 'Ymd\THis' ) . '.log';
		\rename( $file, $f );
	}
}

/**
 *  Write messages to given error file
 */
function logToFile( string $msg, string $dest ) {
	logRollover( $dest );
	\error_log( 
		\gmdate( 'D, d M Y H:i:s T', time() ) . "\n" . 
			$msg . "\n\n\n\n", 
		3, 
		$dest
	);
}

/**
 * Send email to authorized recipient
 */
function logToMail( string $msg, int $i, int $c ) {
	static $format;
	static $host;
	
	if ( !isset( $format ) ) {
		$format = 
		'From: ' . \MAIL_FROM . "\r\n" .  
		"MIME-Version: 1.0\r\n" . 
		"Content-Type: text/plain; charset=UTF-8\r\n" . 
		"Content-Transfer-Encoding: base64\r\n" . 
		"X-Mailer: Notes\r\n";
	}
	
	if ( !isset( $host ) ) {
		$h = \gethostname();
		$host = ( false === $h ) ? 
			'' : "\r\nX-Notes-Host: {$h}";
	}
	
	\error_log( 
		\base64_encode( $msg ), 1, \MAIL_RECEIVE, 
		$format  . 
			"X-Notes-Batch: {$i} of {$c}\r\n" . 
			'X-Notes-Idx: ' . hrtime( true ) . $host
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
			
			case 'mail':
				$i = 1;
				$c = count( $v );
				foreach( $v as $m ) {
					logToMail( $m, $i, $c );
					$i++;
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
			break;
		}
		
		messages( 'error', 'Unable to read file: ' . $file );
		die();
	}
} );

// Start controller
$controller	= 
new \Notes\Controller( ['\\Notes\\Data'] );


