<?php declare( strict_types = 1 );

namespace Notes;

class HtmlNote extends Controllable {
	
	/**
	 *  Current main document
	 *  @var DOMDocument
	 */
	public readonly \DOMDocument	$dom;
	
	/**
	 *  Document rendered as XML, if true
	 *  @var bool
	 */
	public readonly bool		$is_xml;
	
	/**
	 *  Build an HTML segment note
	 *  
	 *  @param \Notes\Controller	$ctrl		Current event controller
	 *  @param bool			$xml		Render as XML if true
	 */
	public function __construct( 
		\Notes\Controller	$ctrl,
		bool			$xml	= false 
	) {
		parent::__construct( $ctrl );
		
		$this->is_xml	= $xml;
		$this->dom	= 
		$xml ? 
			new \DOMDocument( '1.0', 'UTF-8' ) : 
			new \DOMDocument();
	}
	
	/**
	 *  Create node element with current document
	 *  
	 *  @param string	$name		Node element name
	 *  @param string	$content	Optinoal inner content
	 *  @param string	$ns		Optional XML namespace
	 *  @return mixed
	 */
	public function createNode( 
		string		$name,
		?string		$content	= null,
		?string		$ns		= null 
	) : ?\DOMElement {
		
		$content	??= '';
		$ns		= \Notes\Util::cleanUrl( $ns ?? '' );
		
		$node		= 
		match( true ) {
			!empty( $ns ) && !empty( $content )	=>
			$this->dom->createElementNS( $ns, $name, $content ),
			
			!empty( $ns ) && empty( $content )	=>
			$this->dom->createElementNS( $ns, $name ),
			
			empty( $ns ) && !empty( $content )	=>
			$this->dom->createElement( $name, $content ),
			
			default					=>
			$this->dom->createElement( $name )
		};
		
		if ( false === $node ) {
			return null;
		}
		
		return $node;
	}
	
	/**
	 *  Create text chraracter content with current document
	 *  
	 *  @param string	$content	Base element content
	 *  @param string	$mode		Filtering mode
	 *  @return mixed
	 */
	public function createText( 
		string	$content,
		string	$mode		= 'text'
	) {
		$node = 
		match( \strtolower( $mode ) ) {
			// TODO: Filter basic HTML
			'html'		=>
			$this->dom->createCDATASection( $content ),
			
			// URL Slug and similar
			'slug'		=>
			$this->dom->createTextNode( 
				\Notes\Util::slugify( $content )
			),
			
			// Plain text without special characters
			'plain'		=>
			$this->dom->createTextNode( 
				\Notes\Util::bland( $content, true )
			),
			
			// Template placeholder
			'place', 'placeholder'	=>
			$this->dom->createTextNode(
				\preg_replace( 
					'/[^\p{L}\p{N}\:\{\}_]+/', '', 
					\Notes\Util::unifySpaces( $content, '_' )
				)
			),
			
			// Everything else
			default		=>
			$this->dom->createTextNode( 
				\Notes\Util::bland( $content )
			);
		};
		
		return ( false === $node ) ? null : $node;
	}
	
	/**
	 *  Extract note body
	 *  
	 *  @return string
	 */
	public function render() : string {
		if ( empty( $this->dom->childNodes ) ) {
			return '';
		}
		
		$temp	= $this->is_xml ? 
			new \DOMDocument( '1.0', 'UTF-8' ) : 
			new \DOMDocument();
			
		foreach ( $this->dom->childNodes as $child ) {
   			$temp->appendChild( $temp->importNode( $child, true ) );
  		}
		
		return $this->is_xml ? 
			$temp->saveXML() : $temp->saveHTML();
	}
}

