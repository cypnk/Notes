<?php declare( strict_types = 1 );

namespace Notes;

enum InputType {
	
	case Text;
	case Email;
	case Radio;
	case Select;
	case Search;
	case Wysiwyg;
	case Checkbox;
	case Password;
	case Textarea;
	case DateTime;
	
	case Hidden;
	case Other;
	
	// TODO
	case Submit;
	case Button;
	
	// Generic input
	const InputRender	=<<<HTML
{input_field_before}<input id="{id}" name="{name}" type="text" 
	aria-describedby="{id}-desc" placeholder="{placeholder}" 
	class="{input_classes}" value="{value}" 
	{required}{extra}>{input_field_after}
HTML;
	
	// Typed text input
	const InputTypeRender	=<<<HTML
{input_field_before}<input id="{id}" name="{name}" type="{type}" 
	aria-describedby="{id}-desc" placeholder="{placeholder}" 
	class="{input_classes}" value="{value}" 
	{required}{extra}>{input_field_after}
HTML;
	
	// Checkbox and radio button
	const TickRender	=<<<HTML
{input_field_before}<input id="{id}" name="{name}" value="{value}" 
	type="{type}" class="{input_classes}" aria-describedby="{id}-desc" 
	{required}{extra}>{input_field_after}
HTML;
	
	// Multiline
	const TextareaRender	=<<<HTML
{input_field_before}<textarea id="{id}" name="{name}" 
	aria-describedby="{id}-desc" placeholder="{placeholder}" 
	rows="{rows} cols="{cols}" class="{input_classes}" 
	{required}{extra}>{value}</textarea>{input_field_after} 
HTML;
	
	// HTML Formatted multiline
	const WysiwygRender	=<<<HTML
{input_field_before}<div class="{wysiwyg-classes}" rel="{id}-wysiwyg"></div>
<textarea id="{id}" name="{name}" 
	aria-describedby="{id}-desc" placeholder="{placeholder}" 
	rows="{rows} cols="{cols}" class="{input_classes}" 
	data-wysiwyg="{id}" {required}{extra}
	>{value}</textarea>{input_field_after} 
HTML;
	
	// Multiple options
	const SelectRender	=<<<HTML
{input_field_before} 
<select id="{id}" name="{name}" aria-describedby="{id}-desc"
	class="{input_classes}" {required}{extra}>
	{unselect_option}{options}</select>{input_field_after} 
HTML;
	
	// Single option in above
	const OptionRender	= 
	'<option value="{value}" {selected}>{label}</option>';
	
	// Unselected option
	const UnselectRender	= '<option value="">--</option>';
	
	// Hidden input template
	const HiddenRender	= 
	'<input type="hidden" name="{name}" value="{value}">';
	
	const SubmitRender	=<<<HTML
<p class="{input_wrap_classes}">
{input_before}{input_submit_before}<input type="{type}" id="{id}" 
	name="{name}" value="{value}" class="{submit_classes}" 
	{extra}>{input_submit_after}{input_after}</p>
HTML;
	
	// Field wrapper
	const InputWrap		=<<<HTML
<p class="{input_wrap_classes}">{input_before}
{label_before}<label for="{id}" class="{label_classes}">{label}
	{special_before}<span class="{special_classes}"
	>{special}</span>{special_after}</label>{label_after} 
{input}
{desc_before}<small id="{id}-desc" class="{desc_classes}" 
	{desc_extra}>{desc}</small>{desc_after}{input_after}</p>
HTML;
	
	/**
	 *  Render the current input type in the above templates
	 */
	public function render( array $input ) : string {
		
		// Default to text type if not given
		$input['type'] ??= 'text';
		$input['type'] = \strtolower( $input['type'] );
		
		$data = static::placeholders( $input );
		
		// Default settings
		static::baseDefaults( $data );
		
		// Default styling
		static::baseStyling( $data );
		
		$data['{input}'} = 
		match( $this ) {
			// Flat text
			InputType::Text, 
			InputType::Email,
			InputType::Search, 
			InputType::Password	=> 
				\strtr( static::InputTypeRender, $data ),
			
			// Datetime
			InputType::DateTime	=> ( function() use ( $data ) {
				// Override type
				$data['{type}']	= 'datetime-local';
				
				return \strtr( static::InputTypeRender, $data )
			} )(),
			
			// Select
			InputType::Select	=> ( function() use ( $data, $input ) {
				// Set unselected (empty) option
				$data['{unselect_option}']	= 
				empty( $input['unselect'] ) ? '' : 
					static::UnselectRender;
				
				$data['{options}']		= 
				static::renderOptions( $input['options'] ?? [] );
				
				return 
				\strtr( static::SelectRender, $data ),
			} )(),
			
			// Option
			InputType::Radio,
			InputType::Checkbox	=> 
				\strtr( static::TickRender, $data ),
			
			// Multiline
			InputType::Textarea	=> ( function() use ( $data ) {
				$data['{rows}']	??= 5;
				$data['{cols}']	??= 50;
				
				return 
				\strtr( static::TextareaRender, $data );
			} )(),
			
			// Multiline formatted
			InputType::Wysiwyg	=> ( function() use ( $data ) { 
				$data['{rows}']	??= 5;
				$data['{cols}']	??= 50;
				
				return 
				\strtr( static::WysiwygRender, $data );
			} )(),
			
			// Hidden inputs
			InputType::Hidden	=>
				\strtr( static::HiddenRender, $data ), 
			
			// Buttons
			InputType::Button,
			InputType::Submit	=>
				\strtr( static::SubmitRender, $data ), 
				
			// Everything else
			default		=> 
				\strtr( static::InputRender, $data )
		};
		
		
		return 
		match( $this ) {
			// Send bare
			InputType::Submit,
			InputType::Button,
			InputType::Hidden	=> $data['{input}'],
			
			// Send wrapped
			default			=> 
				\strtr( static::InputWrap, $data )
		};
	}
	
	// TODO
	public function validate( array $input ) {
		
	}
	
	/**
	 *  Set default placeholder values
	 */
	public static baseDefaults( array &$data ) {
		
		// Application
		$data['{id}']			??= '';
		$data['{name}']			??= $data['{id}'];
		$data['{value}']		??= '';
		$data['{placeholder}']		??= '';
		$data['{required}']		??= '';
		
		// Accessibility
		$data['{label}']		??= '';
		$data['{special}']		??= '';
		$data['{description}']		??= '';
		$data['{extra}']		??= '';
		
		// Reset selected if not set
		$data['{selected}']  		??= '';
		
		// Overridden content
		$data['{input_field_before}']	??= '';
		$data['{input_field_after}']	??= '';
		
		$data['{input_before}']		??= '';
		$data['{input_after}']		??= '';
		
		$data['{label_before}']		??= '';
		$data['{label_after}']		??= '';
		$data['{special_before}']	??= '';
		$data['{special_after}']	??= '';
		$data['{desc_before}']		??= '';
		$data['{desc_after}']		??= '';
		
		$data['{special_extras}']	??= '';
		$data['{desc_extras}']		??= '';
		$data['{label_extras}']		??= '';
		
	}
	
	/**
	 *  Default styling from tachyons.css
	 */
	public static baseStyling( array &$data ) {
		$data['{input_wrap_classes}']	??= 'pa4 black-80';
		$data['{label_classes}']	??= 
		match( $this ) { 
			// Radio labels are simpler
			InputType::Radio,
			InputType::Checkbox	=> 'f6 b lh-copy mb2',
			
			default			=> 'f6 b db mb2'
		};
		
		$data['{special_classes}']	??= 'normal black-80';
		$data['{desc_classes}']		??= 'f6 black-80';
		$data['{input_classes}']	??= 
		match( $this ) {
			// Ticks
			InputType::Radio,
			InputType::Checkbox	=> 'b mr2',
			
			// Clickable
			InputType::Button,
			InputType::Submit	=> 
			'f6 link dim ph3 pv2 mb2 dib white bg-dark-blue',
			
			// Multiline
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
			
			$data['{' . $k . '}'] = $v ?? '';
		} );
		
		return $data;
	}
}

