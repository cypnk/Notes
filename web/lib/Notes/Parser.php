<?php declare( strict_types = 1 );
/**
 *  @file	/lib/web/Notes/Parser.php
 *  @brief	Text component placeholder parser
 */
namespace Notes;

class Parser extends Controllable {
	
	/**
	 *  Region match pattern
	 *  @example {region}
	 */
	const RX_REGION	= '/(?<=\{)([a-z_]+)(?=\})/i';
	
	/**
	 *  General term match pattern
	 */
	const RX_MATCH	= '/(?<=\{){repeat}(?=\})/x';
	
	/**
	 *  Repeated matching sub pattern
	 */
	const RX_REPEAT	= '(?:[\s:]?([\w]+)(?:\s+([\w=\"\',\s]+))?)';
	
	/**
	 *  Generated regular expression
	 *  @var string
	 */
	private $regex;
	
	/**
	 *  Placeholder parsed templates list
	 *  @var array
	 */
	private $parsed	= [];
	
	/**
	 *  Find template {regions} set in the HTML
	 *  Template regions must consist of letters, underscores, and 
	 *  without spaces
	 *  
	 *  @param string	$tpl	Raw HTML template without content
	 *  @return array
	 */
	public function findTplRegions( string $tpl ) : array {
		if ( \preg_match_all( self::RX_REGION, $tpl, $m ) ) {
			return $m[0];
		}
		return [];
	}
	
	/**
	 *  Process placeholder parameter clusters
	 *  
	 *  @example
	 *  {Lang:label}
	 *  {Workspace:Collection id=:id}
	 * 
	 *  @param string		$tpl	Raw render template
	 */
	public function parse( string $tpl ) : array {
		$key	= \hash( 'sha1', $tpl );
		
		if ( isset( $this->parsed[$key] ) ) {
			return $this->parsed[$key];
		}
		
		$groups	= [];
		
		if ( !\preg_match_all( 
			$this->getRenderRegex(), 
			$tpl, 
			$matches 
		) ) {
			$this->parsed[$key] = $groups;
			return $groups;
		}
		
		// Group segments to major clusters
		$groups	= \array_values( $matches[0] );
		while( false !== next( $matches ) ) {
			$groups = 
			\array_combine( 
				\array_values( $matches[0] ), 
				$groups 
			);
		}
		
		$this->$parsed[$key] = $groups;
		return $groups;
	}
	
	/**
	 *  Placeholder match pattern builder
	 */
	public function getRenderRegex() : string {
		if ( isset( $this->regex ) ) {
			return $this->regex;
		}
		$config	= $this->getControllerParam( '\\Notes\Config' );
		
		// Maximum number of sub matches (in addition to primary)
		$mxd	= $config->setting( 'parser_max_depth', 'int' );
		
		$m		= 
		self::RX_REPEAT . 
			\str_repeat( self::RX_REPEAT . '?', $mxd );
		
		$this->regex	= 
		\strtr( self::RX_MATCH, [ '{repeat}' => $m ] );
		
		return $this->regex;
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
	public function flatten(
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
	public function templatePlaceholders( 
		string		$tpl, 
		array		$definition,
		bool		$flat		= true
	) {
		// Convert data to phrase:sub_phrase... format if true
		$terms	= 
		$this->placeholders( 
			$flat ? $this->flatten( $definition ) : $definition 
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
	public function placeholders( array $input ) : array {
		$data = [];
		
		// Format data to placeholders
		\array_walk( $input, function( $v, $k ) use ( &$data ) {
			// Skip arrays (sub definitions)
			if ( \is_array( $v ) ) { return; }
			
			$data['{' . $k . '}'] = ( string ) ( $v ?? '' );
		} );
		
		return $data;
	}
}


