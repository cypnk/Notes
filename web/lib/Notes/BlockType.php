<?php declare( strict_types = 1 );

namespace Notes;

class BlockType extends Content {
	
	protected readonly string $_label;
	
	/**
	 *  Block creation input display HTML
	 *  @var string
	 */
	public string $create_template;
	
	/**
	 *  Block editing input HTML
	 *  @var string
	 */
	public string $edit_template;
	
	/**
	 *  Block delete HTML view E.G. confirmation buttons/links
	 *  @var string
	 */
	public string $delete_template;
	
	/**
	 *  Block final rendered HTML
	 *  @var string
	 */
	public string $view_template;
	
	/**
	 *  Cached renders of this block type based on content
	 *  @var array
	 */
	protected array $_rendered	= [];
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'label':
				if ( isset( $this->_label ) ) {
					$this->error( 'Attempted to directly change label' );
					return;
				}
				
				$this->_label = ( string ) $value;
				return;
			
			// Pre-rendered content, if cached
			case 'view':
				$this->_rendered['view'] = ( string ) $value;
				return;
			
			case 'create':
				$this->_rendered['create'] = ( string ) $value;
				return;
				
			case 'edit':
				$this->_rendered['edit'] = ( string ) $value;
				return;
				
			case 'delete':
				$this->_rendered['delete'] = ( string ) $value;
				return;
		}
		
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'label':
				return $this->_label ?? '';
			
			case 'view':
				return $this->_rendered['view'] ?? '';
			
			case 'create':
				return $this->_rendered['create'] ?? '';
				
			case 'edit':
				return $this->_rendered['edit'] ?? '';
				
			case 'delete':
				return $this->_rendered['delete'] ?? '';
		}
		
		return parent::__get( $name );	
	}
	
	public function save() : bool {
		$bt = isset( $this->id ) ? true : false;
		
		// Default label
		$this->_content['label'] ??= 'text';
		
		$params	= [
			':content'		=> 
				static::formatSettings( $this->_content ),
			':view_tpl'	=> $this->view_template		?? '',
			':create_tpl'	=> $this->create_template	?? '',
			':edit_tpl'	=> $this->edit_template		?? '',
			':delete_tpl'	=> $this->delete_template	?? ''
		];
		
		if ( $pb ) {
			$params[':id']	= $this->id;
			
			return 
			$db->setUpdate(
				"UPDATE block_types 
				SET content = :content, 
					view_template = :view_tpl, 
					create_template = :create_tpl,
					edit_template = :edit_tpl,
					delete_template = :delete_tpl 
					WHERE id = :id;",
				$params,
				\DATA
			);
		}
		
		$id	= 
		$db->setInsert(
			"INESRT INTO block_types 
				( content, view_template, create_template,
					edit_template, delete_template ) 
			VALUES ( :content, :view_tpl, :create_tpl,
					:edit_tpl, :delete_tpl );",
			$params,
			\DATA
		);
		if ( empty( $id ) ) {
			return false;
		}
		$this->id = $id;
		return true;
	}
}
