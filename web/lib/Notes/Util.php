<?php declare( strict_types = 1 );

namespace Notes;

class Util {
	
	/**
	 *  URL validation regular expressions
	 */
	const RX_URL	= 
	'~^(http|ftp)(s)?\:\/\/((([\pL\pN\-]{1,25})(\.)?){2,9})($|\/.*$){4,255}$~i';
	
	/**
	 *  Low-key XSS tests
	 */
	const RX_XSS2	= '/(<(s(?:cript|tyle)).*?)/ism';
	const RX_XSS3	= '/(document\.|window\.|eval\(|\(\))/ism';
	const RX_XSS4	= '/(\\~\/|\.\.|\\\\|\-\-)/sm';
	
	
	
	/**
	 *  String to list helper
	 *  
	 *  @param string	$text	Input text to break into items
	 *  @param bool		$lower	Convert Mixed/Uppercase text to lowercase if true
	 *  @param string	$sep	String delimiter, defaults to comma
	 */
	public static function trimmedList( 
		string		$text, 
		bool		$lower	= false, 
		string		$sep	= ',' 
	) : array {
		$map = \array_map( 'trim', \explode( $sep, $text ) );
		return $lower ? 
			\array_map( 'strtolower', $map ) : $map;
	}
	
	/**
	 *  Apply uniform encoding of given text to UTF-8
	 *  
	 *  @param string	$text	Raw input
	 *  @param bool		$ignore Discard unconvertable characters (default)
	 *  @return string
	 */
	public static function utf( 
		string		$text, 
		bool		$ignore		= true 
	) : string {
		$out = $ignore ? 
			\iconv( 'UTF-8', 'UTF-8//IGNORE', $text ) : 
			\iconv( 'UTF-8', 'UTF-8', $text );
		
		return ( false === $out ) ? '' : $out;
	}
	
	/**
	 *  Strip unusable characters from raw text/html and conform to UTF-8
	 *  
	 *  @param string	$html	Raw content body to be cleaned
	 *  @param bool		$entities Convert to HTML entities
	 *  @return string
	 */
	public static function pacify( 
		string		$html, 
		bool		$entities	= false 
	) : string {
		$html		= static::utf( \trim( $html ) );
		
		// Remove control chars except linebreaks/tabs etc...
		$html		= 
		\preg_replace(
			'/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $html
		);
		
		// Non-characters
		$html		= 
		\preg_replace(
			'/[\x{fdd0}-\x{fdef}]/u', '', $html
		);
		
		// UTF unassigned, formatting, and half surrogate pairs
		$html		= 
		\preg_replace(
			'/[\p{Cs}\p{Cf}\p{Cn}]/u', '', $html
		);
		
		// Convert Unicode character entities?
		if ( $entities ) {
			$html	= 
			\mb_convert_encoding( 
				$html, 'HTML-ENTITIES', 'UTF-8' 
			);
		}
		
		return \trim( $html );
	}
	
	/**
	 *  HTML safe character entities in UTF-8
	 *  
	 *  @param string	$v	Raw text to turn to HTML entities
	 *  @param bool		$quotes	Convert quotes (defaults to true)
	 *  @param bool		$spaces	Convert spaces to "&nbsp;*" (defaults to true)
	 *  @return string
	 */
	public static function entities( 
		string		$v, 
		bool		$quotes	= true,
		bool		$spaces	= true
	) : string {
		
		$v = $quotes ? 
		\htmlentities( 
			static::utf( $v, false ), 
			\ENT_QUOTES | \ENT_SUBSTITUTE, 
			'UTF-8'
		) : 
		\htmlentities( 
			static::utf( $v, false ), 
			\ENT_NOQUOTES | \ENT_SUBSTITUTE, 
			'UTF-8'
		);
		
		return $spaces ? 
		\strtr( $v, [ 
			' ' => '&nbsp;',
			'	' => '&nbsp;&nbsp;&nbsp;&nbsp;'
		] ) : $v;
	}
	
	/**
	 *  Filter URL
	 *  This is not a 100% foolproof method, but it's better than nothing
	 *  
	 *  @param string	$txt	Raw URL attribute value
	 *  @param bool		$xss	Filter XSS possibilities
	 *  @return string
	 */
	public static function cleanUrl( 
		string		$txt, 
		bool		$xss		= true
	) : string {
		// Nothing to clean
		if ( empty( $txt ) ) {
			return '';
		}
		
		// Default filter
		if ( \filter_var( $txt, \FILTER_VALIDATE_URL ) ) {
			// XSS filter
			if ( $xss ) {
				if ( !\preg_match( static::RX_URL, $txt ) ){
					return '';
				}	
			}
			
			if ( 
				\preg_match( static::RX_XSS2, $txt ) || 
				\preg_match( static::RX_XSS3, $txt ) || 
				\preg_match( static::RX_XSS4, $txt ) 
			) {
				return '';
			}
			
			// Return as/is
			return  $txt;
		}
		
		return static::entities( $txt, false, false );
	}
	
	/**
	 *  Simple email address filter helper
	 *  
	 *  @param string	$email	Raw email (currently doesn't support Unicode domains)
	 *  @return string
	 */
	public static function cleanEmail( string $email ) : string {
		if ( \filter_var( $email, \FILTER_VALIDATE_EMAIL ) ) {
			return $email;
		}
		
		return '';
	}
	
	/**
	 *  Prepend given prefix to URLs starting with '/'
	 *  
	 *  @param string	$url	Raw URL path
	 *  @param string	$prefix	Prefix to prepend if $url starts with '/'
	 *  @return string
	 */
	public static function prependPath( string $v, string $prefix ) : string {
		$v = trim( $v, '"\'' );
		return \preg_match( '/^\//', $v ) ?
			static::cleanUrl( $prefix . $v ) : 
			static::cleanUrl( $v );
	}
	
	/**
	 *  Flatten a multi-dimensional array into a path map
	 *  
	 *  @link https://stackoverflow.com/a/2703121
	 *  
	 *  @param array	$items		Raw item map E.G. parsed JSON
	 *  @param string	$delim		Phrase separator in E.G. ":" in {lang:value}
	 *  @return array
	 */ 
	public static function flatten(
		array		$items, 
		string		$delim	= ':'
	) : array {
		$it	= 
		new \RecursiveIteratorIterator( 
			new \RecursiveArrayIterator( $items )
		);
		
		$out	= [];
		foreach ( $it as $leaf ) {
			$path = '';
			foreach ( \range( 0, $it->getDepth() ) as $depth ) {
				$path = 
				\ltrim( $path, $delim ) . $delim . 
					$it->getSubIterator( $depth )->key();
			}
			$out[$path] = $leaf;
		}
		
		return $out;
	}
	
	/**
	 *  Scan and replace phrase placeholders in a given template
	 *  
	 *  @param string	$tpl		Loaded template data
	 *  @param array	$definition	Loaded terms to replace placeholders
	 *  @param bool 	$flat		Flatten definition before use if true
	 *  @return string
	 */
	public static function templatePlaceholders( 
		string		$tpl, 
		array		$definition,
		bool		$flat		= true
	) {
		// Convert data to phrase:sub_phrase... format if true
		$terms	= 
		static::placeholders( 
			$flat ? static::flatten( $definition ) : $definition 
		);
		
		// Format variable placeholders before returning
		return 
		\preg_replace( 
			'/\s*__(\w+)__\s*/', ' {\1} ', \strtr( $tpl, $terms ) 
		);
	}
	
	/**
	 *  Format template placeholders to {value} format
	 *  
	 *  @param array	$input	Original data
	 *  @return array
	 */
	public static function placeholders( array $input ) : array {
		$data = [];
		
		// Format data to placeholders
		\array_walk( $input, function( $v, $k ) use ( &$data ) {
			// Skip arrays (sub definitions)
			if ( \is_array( $v ) ) { return; }
			
			$data['{' . $k . '}'] = ( string ) ( $v ?? '' );
		} );
		
		return $data;
	}
	
	
	/**
	 *  Content formatting
	 */
	
	/**
	 *  Convert timestamp to int if it's not in integer format
	 *  
	 *  @return mixed
	 */
	public static function tstring( $stamp ) {
		if ( empty( $stamp ) ) {
			return null;
		}
		
		if ( \is_numeric( $stamp ) ) {
			return ( int ) $stamp;
		}
		
		$st =  \ltrim( 
			\preg_replace( '/[^0-9\/]+/', '', $stamp ), 
			'/' 
		);
	
		return \strtotime( empty( $st ) ? 'now' : $st );
	}
	
	/**
	 *  UTC timestamp
	 *  
	 *  @param mixed	$stamp	Plain timestamp or null to generate new
	 *  @return string
	 */
	public static function utc( $stamp = null ) : string {
		return 
		\gmdate( 
			'Y-m-d\TH:i:s', 
			static::tstring( $stamp ?? 'now' ) 
		);
	}
	
	/**
	 *  Length of given string
	 *  
	 *  @param string	$text	Raw input
	 *  @return int
	 */
	public static function strsize( string $text ) : int {
		return \mb_strlen( $text, '8bit' );
	}
	
	/**
	 *  Limit string size
	 *  
	 *  @param string	$text	Raw input
	 *  @param int		$start	Beginning index
	 *  @param int		$size	Maximum string length
	 *  @return string
	 */
	public static function truncate( string $text, int $start, int $size ) {
		if ( static::strsize( $text ) <= $size ) {
			return $text;
		}
		
		return \mb_substr( $text, $start, $size, '8bit' );
	}
	
	/**
	 *  Try to detect if a string contains ASCII-only text
	 *  
	 *  @param string	$text		Text to test
	 *  @return bool
	 */
	public static function isASCII( string $text ) : bool {
		return \mb_check_encoding( $text, 'ASCII' );
	}
	
	/**
	 *  Check if a string contains a fragment
	 *  
	 *  @param mixed	$source		Original text
	 *  @param strin	$term		Search term
	 */
	public static function textHas( $source, string $term ) : bool {
		return 
		( empty( $source ) || empty( $term ) ) ? 
			false : \str_contains( ( string ) $source, $term );
	}

	/**
	 *  Check if string starts with a fragment
	 *  
	 *  @param string	$find		Needle to search
	 *  @param array	$collection	Haystack to search partials for
	 *  @param bool		$ca		Case insensitive if true (default)
	 *  @return bool
	 */
	public static function textStartsWith( 
		string		$find, 
		array		$collection, 
		bool		$ca		= true 
	) {
		if ( $ca ) {
			foreach ( $collection as $c ) {
				if ( 0 === \strncasecmp( 
					$find, $c, 
					static::strsize( $c ) ) 
				) {
					return true;
				}
			}
		} else {
			foreach ( $collection as $c ) {
				if ( 0 === \strncmp( 
					$find, $c, 
					static::strsize( $c ) ) 
				) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 *  Search string for a fragment in an array
	 *  
	 *  @param string	$find		Needle to search
	 *  @param array	$collection	Haystack to search contained string
	 *  @return bool
	 */
	public static function textNeedleSearch( 
		string		$find, 
		array		$collection 
	) : bool {
		foreach ( $collection as $c ) {
			if ( static::textHas( $find, $c ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 *  Feed timestamp
	 *  
	 *  @param mixed	$stamp		Optional timestamp, defaults to 'now'
	 *  @return string
	 */
	public static function dateRfc( $stamp = null ) : string {
		return 
		\gmdate( 
			\DATE_RFC2822, 
			static::tstring( $stamp ?? 'now' ) 
		);
	}
	
	/**
	 *  File modified timestamp
	 *  
	 *  @param mixed	$stamp		Optional timestamp, defaults to 'now'
	 *  @return string
	 */
	public static function dateRfcFile( $stamp = null ) : string {
		return 
		\gmdate( 
			'D, d M Y H:i:s T', 
			static::tstring( $stamp ?? 'now' ) 
		);
	}
	
	/**
	 *  Convert all spaces to single character
	 *  
	 *  @param string	$text		Raw text containting mixed space types
	 *  @param string	$rpl		Replacement space, defaults to ' '
	 *  @param string	$br		Preserve line breaks
	 *  @return string
	 */
	public static function unifySpaces( 
		string		$text, 
		string		$rpl	= ' ', 
		bool		$br	= false 
	) : string {
		return $br ?
		\preg_replace( 
			'/[ \t\v\f]+/', $rpl, static::pacify( $text ) 
		) : 
		\preg_replace( 
			'/[[:space:]]+/', $rpl, static::pacify( $text ) 
		);
	}
	
	/**
	 *  Get a list of tokens separated by spaces
	 *  
	 *  @param string	$text		Raw text containing repeated words
	 *  @return array
	 */
	public static function uniqueTerms( string $value ) : array {
		return 
		\array_unique( 
			\preg_split( 
				'/[[:space:]]+/', trim( $value ), -1, 
				\PREG_SPLIT_NO_EMPTY 
			) 
		);
	}
	
	/**
	 *  Clean entry title
	 *  
	 *  @param mixed	$title	Raw title entered by the user
	 *  @param int		$max	Maximum string length
	 *  @return string
	 */
	public static function title( $text, int $max = 255 ) : string {
		if ( \is_array( $text ) ) {
			return '';
		}
		
		// Unify spaces, tabs, returns etc...
		$text	= 
		static::unifySpaces( 
			\is_string( $text ) ? $text : ( string ) $text 
		);
		
		return 
		static::smartTrim( 
			\preg_replace( '/\s+/', ' ', $text ), $max 
		);
	}
	
	/**
	 *  Normalize unicode characters
	 *  
	 *  This depends on the Intl extension (usually comes with PHP), 
	 *  but needs to be enabled in php.ini
	 *  @link https://www.php.net/manual/en/intl.installation.php
	 *  
	 *  @param string	$text
	 *  @return string 
	 */
	public static function normal( string $text ) : string {
		$normal = 
		\normalizer_normalize( $text, \Normalizer::FORM_C );
		
		return ( false === $normal ) ? $text : $normal;
	}
	
	/**
	 *  Process multiple comma delimited whitelists and filter label names
	 *  
	 *  @param array	$groups		Raw key-value pairs
	 *  @param bool		$lower		Values should be lowercase lists
	 *  @return array
	 */ 
	public static function whiteLists( 
		array		$groups, 
		bool		$lower		= false 
	) : array {
		$ext = [];
		
		foreach ( $groups as $k => $v ) { 
			$ext[static::labelName( $k )] = 
			\implode( ',', static::trimmedList( $v, $lower ) );
		}
		
		return $ext;
	}

	/**
	 *  Convert to unicode lowercase
	 *  
	 *  @param string	$text	Raw mixed/uppercase text
	 *  @return string
	 */
	public static function lowercase( string $text ) : string {
		return \mb_convert_case( $text, \MB_CASE_LOWER, 'UTF-8' );
	}
	
	/**
	 *  Limit a string without cutting off words
	 *  
	 *  @param string	$val	Text to cut down
	 *  @param int		$max	Content length (defaults to 100)
	 *  @return string
	 */
	public static function smartTrim(
		string		$val, 
		int		$max		= 100
	) : string {
		$val	= \trim( $val );
		$len	= static::strsize( $val );
		
		if ( $len <= $max ) {
			return $val;
		}
		
		$out	= '';
		$words	= \preg_split( '/([\.\s]+)/', $val, -1, 
				\PREG_SPLIT_OFFSET_CAPTURE | 
				\PREG_SPLIT_DELIM_CAPTURE );
		
		for ( $i = 0; $i < \count( $words ); $i++ ) {
			$w	= $words[$i];
			// Add if this word's length is less than length
			if ( $w[1] <= $max ) {
				$out .= $w[0];
			}
		}
		
		$out	= \preg_replace( "/\r?\n/", '', $out );
		
		// If there's too much overlap
		if ( static::strsize( $out ) > $max + 10 ) {
			$out = static::truncate( $out, 0, $max );
		}
		
		return $out;
	}
	
	/**
	 *  Convert a string into a page slug
	 *  
	 *  @param string	$title	Fallback title to generate slug
	 *  @param string	$text	Text to transform into a slug
	 *  @return string
	 */
	public static function slugify( 
		string		$title, 
		string		$text		= ''
	) : string {
		if ( empty( $text ) ) {
			$text = $title;
		}
		$text = static::lowercase( static::unifySpaces( $text ) );
		$text = \preg_replace( '~[^\\pL\d]+~u', ' ', $text );
		$text = \preg_replace( '/\s+/', '-', \trim( $text ) );
		$text = \preg_replace( '/\-+/', '-', \trim( $text, '-' ) );
		
		return \strtolower( static::smartTrim( $text ) );
	}
	
	/**
	 *  Label name ( ASCII only )
	 *  
	 *  @param string	$text	Raw label entered into field
	 *  @param int		$int	Max label length
	 *  @return string
	 */
	public static function labelName( 
		string	$text, 
		int	$len	= 50 
	) : string {
		$text	= static::unifySpaces( $text, '_' );
		
		return 
		static::smartTrim( \preg_replace( 
			'/^[a-zA-Z0-9_\-\.]/i', '', 
			static::normal( $text ) 
		), $len );
	}
	
	
	/**
	 *  Filter number within min and max range, inclusive
	 *  
	 *  @param mixed	$val		Given default value
	 *  @param int		$min		Minimum, returned if less than this
	 *  @param int		$max		Maximum, returned if greater than this
	 *  @return int
	 */
	public static function intRange( $val, int $min, int $max ) : int {
		$out = ( int ) $val;
		
		return 
		( $out > $max ) ? $max : ( ( $out < $min ) ? $min : $out );
	}
	/**
	 *  Limit between min and max range for each set, inclusive
	 *  
	 *  @param array	$items		Set range of integers
	 *  @param int		$num		Total number required
	 *  @param int		$min		Minimum set if item is less than this
	 *  @param int		$max		Maximum set if item is greater than this
	 */
	public static function spanIntRange(
		array		$items, 
		int		$num,
		int		$min,
		int		$max,
		string		$pad		= 'max'
	) {
		// Pad maximum or minimum values if array is too short
		$items = 
		( 0 === \strcasecmp( $pad, 'max' ) ) ?
			\array_pad( $items, $num, $max ) : 
			\array_pad( $items, $num, $min );
		
		if ( count( $items ) > $num ) {
			$items = \array_slice( $items, 0, $num );
		}
		
		return 
		\array_map( function( $v ) {
			return static::intRange( $v, $min, $max );
		}, $items );
	}
	
	/**
	 *  Make text completely bland by stripping punctuation, 
	 *  spaces and diacritics (for further processing)
	 *  
	 *  @param string	$text		Raw input text
	 *  @param bool		$nospecial	Remove special characters if true
	 *  @return string
	 */
	public static function bland( 
		string		$text, 
		bool		$nospecial	= false 
	) : string {
		$text = \strip_tags( static::unifySpaces( $text ) );
		
		if ( $nospecial ) {
			return \preg_replace( 
				'/[^\p{L}\p{N}\-\s_]+/', '', \trim( $text ) 
			);
		}
		return \trim( $text );
	}
	
	/**
	 *  Safely encode array to JSON
	 *  
	 *  @param array	$data	Content to be encoded to string
	 *  @param bool		$pretty	Preserve whitespace between content 
	 *  @return string
	 */
	public static function encode( 
		array	$data	= [], 
		bool	$pretty	= false 
	) : string {
		if ( empty( $data ) ) {
			return '';
		}
		
		if ( $pretty ) {
			$out = 
			\json_encode( 
				$data, 
				\JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_QUOT | 
				\JSON_HEX_AMP | \JSON_UNESCAPED_UNICODE | 
				\JSON_PRETTY_PRINT 
			);
		} else {
			$out = 
			\json_encode( 
				$data, 
				\JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_QUOT | 
				\JSON_HEX_AMP | \JSON_UNESCAPED_UNICODE
			);
		}
		
		return ( false === $out ) ? '' : $out;
	}
	
	/**
	 *  Safely decode JSON to array
	 *  
	 *  @return array
	 */
	public static function decode( 
		string		$data	= '', 
		int		$depth	= 10 
	) : array {
		if ( empty( $data ) ) {
			return [];
		}
		$depth	= static::intRange( $depth, 1, 50 );
		$out	= 
		\json_decode( 
			\utf8_encode( $data ), true, $depth, 
			\JSON_BIGINT_AS_STRING
		);
		
		if ( empty( $out ) || false === $out ) {
			return [];
		}
		
		return $out;
	}
	
	/**
	 *  Path prefix slash (/) helper
	 */
	public static function slashPath( 
		string		$path, 
		bool		$suffix	= false 
	) : string {
		return $suffix ?
			\rtrim( $path, '/\\' ) . '/' : 
			'/'. \ltrim( $path, '/\\' );
	}
	
	/**
	 *  Split a block of text into an array of lines
	 *  
	 *  @param string	$text	Raw text to split into lines
	 *  @param int		$lim	Max line limit, defaults to unlimited
	 *  @param bool		$tr	Also trim lines if true
	 *  @return array
	 */
	public static function lines( 
		string	$text, 
		int	$lim	= -1, 
		bool	$tr	= true 
	) : array {
		return $tr ?
		\preg_split( 
			'/\s*\R\s*/', 
			trim( $text ), 
			$lim, 
			\PREG_SPLIT_NO_EMPTY 
		) : 
		\preg_split( '/\R/', $text, $lim, \PREG_SPLIT_NO_EMPTY );
	}
	
	/**
	 *  Helper to turn items (one per line) into a unique value array
	 *  
	 *  @param string	$text	Lined settings (one per line)
	 *  @param int		$lim	Maximum number of items
	 *  @param string	$filter	Optional filter name to apply
	 *  @return array
	 */
	public static function lineSettings( 
		string	$text, 
		int	$lim, 
		string	$filter = '' 
	) : array {
		$ln = \array_unique( static::lines( $text ) );
		
		$rt = ( ( count( $ln ) > $lim ) && $lim > -1 ) ? 
			\array_slice( $ln, 0, $lim ) : $ln;
		
		return 
		( !empty( $filter ) && \is_callable( $filter ) ) ? 
			\array_map( $filter, $rt ) : $rt;
	}
	
	/**
	 *  Array formatted return helper
	 *  
	 *  @param mixed	$res	Raw data
	 *  @return array
	 */
	public static function arrayFormat( $res ) : array {
		return 
		empty( $res ) ? [] : ( \is_array( $res ) ? $res : [] );
	}
	
	/**
	 *  Filter file extension
	 *  
	 *  @param string	$ext		Raw file extension or empty
	 *  @return string
	 */
	public static function filterExt( ?string $ext ) : string {
		return 
		empty( $ext ) ? '' : 
		\preg_replace( 
			'/[[:space:]]+/', 
			static::bland( static::title( $ext ), true ), '' 
		);
	}
	
	
	/**
	 *  Generators
	 */
	
	/**
	 *  Generate a random string ID based on given random bytes
	 *  
	 *  @param int		$bytes		Size of random bytes
	 *  @return string
	 */
	public static function genId( int $bytes = 16 ) : string {
		return 
		\bin2hex( \random_bytes( 
			static::intRange( $bytes, 1, 16 ) 
		) );
	}
	
	/**
	 *  Generate a system time based sqeuential random ID
	 *  
	 *  Note: Downgrading from PHP 7.3 to 7.2 may cause IDs to appear out 
	 *  of sync
	 *  
	 *  @return string
	 */
	public static function genSeqId() : string {
		return 
		\base_convert( 
			( string ) \hrtime( true ), 10, 16 
		) . static::genId();
	}
	
	/**
	 *  Generate an alphanumeric string with 32 bytes of random data
	 *  
	 *  @return string
	 */
	public static function genAlphaNum() : string {
		return 
		\preg_replace( 
			'/[^[:alnum:]]/u', 
			'', 
			\base64_encode( \random_bytes( 32 ) ) 
		);
	}
	
	/**
	 *  Generate a fixed length string in ASCII space, no special chars
	 *  This is primarily a plugin helper
	 *  
	 *  @param int	$size	Code size between 1 and 24, inclusive
	 *  @return string
	 */
	public static function genCodeKey( int $size = 24 ) : string {
		$size	= static::intRange( $size, 1, 24 );
		$code	= '';
		while ( static::strsize( $code ) < $size ) {
			$code .= static::genAlphaNum();
		}
		
		return static::truncate( $code, 0, $size );
	}
	
	/**
	 *  Timezone offset list generator
	 *  
	 *  @return array
	 */
	public static function timezoneOffsets() : array {
		static $offsets;
		
		if ( isset( $offsets ) ) {
			return $offsets;
		}
		
		$zones		= 
		\DateTimeZone::listIdentifiers( \DateTimeZone::ALL );
		
		$offsets	= [];
		foreach ( $zones as $tz ) {
			$t		= new \DateTimeZone( $tz );
			$offsets[$t]	= 
			$tz->getOffset( new \DateTime );
		}
		
		\asort( $offsets );
		
		return $offsets;
	}
}


