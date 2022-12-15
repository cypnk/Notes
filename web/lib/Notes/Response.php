<?php declare( strict_types = 1 );

namespace Notes;

class Response extends Message {
	
	/**
	 *  Content Security Policy and Permissions Policy
	 *  
	 *  @var array
	 */
	protected readonly $policy;
	
	/**
	 *  Parsed content security policy
	 *  
	 *  @var array
	 */
	protected $parsed	= [];
	
	/**
	 *  Message headers list
	 *  @var array
	 */
	protected array $headers;
	
	/**
	 *  Quoted security policy attribute helper
	 *   
	 *  @param string	$atr	Security policy parameter
	 *  @return string
	 */
	public function quoteSecAttr( string $atr ) : string {
		// Safe allow list
		static $allow	= [ 'self', 'src', 'none' ];
		$atr		= 
		\trim( \Notes\Util::unifySpaces( $atr ) );
		
		return 
		\in_array( $atr, $allow ) ? 
			$atr : '"' . \Notes\Util::cleanUrl( $atr ) . '"'; 
	}
	
	/**
	 *  Parse security policy attribute value
	 *  
	 *  @param string	$key	Permisisons policy identifier
	 *  @param mixed	$policy	Policy value(s)
	 *  @return string
	 */
	public function parsePermPolicy(
		string		$key, 
				$policy	= null 
	) : string {
		// No value? Send empty set E.G. "interest-cohort=()"
		if ( empty( $policy ) ) {
			return \Notes\Util::bland( $key ) . '=()';
		}
		
		// Send specific value(s) E.G. "fullscreen=(self)"
		return 
		\Notes\Util::bland( $key ) . '=(' . 
		( \is_array( $policy ) ? 
			\implode( ' ', 
				\array_map( 
					[ $this, 'quoteSecAttr' ], 
					$policy 
				) 
			) : 
			$this->quoteSecAttr( ( string ) $policy ) ) . 
		')';
	}
	
	/**
	 *  Content Security and Permissions Policy settings
	 *  
	 *  @param string	$policy		Security policy header
	 *  @return string
	 */
	public function securityPolicy( string $policy ) : string {
		// Load defaults
		if ( !isset( $this->policy ) ) {
			$p = $this->config->setting( 
				'default_secpolicy', 'json' 
			);
			
			$this->policy = empty( $p ) ? [] : $p;
		}
		
		switch ( $policy ) {
			case 'common':
			case 'common-policy':
				if ( isset( $this->parsed['common'] ) ) {
					return $this->parsed['common'];
				}
				
				// Common header override
				$cfj = 
				$this->config->setting( 
					'common-policy', 
					'lines', 
					'\\Notes\\Util::bland' 
				);
				$this->parsed['common'] = \implode( "\n", $cfj );
				
				return $this->parsed['common'];
				
			case 'permissions':
			case 'permissions-policy':
				if ( isset( $this->parsed['permissions'] ) ) {
					return $this->parsed['permissions'];
				}
				
				$prm = [];
				
				// Permissions policy override
				$cfj = $this->config->setting( 
					'permisisons-policy', 'json' 
				);
				
				$def = 
				$this->policy['permissions-policy'] ?? [];
				
				$pjp = 
				\is_array( $cfj ) ? 
					\array_merge( $def, $cfj ) : $def;
				
				foreach ( $pjp as $k => $v ) {
					$prm[]	= 
					$this->parsePermPolicy( $k, $v );
				}
				
				$this->parsed['permissions'] = 
					\implode( ', ', $prm );
				return $this->parsed['permissions'];
			
			case 'content-security':
			case 'content-security-policy':
				if ( isset( $this->parsed['content'] ) ) {
					return $this->parsed['content'];
				}
				
				$csp = '';
				$cjp = 
				$this->policy['content-security-policy'] ?? [];
				
				// Approved frame ancestors ( for embedding media )
				$raw = 
				$this->config->setting( 
					'frame_whitelist', 
					'lines', 
					'\\Notes\\Util::cleanUrl' 
				);
				
				$raw = \array_unique( \array_filter( $raw ) );
				$frm = \implode( ' ', $raw );
				
				foreach ( $cjp as $k => $v ) {
					$csp .= 
					( 0 == \strcmp( $k, 'frame-ancestors' ) ) ? 
						"$k $v $frm;" : "$k $v;";
				}
				$this->parsed['content'] = \rtrim( $csp, ';' );
				return $this->parsed['content'];
		}
		
		return '';
	}
	
	/**
	 *  Set expires header
	 */
	public function setCacheExp( int $ttl ) {
		$this->headers[] = 'Cache-Control: max-age=' . $ttl;
		$this->headers[] = 
			'Expires: ' . 
			\gmdate( 'D, d M Y H:i:s', time() + $ttl ) . 
			' GMT';
	}
	
	/**
	 *  Clean the output buffer without flushing
	 *  
	 *  @param bool		$ebuf		End buffers
	 */
	public function cleanOutput( bool $ebuf = false ) {
		if ( $ebuf ) {
			while ( \ob_get_level() > 0 ) {
				\ob_end_clean();
			}
			return;
		}
		
		while ( \ob_get_level() && \ob_get_length() > 0 ) {
			\ob_clean();
		}
	}
	
	/**
	 *  Remove previously set headers, output
	 */
	public function scrubOutput() {
		// Scrub output buffer
		\ob_clean();
		\header_remove( 'Pragma' );
		
		// This is best done in php.ini : expose_php = Off
		\header( 'X-Powered-By: nil', true );
		\header_remove( 'X-Powered-By' );
	}
	
	/**
	 *  Flush and optionally end output buffers
	 *  
	 *  @param bool		$ebuf		End buffers
	 */
	public function flushOutput( bool $ebuf = false ) {
		if ( $ebuf ) {
			while ( \ob_get_level() > 0 ) {
				\ob_end_flush();
			}
		} else {
			while ( \ob_get_level() > 0 ) {
				\ob_flush();	
			}
		}
		flush();
	}
	
	/**
	 *  Flush and end all output buffers
	 *  
	 *  @param bool		$done	End execution if true
	 */
	public function flushBuffers( bool $done = false ) {
		$this->flushOutput( true );
		
		if ( $done ) {
			die();
		}
	}
	
	/**
	 *  Erase and end all output buffers
	 *  
	 *  @param bool		$done	End execution if true
	 */
	public function eraseBuffers( bool $done = false ) {
		$this->cleanOutput( true );
		
		if ( $done ) {
			die();
		}
	}
	
	/**
	 *  Send all currently set headers
	 */
	public function sendHeaders() {
		$headers = \array_unique( $this->headers );
		foreach ( $headers as $h ) {
			\header( $h, true );
		}
	}
	
	/**
	 *  Visitor disconnect event helper
	 */
	public function visitorAbort( string $msg = 'File send' ) {
		$this->cleanOutput( true );
		if ( !\headers_sent() ) {
			$this->httpCode( 205 );
		}
		$this->notices[] = 'Client disconnected :' . $msg );
		die();
	}
	
	/**
	 *  Generate ETag from file path
	 */
	public function genEtag( string $path ) {
		static $tags		= [];
		
		if ( isset( $tags[$path] ) ) {
			return $tags[$path];
		}
		
		$tags[$path]		= [];
		
		// Find file size header
		$tags[$path]['fsize']	= \filesize( $path );
		
		// Send empty on failure
		if ( false === $tags[$path]['fsize'] ) {
			$tags[$path]['fmod'] = 0;
			$tags[$path]['etag'] = '';
			return $tags;
		}
		
		// Similar to Nginx ETag algo: 
		// Lowercase hex of last modified date and filesize
		$tags[$path]['fmod']	= \filemtime( $path );
		if ( false !== $tags[$path]['fmod'] ) {
			$tags[$path]['etag']	= 
			\sprintf( '%x-%x', 
				$tags[$path]['fmod'], 
				$tags[$path]['fsize']
			);
		} else {
			$tags[$path]['etag'] = '';
		}
		
		return $tags[$path];
	}
	
	/**
	 *  Prepare to send a file instead of an HTTP response
	 *  
	 *  @param string	$path		File path to send
	 *  @param int		$code		HTTP Status code
	 *  @param bool		$verify		Verify mime content type
	 */
	public function sendFilePrep( 
		string		$path, 
		int		$code		= 200, 
		bool		$verify		= true 
	) {
		$this->scrubOutput();
		$this->httpCode( $code );
		
		// Set content type if mime is found
		if ( $verify ) {
			$mime			= 
			static::detectMime( $path );
			$this->headers[]	= "Content-Type: {$mime}";
		}
		$this->headers[] = 
		"Content-Security-Policy: default-src 'self'";
		
		// Setup content security
		$this->preamble( '', false, false );
	}
	
	/**
	 *  Check If-None-Match header against given ETag
	 *  
	 *  @return true if header not set or if ETag doesn't match
	 */
	public function ifModified( $etag ) : bool {
		$mod = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
	
		if ( empty( $mod ) ) {
			return true;
		}
		
		return ( 0 !== \strcmp( $etag, $mod ) );
	}
	
	/**
	 *  Open a file stream in binary mode and set blocking mode
	 *  
	 *  @param mixed	$stream		File resource or false if initializing
	 *  @param string	$path		File path
	 *  @return resource|false
	 */
	public static function openStream( &$stream, string $path ) {
		$stream = \fopen( $path, 'rb' );
		if ( false === $stream ) {
			return;
		}
		\stream_set_blocking( $stream, false );
	}
	
	/**
	 *  Close opened stream
	 *  
	 *  @param resource	$stream		Open file stream
	 */
	public static function closeStream( &$stream ) {
		if ( false === $stream ) {
			return;
		}
		\fclose( $stream );
		$stream = false;
	}
	
	/**
	 *  File mime-type detection helper
	 *  
	 *  @param string	$path	Fixed file path
	 *  @return string
	 */
	public static function detectMime( string $path ) : string {
		$ext = \pathinfo( $path, \PATHINFO_EXTENSION ) ?? '';
		
		// Simpler text types
		switch( \strtolower( $ext ) ) {
			case 'txt':
				return 'text/plain';
				
			case 'css':
				return 'text/css';
				
			case 'js':
				return 'text/javascript';
				
			case 'svg':
				return 'image/svg+xml';
				
			case 'vtt':
				return 'text/vtt';
			
			case 'xsl':
				return 'application/xslt+xml';
			
			case 'atom':
				return 'application/atom+xml';
				
			case 'xml':
			case 'rss':
			case 'feed':
				return 'application/xml';
		}
		
		// Intercept potential mime warning as error
		set_error_handler( function( 
			$eno, $emsg, $efile, $eline 
		) use ( $path ) {
			$str	= 
			'Unable to detect mime of ' . $path . ' ' .  
				'Message: {msg} File: {file} Line: {line}';
				
			logException( 
				new \ErrorException( 
					$emsg, 0, $eno, $efile, $eline 
				), $str 
			);
		}, E_WARNING );
		
		// Detect other mime types
		$mime = \mime_content_type( $path );
		
		restore_error_handler();
		
		return ( false === $mime ) ? 
			'application/octet-stream' : 
			\strtolower( $mime );
	}
	
	/**
	 *  Stream content in chunks within starting and ending limits
	 *  
	 *  @param resource	$stream		Open file stream
	 *  @param int		$int		Starting offset
	 *  @param int		$end		Ending offset or end of file
	 *  @param callable	$flh		Flushing action
	 *  @param callable	$abr		Abort action
	 */
	public function streamChunks( &$stream, int $start, int $end, $flh, $abr ) {
		// Default chunk size
		$csize	= 
		$this->config->setting( 
			'stream_chunk_size', 
			static::STREAM_CHUNK_SIZE, 
			'int' 
		);
		$sent	= 0;
		
		$is_flh	= \is_callable( $flh );
		$is_abr	= \is_callable( $abr );
		
		\fseek( $stream, $start );
		
		while ( !\feof( $stream ) ) {
			
			// Check for aborted connection between flushes
			if ( \connection_aborted() ) {
				static::closeStream( $stream );
				if ( $is_abr ) {
					\call_user_func( $abr );
				}
				break;
			}
			
			// End reached
			if ( $sent >= $end ) {
				if ( $is_flh ) {
					\call_user_func( $flh );
				}
				break;
			}
			
			// Change chunk size when approaching the end of range
			if ( $sent + $csize > $end ) {
				$csize = ( $end + 1 ) - $sent;
			}
			
			// Reset limit while streaming
			\set_time_limit( 30 );
			
			$buf = \fread( $stream, $csize );
			echo $buf;
			
			$sent += \Notes\Util::strsize( $buf );
			
			if ( $is_flh ) {
				\call_user_func( $flh );
			}
		}
	}
	
	
	/**
	 *  Finish file sending functionality
	 *  
	 *  @param string	$path		File path to send
	 */
	public function sendFileFinish( $path, bool $nosend = false ) {
		// Prepare content length and etag headers
		$tags	= $this->genEtag( $path );
		$fsize	= $tags['fsize'];
		$etag	= $tags['etag'];
		$stream = false;
	
	
		// Prepare content length and etag headers
		if ( $nosend ) {
			$this->scrubOutput();
			$this->sendHeaders();
			
			// Flush and end buffers
			$this->flushBuffers( true );
		}
		
		if ( false !== $fsize ) {
			$climit = 
			$this->config->setting( 
				'stream_chunk_limit', 
				static::STREAM_CHUNK_LIMIT, 
				'int' 
			);
			
			// Prepare resource if this is a large file
			if ( $fsize > $climit ) {
				static::openStream( $stream, $path );
				if ( false === $stream ) {
					// Don't send error or this may loop
					// Error handlers also use this function
					$this->errors[] = 'Error opening ' . $path;
					die();
				}
			}
			
			$this->headers[] = "Content-Length: {$fsize}";
			if ( !empty( $etag ) ) {
				$this->headers[] = "ETag: {$etag}";
			}	
			
			if ( $this->config->setting( 'show_modified', 'int' ) ) {
				$fmod	= $tags['fmod'];
				if ( !empty( $fmod ) ) {
					$this->headers[]  =
					'Last-Modified: ', 
						\Notes\Util::dateRfcFile( 
						$fmod 
					);
				}
			}
		}
		
		
		$this->scrubOutput();
		$this->sendHeaders();
		
		if ( $this->ifModified( $etag ) && !$nosend ) {
			if ( $stream === false ) {
				\readfile( $path );
				return;
			}
			$this->streamChunks( 
				$stream, 0, $fsize, 
				[ $this, 'flushBuffers' ], 
				[ $this, 'visitorAbort' ]
			);
			static::closeStream();
		}
		
		// Flush and end buffers
		$this->flushBuffers( true );
	}
	
	/**
	 *  Send file-specific headers
	 *  
	 *  @param string	$dsp		Content disposition
	 *  @param string	$fname		Download file name
	 *  @param bool		$cache		Set file cache
	 */
	public function sendFileHeaders( string $dsp, string $fname, bool $cache ) {
		// Setup file parameters
		$this->headers[] = 
		"Content-Disposition: {$dsp}; filename=\"{$fname}\"";
	
		// If cached, set long expiration
		if ( $cache ) {
			$this->headers[] = 
			'Cache-Control:public, max-age=31536000';
			return;
		}
		
		// Uncached
		$this->headers[] = 'Cache-Control: must-revalidate';
		$this->headers[] = 'Expires: 0';
		$this->headers[] = 'Pragma: no-cache';
	}
	
	/**
	 *  Prepare to send back a dynamically generated file (E.G. Captcha)
	 *  This function is a plugin helper
	 *  
	 *  @param string	$mime		Generated file's mime content-type
	 *  @param string	$fname		File name
	 *  @param int		$code		HTTP Status code
	 *  @param bool		$cache		Cache generated file if true
	 */
	public function sendGenFilePrep( 
		string		$mime, 
		string		$fname, 
		int		$code		= 200, 
		bool		$cache		= false 
	) {
		$this->sendFilePrep( $fname, $code, false );
		$this->headers[] = "Content-Type: {$mime}";
		$this->sendFileHeaders( 'inline', $fname, $cache );
	}
	
	/**
	 *  Send a physical file if it exists
	 *  
	 *  @param string	$path		Physical path relative to script
	 *  @param bool		$down		Prompt download if true
	 *  @param int		$code		HTTP Status code
	 */
	public function sendFile(
		string		$path,
		bool		$down		= false, 
		bool		$cache		= true,
		int		$code		= 200
	) : bool {
		// No file found
		if ( !\is_readable( $path ) || !\is_file( $path ) ) {
			return false;
		}
		
		// Client save path
		$fname	= \basename( $path );
		
		// Show inline or prompt download
		$dsp	= $down ? 'attachment' : 'inline';
		
		// Prepare to send file
		$this->sendFilePrep( $path, $code );
		$this->sendFileHeaders( $dsp, $fname, $cache );
		
		// Finish sending file
		$this->sendFileFinish( $path );
		return true;
	}
	
	/**
	 *  Print headers, content, and end execution
	 *  
	 *  @param int		$code		HTTP Status code
	 *  @param string	$content	Page data to send to client
	 *  @param bool		$cache		Cache page data if true
	 */
	public function send(
		int		$code		= 200,
		string		$content	= '',
		bool		$cache		= false,
		bool		$feed		= false
	) {
		$this->scrubOutput();
		$this->httpCode( $code );
		
		if ( $feed ) {
			$this->headers[] = 
			'Content-Type: application/xml; charset=utf-8';
			
			$this->headers[] = 
			'Content-Disposition: inline';
			$this->preamble( '', true, false );
		} else {
			$this->preamble();
		}
		
		// Also save to cache?
		if ( $cache ) {
			$ex	= 
			$this->config->setting( 'cache_ttl', 'int' );
			
			$this->setCacheExp( $ex );
			// TODO: Schedule 'saveCache' with full URI
		}
		
		// TODO: Trigger 'contentsend' event and schedule 'ob_end_flush'
		
		// Check gzip prerequisites
		if ( $code != 304 && \extension_loaded( 'zlib' ) ) {
			\ob_start( 'ob_gzhandler' );
		}
		
		$this->sendHeaders();
		
		// Send to visitor
		echo $content;
		
		// Flush and end buffers
		$this->flushBuffers( true );
	}
	
	/**
	 *  Error file sending helper
	 *  
	 *  @param int		$code		Error code number
	 *  @return bool			True on success
	 */
	protected function sendErrorFile( int $code ) : bool {
		// Try to send generic file error, if it exists, and exit
		$path = 
		$this->config->setting( 'error_path', 'string' );
		
		if ( empty( $path ) ) {
			return false;
		}
		
		$path	= \Notes\Util::slashPath( $path, true );
		
		// Send generic 50x if in series
		if ( \in_array( $code, [ 500, 501, 503 ] ) ) {
			if ( $this->sendFile( 
				$path . '50x.html', false, false, $code 
			) ) {
				$this->flushBuffers( true );
			}
		}
		
		switch( $code ) {
			case 400:
			case 401:
			case 403:
			case 404:
			case 405:
			case 429:
			case 500:
			case 501:
			case 503:
				$path = $path . $code . '.html';
				break;
		}
	
		// TODO: Trigger error file sending event
		if ( $this->sendFile( $path, false, false, $code ) ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 *  Send error message
	 */
	public function sendError( int $code, string $body ) {
		// Try to send a static error file if it exists first
		if ( $this->sendErrorFile( $code ) ) {
			$this->flushBuffers( true );
		}
		
		// Send error as-is and exit
		$this->send( $code, $body, false, false );
	}
	
	
	/**
	 *  Send file with ETag data
	 *  
	 *  @param string	$path	File path after confirming it exists
	 */
	public function sendWithEtag( $path ) : bool {
		$tags	= $this->genEtag( $path );
		
		// Couldn't generate ETag?
		// Either filesize() or filemtime() failed
		if ( empty( $tags['etag'] ) ) {
			return false;
		}
		
		// Create return code based on returned ETag
		$code	= $this->ifModified( $tags['etag'] )? 200 : 304;
		
		// Send on success
		return $this->sendFile( $path, false, true, $code );
	}
	
	/**
	 *  Invalid file range error page helper
	 */
	public function sendRangeError() {
		$this->sendError( 416, "File range too large" );
	}
	
	/**
	 *  Handle ranged file request
	 *  
	 *  @param array	$frange		List of file ranges
	 *  @param string	$path		Absolute file path
	 *  @param bool		$dosend		Send file ranges if true
	 *  @return bool
	 */
	public function sendFileRange(
		array		$frange, 
		string		$path, 
		bool		$dosend 
	) : bool {
		$fsize	= \filesize( $path );
		$fend	= $fsize - 1;
		$totals	= 0;
		
		// Check if any ranges are outside file limits
		foreach ( $frange as $r ) {
			if ( $r[0] >= $fend || $r[1] > $fend ) {
				$this->sendRangeError();
			}
			$totals += ( $r[1] > -1 ) ? 
				( $r[1] - $r[0] ) + 1 : ( $fend - $r[0] ) + 1;
		}
		
		if ( !$dosend ) {
			return true;
		}
		
		static::openStream( $stream, $path );
		if ( false === $stream ) {
			$this->errors[] = 'Error opening ' . $path;
			$this->sendError( 500, 'Resource error' );
		}
		
		// Prepare partial content
		$this->sendFilePrep( $path, 206, false );
		$this->headers[] = "Accept-Ranges: bytes";
		
		$mime	= static::detectMime( $path );
		
		// Generate boundary
		$bound	= \base64_encode( \hash( 'sha1', $path . $fsize, true ) );
		$this->headers[] = 
		"Content-Type: multipart/byteranges; boundary={$bound}";
		
		$this->headers[] = "Content-Length: {$totals}";
		
		$this->sendFileHeaders( 'inline', $fname, $cache );
		
		// Send any headers and end buffering
		$this->flushOutput( true );
		
		// Start fresh buffer
		\ob_start();
		
		$limit = 0;
		
		foreach ( $frange as $r ) {
			echo "\n--{$bound}";
			echo "Content-Type: {$mime}";
			if ( $r[1] == -1 ) {
				echo "Content-Range: bytes {$r[0]}-{$fend}/{$fsize}\n";
			} else {
				echo "Content-Range: bytes {$r[0]}-{$r[1]}/{$fsize}\n";
			}
			
			$limit = ( $r[1] > -1 ) ? $r[1] + 1 : $fsize;
			$this->streamChunks( 
				$stream, $r[0], $limit, 
				[ $this, 'flushOutput' ], 
				[ $this, 'visitorAbort' ] 
			);
		}
		
		$this->closeStream( $stream );
		$this->flushOutput( true );
		return true;
	}
	
	/**
	 *  Safety headers
	 *  
	 *  @param string	$chk	Content checksum
	 *  @param bool		$send	CSP Send Content Security Policy header
	 *  @param bool		$type	Send content type (html)
	 */
	public function preamble(
		string	$chk		= '', 
		bool	$send_csp	= true,
		bool	$send_type	= true
	) {
		if ( $send_type ) {
			$this->headers[] = 
			'Content-Type: text/html; charset=utf-8';
		}
		
		// Set default permissions policy header
		$perms = $this->securityPolicy( 'permissions-policy' );
		if ( !empty( $perms ) ) {
			$this->headers[] = 
				'Permissions-Policy: ' . $perms;
		}
		
		// If sending CSP and content checksum isn't used
		if ( $send_csp ) {
			$this->headers[] = 
			'Content-Security-Policy: ' . 
				securityPolicy( 'content-security-policy' );
			
		// Content checksum used
		} elseif ( !empty( $chk ) ) {
			$this->headers[] = 
			"Content-Security-Policy: default-src " .
				"'self' '{$chk}'"
		}
	}
	
	/**
	 *  Send list of supported HTTP request methods
	 */
	public function getAllowedMethods( bool $arr = false ) {
		$ap	= 
		$this->config->setting( 'allow_post', 'int' );
		if ( $arr ) {
			return $ap ?  
			[ 'get', 'post', 'head', 'options' ] : 
			[ 'get', 'head', 'options' ];
		}
		
		return $ap ? 
		'GET, POST, HEAD, OPTIONS' : 'GET, HEAD, OPTIONS';
	}
	
	/**
	 *  Send list of allowed methods in "Allow:" header
	 */
	public function sendAllowHeader() {
		$this->headers[] = 
			'Allow: ' . $this->getAllowedMethods();
	}
	
	/**
	 *  Create HTTP status code message
	 *  
	 *  @param int		$code		HTTP Status code
	 */
	public function httpCode( int $code ) {
		$green	= [
			200, 201, 202, 204, 205, 206, 
			300, 301, 302, 303, 304,
			400, 401, 403, 404, 405, 406, 407, 409, 410, 411, 412, 
			413, 414, 415,
			500, 501
		];
		
		if ( \in_array( $code, $green ) ) {
			\http_response_code( $code );
			
			// Some codes need additional headers
			switch( $code ) {
				case 405:
					$this->sendAllowHeader();
					break;
			}
			
			return;
		}
		
		$prot = $this->getProtocol();
		
		// Special cases
		switch( $code ) {
			case 416:
				$this->headers[] = 
				"$prot $code " . 'Range Not Satisfiable';
				return;
				
			case 425:
				$this->headers[] =
				"$prot $code Too Early";
				return;
			
			case 429:
				$this->headers[] =
				"$prot $code Too Many Requests";
				return;
				
			case 431:
				$this->headers[] = 
				"$prot $code " . 
				'Request Header Fields Too Large';
				return;
			
			case 503:
				$this->headers[] = 
				"$prot $code Service Unavailable";
				return;
		}
		
		// Log unkown status type
		$this->errors[] = 'Unknown status code "' . $code . '"';
		
		\http_response_code( 500 );
		$this->flushBuffers( true );
	}
	
	/**
	 *  Redirect with status code
	 *  
	 *  @param int		$code	HTTP Status code
	 *  @param string	$path	Full URL to from current domain
	 */
	public function redirect(
		int		$code		= 200,
		string		$path		= ''
	) {
		$url	= \parse_url( $path );
		$host	= $url['host'] ?? '';
		
		// Arbitrary redirect attempt?
		if ( 0 !== \strcasecmp( $host, $_SERVER['SERVER_NAME'] ) ) {
			$this->errors[] = 'error', 'Invalid URL: ' . $path;
			$this->eraseBuffers( true );
		}
		
		// Get get current path
		$path	= $host . 
		\Notes\Util::slashPath( $url['path'] ?? '' );
		
		// Directory traversal
		$path	= \preg_replace( '/\.{2,}', '.', $path );
		
		if ( false === \headers_sent() ) {
			\header( 'Location: ' . $path, true, $code );
		}
		
		$this->eraseBuffers( true );
	}
}

