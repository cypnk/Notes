<?php declare( strict_types = 1 );

namespace Notes;

class Request extends Message {
	
	/**
	 *  Visitor's IP address (IPv4 or IPv6)
	 *  @var string
	 */
	protected readonly string $ip;
	
	/**
	 *  Raw IP address, unfiltered
	 *  @var string
	 */
	protected readonly string $raw_ip;
	
	/**
	 *  Current request guessed to be a secure connection, if true
	 *  @var bool
	 */
	protected readonly bool $secure;
	
	/**
	 *  Forwarded headers from reverse proxy, load balancer etc...
	 *  @var array
	 */
	protected readonly array $forwarded;
	
	/**
	 *  Raw user agent header sent by visitor
	 *  @var string
	 */
	protected readonly string $user_agent;
	
	/**
	 *  Current HTTP request method E.G. get, post, head etc...
	 *  @var string
	 */
	protected readonly string $request_method;
	
	/**
	 *  Accept languages sorted by priority
	 *  @var array
	 */
	protected readonly array $language;
	
	/**
	 *  Current server host name
	 *  @var string
	 */
	protected readonly string $host;
	
	/**
	 *  Prefix URL including hostname and protocol
	 *  @var string
	 */
	protected readonly string $web;
	
	/**
	 *  Full request path
	 *  @var string
	 */
	protected readonly string $url;
	
	/**
	 *  File request section ranges
	 *  @var array
	 */
	protected readonly array $file_ranges;
	
	/**
	 *  Message headers list
	 *  @var array
	 */
	protected readonly array $headers;
	
	/**
	 *  Message headers list
	 *  @var array
	 */
	public readonly array $lv_headers;
	
	/**
	 *  Requested path tree starting with host
	 *  @var array
	 */
	public readonly array $path_tree;
	
	/**
	 *  Process HTTP_* variables
	 *  
	 *  @param bool		$lower		Get array keys in lowercase
	 *  @return array
	 */
	public function httpHeaders( bool $lower = false ) : array {
		if ( $lower ) {
			if ( isset( $this->lv_headers ) ) {
				return $this->lv_headers;
			}
		} else {
			if ( isset( $this->headers ) ) {
				return $this->headers;
			}
		}
		
		$val	= [];
		$lval	= [];
		foreach ( $_SERVER as $k => $v ) {
			if ( 0 === \strncasecmp( $k, 'HTTP_', 5 ) ) {
				$a = explode( '_' ,$k );
				\array_shift( $a );
				\array_walk( $a, function( &$r ) {
					$r = \ucfirst( \strtolower( $r ) );
				} );
				$val[ \implode( '-', $a ) ] = $v;
				$lval[ \strtolower( \implode( '-', $a ) ) ] = $v;
			}
		}
		
		$this->headers		= $val;
		$this->lv_headers	= $lval;
		
		return $lower ? $lval : $val;
	}
	
	
	/**
	 *  Get the first non-empty server parameter value if set
	 *  
	 *  @param array	$headers	Server parameters
	 *  @param array	$terms		Searching terms
	 *  @param bool		$case		Search only in lowercase if true
	 *  @return mixed
	 */
	public static function serverParamWhite( 
		array		$headers, 
		array		$terms, 
		bool		$case		= false 
	) {
		$found	= null;
		
		foreach ( $headers as $h ) {
			// Skip unset or empty keys
			if ( empty( $_SERVER[$h] ) ) {
				continue;
			}
			
			// Search in lowercase
			if ( $case ) {
				$lc	= 
				\array_map( '\Notes\Util::lowercase', $terms );
				
				$sh	= \Notes\Util::lowercase( $_SERVER[$h] );
				$found	= \in_array( $sh, $lc ) ? $sh : '';
			} else {
				$found	= 
				\in_array( $_SERVER[$h], $terms ) ? 
					$_SERVER[$h] : '';
			}
			break;
		}
		return $found;
	}
	
	/**
	 *  Forwarded HTTP header chain from load balancer
	 *  
	 *  @return array
	 */
	public function getForwarded() : array {
		if ( isset( $this->forwarded ) ) {
			return $this->forwarded;
		}
		
		$fwd	= [];
		$terms	= 
			$_SERVER['HTTP_FORWARDED'] ??
			$_SERVER['FORWARDED'] ?? 
			$_SERVER['HTTP_X_FORWARDED'] ?? '';
		
		// No headers forwarded
		if ( empty( $terms ) ) {
			return [];
		}
		
		$pt	= explode( ';', $terms );
		
		// Gather forwarded values
		foreach ( $pt as $p ) {
			// Break into comma delimited list, if any
			$chain = \Notes\Util::trimmedList( $p );
			if ( empty( $chain ) ) {
				continue;
			}
			
			foreach ( $chain as $c ) {
				$k = explode( '=', $c );
				// Skip empty or odd values
				if ( count( $k ) != 2 ) {
					continue;
				}
				
				// Existing key?
				if ( isset( $fwd[$k[0]] ) ) {
					// Existing array? Append
					if ( \is_array( $fwd[$k[0]] ) ) {
						$fwd[$k[0]][] = $k[1];
					
					// Multiple values? 
					// Convert to array and then append new
					} else {
						$tmp		= $fwd[$k[0]];
						$fwd[$k[0]]	= [];
						$fwd[$k[0]][]	= $tmp;
						$fwd[$k[0]][]	= $k[1];
	 				}
				// Fresh value
				} else {
					$fwd[$k[0]] = $k[1];
				}
			}
		}
		$this->forwarded = $fwd;
		return $fwd;
	}
	
	/**
	 *  Get the current IP address connection chain including given proxies
	 *  
	 *  @return array
	 */
	public function getProxyChain() : array {
		static $chain;
		
		if ( isset( $chain ) ) {
			return $chain;
		}
		
		$chain = 
		\Notes\Util::trimmedList( 
			$_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
			$_SERVER['HTTP_CLIENT_IP'] ?? 
			$_SERVER['REMOTE_ADDR'] ?? '' 
		);
		
		return $chain;
	}
	
	/**
	 *  Get IP address (best guess)
	 *  
	 *  @return string
	 */
	public function getIP() : string {
		if ( isset( $this->ip ) ) {
			return $this->ip;
		}
		
		$fwd	= $this->getForwarded();
		
		$ip	= '';
		// Get IP from reverse proxy, if set
		if ( \array_key_exists( 'for', $fwd ) ) {
			$ip = 
			\is_array( $fwd['for'] ) ? 
				\array_shift( $fwd['for'] ) : 
				( string ) $fwd['for'];
		
		// Get from sent headers
		} else {
			$raw = $this->getProxyChain();
			if ( empty( $raw ) ) {
				$ip = '';
				return '';
			}
			
			$ip	= \array_shift( $raw );
		}
		
		$this->raw_ip	= $ip;
		
		$skip		= 
		$this->config->setting( 'skip_local', 'int' );
		
		$va		=
		( $skip ) ?
		\filter_var( $ip, \FILTER_VALIDATE_IP ) : 
		\filter_var(
			$ip, 
			\FILTER_VALIDATE_IP, 
			\FILTER_FLAG_NO_PRIV_RANGE | 
			\FILTER_FLAG_NO_RES_RANGE
		);
		
		$ip		= ( false === $va ) ? '' : $ip;
		$this->ip	= $ip;
		return $ip;
	}
	
	/**
	 *  Guess if current request is secure
	 */
	public function isSecure() : bool {
		if ( isset( $this->secure ) ) {
			return $this->secure;
		}
		
		$ssl	= $_SERVER['HTTPS'] ?? '0';
		$frd	= 
			$_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 
			$_SERVER['HTTP_X_FORWARDED_PROTOCOL'] ?? 
			$_SERVER['HTTP_X_URL_SCHEME'] ?? 'http';
		
		if ( 
			0 === \strcasecmp( $ssl, 'on' )		|| 
			0 === \strcasecmp( $ssl, '1' )		|| 
			0 === \strcasecmp( $frd, 'https' )
		) {
			$this->secure = true;
		} else {
			$this->secure = 
			( 443 == 
				( int ) ( 
					$_SERVER['SERVER_PORT'] ?? 80 
				) 
			);
		}
		
		return $this->secure;
	}
	
	
	/**
	 *  Standard request parameter helpers
	 */
	
	/**
	 *  Browser User Agent
	 *  
	 *  @return string
	 */
	public function getUA() : string {
		if ( isset( $this->user_agent ) ) {
			return $this->user_agent;
		}
		$this->user_agent	= 
			trim( $_SERVER['HTTP_USER_AGENT'] ?? '' );
		return $this->user_agent;
	}
	
	/**
	 *  Get full request URI
	 *  
	 *  @return string
	 */
	public function getURI() : string {
		if ( isset( $this->uri ) ) {
			return $this->uri;
		}
		$this->uri	= $_SERVER['REQUEST_URI'] ?? '';
		return $this->uri;
	}
	
	/**
	 *  Current querystring, if present
	 *  
	 *  @return string
	 */
	public function getQS() : string {
		if ( isset( $this->querystring ) ) {
			return $this->querystring;
		}
		$this->querystring	= 
			$_SERVER['QUERY_STRING'] ?? '';
		return $this->querystring;
	}
	
	/**
	 *  Current client request method
	 *  
	 *  @return string
	 */
	public function getMethod() : string {
		if ( isset( $this->request_method ) ) {
			return $this->request_method;
		}
		$this->request_method = 
		\strtolower( trim( $_SERVER['REQUEST_METHOD'] ?? '' ) );
		return $this->request_method;
	}
	
	/**
	 *  Visitor's preferred languages based on Accept-Language header
	 *  
	 *  @return array
	 */
	public function getLang() : array {
		if ( isset( $this->language ) ) {
			return $this->language;
		}
		
		$found	= [];
		$lang	= 
		\Notes\Util::bland( 
			$this->httpheaders( true )['accept-language'] ?? '' 
		);
		
		// No header?
		if ( empty( $lang ) ) {
			return [];
		}
		
		// Find languages by locale and priority
		\preg_match_all( 
			'/(?P<lang>[^-;,\s]{2,8})' . 
			'(?:-(?P<locale>[^;,\s]{2,8}))?' . 
			'(?:;q=(?P<weight>[0-9]{1}(?:\.[0-9]{1})))?/is',
			$lang,
			$matches
		);
		$matches =
		\array_filter( 
			$matches, 
			function( $k ) {
				return !\is_numeric( $k );
			}, \ARRAY_FILTER_USE_KEY 
		);
		
		if ( empty( $matches ) ) {
			return [];
		}
		
		// Re-arrange
		$c	= count( $matches );
		for ( $i = 0; $i < $c; $i++ ) {
			
			foreach ( $matches as $k => $v ) {
				if ( !isset( $found[$i] ) ) {
					$found[$i] = [];
				}
				
				switch ( $k ) {
					case 'lang':
						$found[$i][$k] = 
						empty( $v[$i] ) ? '*' : $v[$i];
						break;
						
					case 'locale':
						$found[$i][$k] = 
						empty( $v[$i] ) ? '' : $v[$i];
						break;
						
					case 'weight':
						// Lower global or empty language priority
						if ( 
							empty( $matches['lang'][$i] ) ||
							0 == \strcmp( $found[$i]['lang'], '*' )
						) {
							$found[$i][$k] = 
							( float ) ( empty( $v[$i] ) ? 0 : $v[$i] );
						} else {
							$found[$i][$k] = 
							( float ) ( empty( $v[$i] ) ? 1 : $v[$i] );						
						}
						break;
				
					default:
						// Anything else, send as-is
						$found[$i][$k] = 
						empty( $v[$i] ) ? '' : $v[$i];
				}
			}
		}
		
		// Sorting columns
		$weight = \array_column( $found, 'weight' );
		$locale	= \arary_column( $found, 'locale' );
		
		// Sort by weight priority, followed by locale
		$this->language = 
		\array_multisort( 
			$weight, \SORT_DESC, 
			$locale, \SORT_ASC, 
			$found
		) ? $found : [];
		
		return $this->language;
	}
	
	/**
	 *  Get requested file range, return [-1] if range was invalid
	 *  
	 *  @return array
	 */
	public function getFileRange() : array {
		if ( isset( $this->file_ranges ) ) {
			return $this->file_ranges;
		}
		
		$fr = $_SERVER['HTTP_RANGE'] ?? '';
		if ( empty( $fr ) ) {
			$this->file_ranges	= [];
			return $this->file_ranges;
		}
		
		// Range(s) too long 
		if ( strlen( $fr ) > 180 ) {
			$this->file_ranges	= [-1];
			return $this->file_ranges;
		}
		
		// Check multiple ranges, if given
		$rg = \preg_match_all( 
			'/bytes=(^$)|(?<start>\d+)?(\s+)?-(\s+)?(?<end>\d+)?/is',
			$fr,
			$m
		);
		
		// Invalid range syntax?
		if ( false === $rg ) {
			$this->file_ranges	= [-1];
			return $this->file_ranges;
		}
		
		$starting	= $m['start'] ?? [];
		$ending		= $m['end'] ?? [];
		$sc		= count( $starting );
		
		// Too many or too few ranges or starting / ending mismatch
		if ( $sc > 10 || $sc == 0 || $sc != count( $ending ) ) {
			$this->file_ranges	= [-1];
			return $this->file_ranges;
		}
		
		\asort( $starting );
		\asort( $ending );
		$rx = [];
		
		// Format ranges
		foreach ( $ending as $k => $v ) {
			
			// Specify 0 for starting if empty and -1 if end of file
			$rx[$k] = [ 
				empty( $starting[$k] ) ? 0 : \intval( $starting[$k] ), 
				empty( $ending[$k] ) ? -1 : \intval( $ending[$k] )
			];
			
			// If start is larger or same as ending and not EOF...
			if ( $rx[$k][0] >= $rx[$k][1] && $rx[$k][1] != -1 ) {
				return [-1];
			}
		}
		
		// Sort by lowest starting value
		usort( $rx, function( $a, $b ) {
			return $a[0] <=> $b[0];
		} );
		
		// End of file range found if true
		$eof = 0;
		
		// Check for overlapping/redundant ranges (preserves bandwidth)
		foreach ( $rx as $k => $v ) {
			// Nothing to check yet
			if ( !isset( $rx[$k-1] ) ) {
				continue;
			}
			// Starting range is lower than or equal previous start
			if ( $rx[$k][0] <= $rx[$k-1][0] ) {
				return [-1];
			}
			
			// Ending range lower than previous ending range
			if ( $rx[$k][1] <= $rx[$k-1][1] ) {
				// Special case EOF and it hasn't been found yet
				if ( $rx[$k][1] == -1 && $eof == 0) {
					$eof = 1;
					continue;
				}
				return [-1];
			}
			
			// Duplicate EOF ranges
			if ( $rx[$k][1] == -1 && $eof == 1 ) {
				return [-1];
			}
		}
		
		$this->file_ranges = $rx;
		return $rx;
	}
	
	/**
	 *  Currently requested host server name
	 *  
	 *  @return string
	 */
	public function getHost() : string {
		if ( isset( $this->host ) ) {
			return $this->host;
		}
		
		// Base host headers
		$sh	= [ 'HTTP_HOST', 'SERVER_NAME', 'SERVER_ADDR' ];
		
		// Get forwarded host info from reverse proxy
		$fd	= $this->getForwarded();
		
		// Check reverse proxy host name in whitelist
		if ( \array_key_exists( 'host', $fd ) ) {
			$host	= 
			\Notes\Util::lowercase( ( string ) $fd['host'] );
			
		// Check base host headers
		} else {
			foreach ( $sh as $h ) {
				if ( empty( $_SERVER[$h] ) ) {
					continue;
				}
				$host = \Notes\Util::lowercase( 
					( string ) $_SERVER[$h] 
				);
				break;
			}
		}
		
		$this->host = $host ?? '';
		return $this->host;
	}
	
	/**
	 *  Build request paths based on currently host and URI
	 *  
	 *  @return array
	 */
	public function pathTree() : array {
		if ( isset( $this->path_tree ) ) {
			return $this->path_tree;
		}
		$path	= [ $this->getHost() ];
		\array_map( function( $v ) use ( &$path ) {
			$path[] = 
			\rtrim( \end( $path ) . '/' . \ltrim( $v, '/' ), '/' );
		}, explode( '/', \ltrim( $this->getURI(), '/' ) );
		
		$this->path_tree = $path;
		return $this->path_tree;
	}
	
	/**
	 *  Current website with protocol prefix
	 *  
	 *  @return string
	 */
	public function website() : string {
		if ( isset( $this->web ) ) {
			return $this->web;
		}
		
		$this->web	= 
		( $this->isSecure() ? 
			'https://' : 'http://' ) . $this->getHost();
		
		return $this->web;
	}
	
	/**
	 *  Current full URI including website
	 */
	public function fullURI() : string {
		if ( isset( $this->url ) ) {
			return $this->url;
		}
		
		$this->url = 
			$this->website() . 
			\Notes\Util::slashPath( $this->getURI() );
		return $this->url;
	}
}

