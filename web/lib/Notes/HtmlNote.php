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
		
		return $temp->saveHTML();
	}
}

