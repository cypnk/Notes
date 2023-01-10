<?php declare( strict_types = 1 );

namespace Notes;

class DoctypeForm extends Form {
	
	public int $id;
	
	public string $label;
	
	public string $description;
	
	
	public static function newForm(
		\Notes\Controller	$ctrl,
		string			$action = ''
	) {
		return 
		static::loadForm( 
			$ctrl, 
			'web_doctype', 
			'\\Notes\\DoctypeForm', 
			$action
		); 
	}
	
	public function render() : string {
		// TODO: Load language defintions
		$this->rendered = 
		$this->form_type->render( 
			$this->controller, $this->params['form'] 
		)
		
		// Gather event placeholder replacements
		$this->controller->run( 
			'doctype_form', [ 'form' => $this ] 
		);
		
		$params = 
		\array_merge( 
			[ 
				'{id}'		=> ( string ) ( $this->id ?? '' ),
				'{label}'	=> $this->label ?? '',
				'{description}'	=> $this->description ?? ''
			],
			$this->controller->output( 'doctype_form' )
		];
		
		// Apply placeholders to output
		return \strtr( $this->rendered, $params );
	}
}

