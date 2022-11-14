<?php declare( strict_types = 1 );

namespace Notes;

class Input {
	
	protected array $templates	= [
		'text'		=>
		'<p>' . 
		'	<label for="{name}" class="f6 b db mb2">{label} <span class="normal black-60">{special}</span></label>' . 
		'	<input class="input-reset db border-box black-80 w-100 ba b--black-50 pa2 mb2" type="text" id="{id}" aria-describedby="{id}-desc">' . 
		'	<small id="{id}-desc" class="f6 lh-copy black-70 db mb2">{description}</small>'
		'</p>', 
		
		'textarea'	=> 
		'<p>' . 
		'	<label for="{name}" class="f6 b db mb2">{label} <span class="normal black-70">{special}</span></label>' . 
		'	<textarea id="{id}" name="{name}" class="db border-box black-80 w-100 ba b--black-50 pa2 mb2" aria-describedby="{id}-desc"></textarea>' . 
		'	<small id="{id}-desc" class="f6 black-70">{description}</small>' . 
		'</p>',
		
		'email'		=>
		'<p>' . 
		'	<label for="{name}" class="f6 b db mb2">{label} <span class="normal black-60">{special}</span></label>' . 
		'	<input class="input-reset db border-box black-80 w-100 ba b--black-50 pa2 mb2" type="email" id="{id}" aria-describedby="{id}-desc">' . 
		'	<small id="{id}-desc" class="f6 lh-copy black-70 db mb2">{description}</small>'
		'</p>', 
		
		'password'	=>
		'<p>' . 
		'	<label for="{name}" class="f6 b db mb2">{label} <span class="normal black-60">{special}</span></label>' . 
		'	<input class="input-reset db border-box black-80 w-100 ba b--black-50 pa2 mb2" type="password" id="{id}" aria-describedby="{id}-desc">' . 
		'	<small id="{id}-desc" class="f6 lh-copy black-70 db mb2">{description}</small>' . 
		'</p>'
	];
	
	public readonly int $id;
	
	public readonly string $name;
	
	public readonly string $itype;
	
	public string $template;
	
	public string $label;
	
	public string $description;
	
	public string $special;
	
	public array $attributes	= [];
	
	public array $values		= [];
	
	public array $validation	= [
		'missing'	=> '',
		'invalid'	=> ''
	];
	
	public function __construct( 
		string	$_name, 
		string	$_itype,
		?array	$_attr	= null,
		?string	$_tpl	= null,
		?string $id	= null 
	) {
		if ( empty( $id ) ) {
			$id = $_name;
		}
		
		$this->name	= $_name;
		$this->itype	= $_itype;
		
		if ( !empty( $_attr ) ) {
			$this->attributes	= $_attr;
		}
		
		if ( !empty( $_tpl ) ) {
			$this->template		= $_tpl
		}
	}
	
	public function setTemplate( string $_tpl ) {
		$this->template = $_tpl;
	}
	
	public function setAttributes( array $_attr ) {
		$this->attributes = $_attr;
	}
	
	public function render( array $data ) : string {
		switch ( $this->itype ) {
			case 'text':
			case 'textarea':
			case 'email':
			case 'password':
				return 
				\strtr( $this->templates[$this->itype], $data );
			
			case 'select':
				return '';
		}
		return '';
	}
	
	public function getData() {
		switch ( $this->itype ) {
			case 'text':
			case 'textarea':
			case 'wysiwyg':
			case 'email':
			case 'password':
				return $this->values[0] ?? null;
			
			case 'select':
				return $this->values;
		}
	}
}

