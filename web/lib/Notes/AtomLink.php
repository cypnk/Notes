<?php declare( strict_types = 1 );
/**
 *  @file	/web/lib/Notes/AtomLink.php
 *  @brief	Resource reference or function tag link
 */
namespace Notes;

class AtomLink {
	
	/**
	 *  E.G. Edit label
	 *  @var string
	 */
	private string $_rel;
	
	/**
	 *  Link URL
	 *  @var string
	 */
	private string $_href;
	
	/**
	 *  Anchored entry
	 *  @var int
	 */
	protected readonly int $_entry_id;
	
	/**
	 *  Internal descriptor
	 *  @var string
	 */
	protected readonly string $_link_type;
	
	
	public function __set( $name, $value ) {
		switch( $name ) {
			case 'entry_id':
				if ( isset( $this->_entry_id ) ) {
					return;
				}
				$this->_entry_id = ( int ) $value;
				break;
				
			case 'link_type':
				if ( isset( $this->_link_type ) ) {
					return;
				}
				$this->_link_type = 
				\Notes\Util::bland( ( string ) $value );
				break;
			
			case 'rel':
				$this->_rel = 
				\Notes\Util::bland( ( string ) $value );
				break;
				
			case 'href':
				$this->_rel = 
				\Notes\Util::cleanUrl( ( string ) $value );
				break;
				
		}
	}
	
	public function __get( $name ) {
		switch( $name ) {
			case 'entry_id':
				return $this->_entry_id ?? 0;
				
			case 'link_type':
				return $this->_link_type ?? '';
			
			case 'rel':
				return $this->_rel ?? '';
			
			case 'href':
				return $this->_href ?? '';
		}
		
		return null;
	}
	
}


