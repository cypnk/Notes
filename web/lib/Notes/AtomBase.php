<?php declare( strict_types = 1 );
/**
 *  @file	/web/lib/Notes/AtomBase.php
 *  @brief	XML component builder base for AtomPub and Atom feeds
 *  @link	https://www.ietf.org/rfc/rfc5023.txt
 */

namespace Notes;

class AtomBase extends Controllable {
	
	/**
	 *  XML Namespaces
	 *  @var array
	 */
	protected $xmlns	= [
		'atom'	=> 'http://www.w3.org/2005/Atom', 
		'app'	=>'http://www.w3.org/2007/app'
	];
		
	/**
	 *  Unique URN identifier
	 *  @var string
	 */
	public $id;
	
	/**
	 *  Main label for this area as atom:title
	 *  @var string
	 */
	public $title;
	
	/**
	 *  Originally created UTF timestamp
	 *  @var string
	 */
	public $created;
	
	/**
	 *  Last updated UTF timestamp
	 *  @var string
	 */
	public $updated;
	
	/**
	 *  Base document handler
	 *  @var \Notes\HtmlNote
	 */
	protected readonly \Notes\HtmlNote $note;
	
	public function __construct( \Notes\Controller $ctrl ) {
		parent::__construct( $ctrl );
		
		$this->note = new \Notes\HtmlNote( $ctrl );
	}
	
	/**
	 *  Create node element within current document
	 *  
	 *  @param string	$name		Node element name
	 *  @param string	$content	Element inner content
	 *  @param string	$ntype		XML namespace type
	 *  @param bool		$append		Add to current document if true
	 */
	public function addNode( 
		string		$name,
		string		$content,
		string		$ntype,
		bool		$append		= true
	) : \DOMElement {
		$node	= 
		$this->note->createNode( 
			$name, 
			$content, 
			$this->xmlns[$ntype] ?? $this->xmlns['atom'] 
		);
		
		if ( $append ) {
			$this->note->dom->appendChild( $node );
		}
		return $node;
	}
	
	/**
	 *  Remove element from current document if it exists
	 *  
	 *  @param \DOMNode	$node	XML Element
	 */
	public function removeNode( \DOMNode $node ) : bool {
		if ( !$this->note->dom->hasChildNodes() ) {
			return false;
		}
		
		return 
		( false === $this->note->dom->removeChild( $node ) ) ? 
			false : true;
	}
}

