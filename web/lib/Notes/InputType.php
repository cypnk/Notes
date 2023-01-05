<?php declare( strict_types = 1 );

namespace Notes;

enum InputType {
	
	case Text;
	case Email;
	case Radio;
	case Select;
	case Wysiwyg;
	case Checkbox;
	case Password;
	case Textarea;
	
	case Hidden;
	
	// Generic input
	const InputRender	= 
	'<label for="{name}" class="{label_classes}">{label} ' . 
	'<span class="{special_classes}">{special}</span></label> ' . 
	'<input id="{id}" name="{name}" class="{input_classes}" ' . 
		'aria-describedby="{id}-desc" {extras}> ';
	
	// Typed text input
	const InputTypeRender	=
	'<label for="{name}" class="{label_classes}">{label} ' . 
	'<span class="{special_classes}">{special}</span></label> ' . 
	'<input id="{id}" name="{name}" class="{input_classes}" ' . 
		'type="{type}" aria-describedby="{id}-desc" {extras}>';
	
	// Checkbox and radio button
	const TickRender	= 
	'<label for="{name}" class="{label_classes}">{label} ' . 
	'<span class="{special_classes}">{special}</span> ' . 
	'<input id="{id}" name="{name}" type="{type}" class="{input_classes}" ' . 
		'aria-describedby="{id}-desc" {extras}></label>';
	
	// Multiline
	const TextareaRender	=
	'<label for="{name}" class="{label_classes}">{label} ' . 
	'<span class="{special_classes}">{special}</span></label> ' . 
	'<textarea id="{id}" name="{name}" class="{input_classes}" ' . 
		'aria-describedby="{id}-desc" {extras}>{value}</textarea>';
	
	// HTML Formatted multiline
	const WysiwygRender	=
	'<label for="{name}" class="{label_classes}">{label} ' . 
	'<span class="{special_classes}">{special}</span></label> ' . 
	'<div class="wysiwyg" rel="{id}"></div>' . 
	'<textarea id="{id}" name="{name}" class="{input_classes}" ' . 
		'aria-describedby="{id}-desc" 
		data-wysiwyg="{id}" {extras}>{value}</textarea>';
	
	// Multiple options
	const SelectRender	= 
	'<label for="{name}" class="{label_classes}">{label} ' . 
	'<span class="{special_classes}">{special}</span></label> ' . 
	'<select id="{id}" name="{name}" class="{input_classes}" ' . 
		'aria-describedby="{id}-desc">{options}</select>';
	
	// Single option in above
	const OptionRender	= 
	'<option value="{value}" {selected}>{label}</option>';
	
	// Hidden input template
	const HiddenRender	= 
	'<input type="hidden" name="{name}" value="{value}">';
	
	// Instructions and/or accessibility descriptions
	const DescRender	= 
	'<small id="{id}-desc" class="{desc_classes}">{description}</small>';
	
	// Field wrapper
	const InputWrap		= 
	'<p class="{input_wrap_classes}">{input} {desc}</p>';
	
	/**
	 *  Render the current input type in the above templates
	 */
	public function render( array $input ) : string {
		
		$data = static::placeholders( $input );
		
		// Default settings
		static::baseDefaults( $data );
		
		// Default styling
		static::baseStyling( $data );
		
		// TODO
		// $input['validation']		??= [];
		
		$input = 
		match( $this ) {
			// Flat text
			InputType::Text, 
			InputType::Email, 
			InputType::Password	=> 
				\strtr( static::InputTypeRender, $data ),
			
			// TODO: Selection
			InputType::Select	=> ( function() use ( $input, $data ) {
				$data['{options}'] = 
				static::renderOptions( $input['options'] ?? [] );
				
				\strtr( static::SelectRender, $data ),
			} )(),
			
			// Option
			InputType::Radio,
			InputType::Checkbox	=> 
				\strtr( static::TickRender, $data ),
			
			// Multiline
			InputType::Textarea	=> 
				\strtr( static::TextareaRender, $data ),
			
			// Multiline formatted
			InputType::Wysiwyg	=> 
				\strtr( static::WysiwygRender, $data ),
			
			// Hidden inputs
			InputType::Hidden	=>
				\strtr( static::HiddenRender, $data ), 
			
			// Everything else
			default		=> 
				\strtr( static::InputRender, $data )
		};
		
		return 
		\strtr( static::InputWrap, [ 
			'{desc}'	=> \strtr( static::DescRender, $data ),
			'{input}'	=> $input 
		] );
	}
	
	/**
	 *  Set default placeholder values
	 */
	public static baseDefaults( array &$data ) {
		// Application
		$data['{id}']			??= '';
		$data['{name}']			??= $data['{id}'];
		$data['{value}']		??= '';
		
		// Accessibility
		$data['{label}']		??= '';
		$data['{special}']		??= '';
		$data['{description}']		??= '';
		$data['{extras}']		??= '';
		
		// Reset selected if not set
		$data['{selected}']  		??= '';
	}
	
	/**
	 *  Default styling from tachyons.css
	 */
	public static baseStyling( array &$data ) {
		$data['{input_wrap_classes}']	??= '';
		$data['{label_classes}']	??= 'f6 b db mb2';
		$data['{special_classes}']	??= 'normal black-60';
		$data['{desc_classes}']		??= 'f6 black-70';
		$data['{input_classes}']	??= 
		match( $this ) {
			InputType::Select,
			InputType::Radio,
			InputType::Checkbox	=> 'input-reset',
			
			InputType::Textarea,
			InputType::Wysiwyg	=> 
			'db border-box black-80 w-100 ba b--black-50 pa2 mb2',
			
			default			=> 
			'input-reset db border-box black-80 w-100 ba b--black-50 pa2 mb2';
		};
	}
	
	/**
	 *  Select box options
	 */
	public static renderOptions( array $options ) {
		$opt = '';
		foreach ( $options as $o ) {
			$data = static::placeholders( $o );
			
			// Base defaults
			$data = static::baseDefaults( $data );
			
			$opt .= 
			\strtr( static::OptionRender, $data );
		}
		
		return $opt;
	}
	
	/**
	 *  Template placeholders in {value} format
	 */
	public static placeholders( array $input ) {
		$data = [];
		
		// Format data to placeholders
		\array_walk( $input, function( $v, $k ) use ( &$data ) {
			// Skip arrays
			if ( \is_array( $v ) ) { return; }
			
			$data['{' . $k . '}'] = $v;
		} );
		
		return $data;
	}
}

