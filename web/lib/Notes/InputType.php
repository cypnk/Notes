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
	
	case Submit;
	case Reset;
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
	name="{name}" value="{value}" class="{input_classes}" 
	{extra}>{input_submit_after}{input_after}</p>
HTML;
	
	// Field wrapper
	const InputWrap		=<<<HTML
<p class="{input_wrap_classes}">{input_before}
{label_before}<label for="{id}" class="{label_classes}">{label}
	{special_before}<span class="{special_classes}"
	>{special}</span>{special_after}</label>{label_after} 
{input}{input_message}
{desc_before}<small id="{id}-desc" class="{desc_classes}" 
	{desc_extra}>{desc}</small>{desc_after}{input_after}</p>
HTML;
	
	// No label, no description
	const InputWrapNDNL	=<<<HTML
<p class="{input_wrap_classes}">{input_before}{input}{input_message}{input_after}</p>
HTML;

	// Description, but no label
	const InputWrapNL	=<<<HTML
<p class="{input_wrap_classes}">{input_before}
{input}{input_message}
{desc_before}<small id="{id}-desc" class="{desc_classes}" 
	{desc_extra}>{desc}</small>{desc_after}{input_after}</p>
HTML;

	// No description, has label, but no special
	const InputWrapNDNS	=<<<HTML
<p class="{input_wrap_classes}">{input_before}
{label_before}<label for="{id}" 
	class="{label_classes}">{label}</label>{label_after} 
	{input}{input_message}</p>
HTML;
	
	// No description
	const InputWrapND	=<<<HTML
<p class="{input_wrap_classes}">{input_before}
{label_before}<label for="{id}" class="{label_classes}">{label}
	{special_before}<span class="{special_classes}"
	>{special}</span>{special_after}</label>{label_after} 
{input}{input_message}</p>
HTML;
	
	/**
	 *  Error/validity pseudo element message holder
	 *  
	 *  @example
	 *  input:valid ~ .input-message { display:none; }
	 *  input:required:invalid ~ .input-message::after { content: attr(data-required); }
	 *  input:invalid:not(:placeholder-shown) ~ .input-message::after { content: attr(data-validation); }
	 */
	const InputMessage	= 
	'<span class="{message_classes}" {messages}></span>';

	
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
			\strtr( 
				$input['template'] ?? 
					static::InputTypeRender, 
				$data 
			),
			
			// Datetime
			InputType::DateTime	=> ( function() use ( $data ) {
				// Override type
				$data['{type}']	= 'datetime-local';
				
				return 
				\strtr( 
					$input['template'] ?? 
						static::InputTypeRender, 
					$data
				);
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
				\strtr( 
					$input['template'] ?? 
						static::SelectRender, 
					$data
				);
			} )(),
			
			// Option
			InputType::Radio,
			InputType::Checkbox	=> 
			\strtr( 
				$input['template'] ?? 
					static::TickRender, 
				$data
			),
			
			// Multiline
			InputType::Textarea	=> ( function() use ( $data ) {
				$data['{rows}']	??= 5;
				$data['{cols}']	??= 50;
				
				return 
				\strtr( 
					$input['template'] ?? 
						static::TextareaRender, 
					$data
				);
			} )(),
			
			// Multiline formatted
			InputType::Wysiwyg	=> ( function() use ( $data ) { 
				$data['{rows}']	??= 5;
				$data['{cols}']	??= 50;
				
				return 
				\strtr( 
					$input['template'] ?? 
						static::WysiwygRender, 
					$data
				);
			} )(),
			
			// Hidden inputs
			InputType::Hidden	=>
				\strtr( static::HiddenRender, $data ), 
			
			// Buttons
			InputType::Button,
			InputType::Reset,
			InputType::Submit	=>
			\strtr( 
				$input['template'] ?? 
					static::SubmitRender, 
				$data
			), 
			
			// Prepare input wrap template
			default		=> 
			\strtr( 
				$input['template'] ?? 
					static::InputRender, 
				$data
			)
		};
		
		return 
		match( $this ) {
			// Send bare for inline types
			InputType::Submit,
			InputType::Button,
			InputType::Hidden	=> $data['{input}'],
			
			// Send wrapped
			default			=> 
			\strtr( 
				static::baseWrapTemplate( $data ), 
				$data 
			)
		};
	}
	
	// TODO
	public function validate( array $input ) {
		
	}
	
	/**
	 *  Set default placeholder values
	 */
	public static function baseDefaults( array &$data ) {
		
		// Application
		$data['{id}']			??= '';
		$data['{name}']			??= $data['{id}'];
		$data['{value}']		??= '';
		$data['{placeholder}']		??= '';
		$data['{required}']		??= '';
		
		$data['{extra}']		??= '';
		
		// Accessibility
		$data['{label}']		??= '';
		$data['{special}']		??= '';
		$data['{description}']		??= '';
		$data['{messages}']		??= '';
		
		// Append validation message holder, if needed
		$data['{input_message}']	??= (
			empty( $data['{messages}'] ) ? 
			'' : 
			\strtr( static::InputMessage, [ 
				'{messages}' => ( string ) $data['{messages}'] 
			] )
		);
		
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
	 *  Select base input wrap template
	 */
	public static function baseWrapTemplate( array $data ) : string {
		return 
		match( true ) {
			// Skip if there's already a wrap template set
			( 
				!empty( $data['{wrap}'] )	|| 
				!empty( $data['{wrap_template}'] 
			)	=> '',
			
			// No label, no description
			( 
				empty( $data['{desc}'] )	&& 
				empty( $data['{label}'] )
			)	=> static::InputWrapNDNL,
			
			// Description, but no label
			( 
				!empty( $data['{desc}'] )	&& 
				empty( $data['{label}'] )
			)	=> static::InputWrapNL,
			
			// No description, has label, but no special
			( 
				empty( $data['{desc}'] )	&& 
				empty( $data['{special}'] )	&& 
				!empty( $data['{label}']
			)	=> static::InputWrapNDNS,
			
			// No description, but has label and special
			( 
				empty( $data['{desc}'] )	&& 
				!empty( $data['{special}']	&& 
				!empty( $data['{label}']
			)	=> static::InputWrapND,
			
			// Everything else gets wrapped
			default	=> static::InputWrap
		};
	}
	
	/**
	 *  Default styling from tachyons.css
	 */
	public static function baseStyling( array &$data ) {
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
		$data['{message_classes}']	??= 
			'input-message f6 measure-narrow bg-washed-red dark-red';
		
		$data['{input_classes}']	??= 
		match( $this ) {
			// Ticks
			InputType::Radio,
			InputType::Checkbox	=> 'b mr2',
			
			// Clickable
			InputType::Button,
			InputType::Submit	=> 
			'f6 button-reset pointer dim pa2 mb2 dib white bg-dark-blue',
			
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
	public static function renderOptions( array $options ) {
		$opt = '';
		foreach ( $options as $o ) {
			$data = \Notes\Util::placeholders( $o );
			
			// Base defaults
			$data = static::baseDefaults( $data );
			
			$opt .= 
			\strtr( static::OptionRender, $data );
		}
		
		return $opt;
	}
}

