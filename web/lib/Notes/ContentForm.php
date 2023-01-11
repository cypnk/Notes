<?php declare( strict_types = 1 );

namespace Notes;

class ContentForm extends Form {
	
	public int $id;
	
	protected static string $form_name	= 'web_content';
	
	public function render() : string {
		// TODO: Load language defintions
		if ( !isset( $this->rendered ) ) {
			$this->rendered = 
			$this->form_type->render( 
				$this->controller, $this->params['form'] 
			)
		}
		
		// Gather event placeholder replacements
		$this->controller->run( 
			static::$form_name, [ 'form' => $this ] 
		);
		
		// Apply placeholders to output
		return 
		\strtr( $this->rendered, [
			...$this->placeholders, 
			...$this->controller->output( static::$form_name ) 
		] );
	}
	
}
