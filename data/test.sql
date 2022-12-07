
-- SQL Syntax and behavior test DO NOT USE IN PRODUCTION

INSERT INTO events ( label, settings )
	VALUES 
	-- Single-user params
	( 'app_start', json( 
		'{
			"environment" : "Linux\/Manjaro",
			"write" : "ltr",
			"language" : "en-US",
			"autosave" : 1,
			"font" : "NotoSansMono-Regular.ttf",
			"window" : "1024x800"
		}'
	) ),
	
	-- Web/multi-user params
	( 'web_start', json(
		'{
			"realm" : "http:\/\/localhost",
			"routes" [
				[ "get", "",					"home" ],
				[ "get", "page:page",				"homepaginate" ],
				[ "get", "feed",				"feed" ],
				
				[ "get", ":year",				"archive" ],
				[ "get", ":year\/page:page",			"archive" ],
				[ "get", ":year\/:month",			"archive" ],
				[ "get", ":year\/:month\/page:page",		"archive" ],
				[ "get", ":year\/:month\/:day",			"archive" ],
				
				[ "get", "tags\/:tag",				"tagview" ],
				[ "get", "tags\/:tag\/page:page",		"tagpaginate" ],
				
				[ "get", "\\\\?nonce=:nonce&token=:token&meta=&find=:find", "search" ],
				[ "get", "\\\\?nonce=:nonce&token=:token&meta=&find=:find\/page:page", "searchpaginate" ],
				
				[ "get", "login",				"loginview" ],
				[ "post", "login",				"loginsent" ],
				[ "get", "register",				"registerview" ],
				[ "post", "register",				"registersent" ]
			]
		}'
	) );-- --

INSERT INTO users ( username, user_clean, password ) 
	VALUES ( 'AzureDiamond', 'AzureDiamond', 'hunter2' );-- --

INSERT INTO roles ( label, description ) 
	VALUES ( 'admin', 'Global manager' );-- --

INSERT INTO roles ( label, description ) 
	VALUES ( 'editor', 'Content manager' );-- --

INSERT INTO role_privileges ( role_id, settings ) 
	VALUES ( 1, json( '{ "scope" : "global", "actions" : [ "*" ] }' ) );-- --

INSERT INTO role_privileges ( role_id, settings ) 
	VALUES ( 2, json( 
		'{ "scope" : "local", "actions" : [ 
			"doccreate", "docedit", "docdelete", 
			"pagecreate", "pageedit", "pagedelete",
			"blockcreate", "blockedit", "blockdelete"
		] }' 
	) );-- --

INSERT INTO configs ( settings )
	VALUES
	-- Global (default)
	( json( '{
		"path" : "{path}",
		"store" : "{store}",
		"files" : "{files}",
		"temp" : "{temp}",
		"jobs" : "{jobs}",
		"mail_from" : "domain@localhost",
		"date_nice" : "l, F j, Y",
		"timezone" : "UTC",
		"folder_limit" : 15,
		"allow_upload" : 0,
		"ext_whitelist" : {
			"text"		: "txt",
			"images"	: "jpg, jpeg, gif, png", 
			"fonts"		: "",
			"audio"		: "",
			"video"		: "",
			"documents"	: "",
			"archives"	: ""
		},
		"language" : "en-US",
		"stream_chunk_limit" : 50000,
		"stream_chunk_size" : 4096,
		"token_bytes" : 8,
		"nonce_hash" : "tiger160,4",
		"max_url_size" : 512,
		"max_log_size" : 5000000,
		"max_search_words" : 10,
		"max_page" : 500, 
		"max_block" : 1500, 
		"frame_whitelist" : []
	}' ) ),
	
	-- Web realm specific (testing on localhost)
	( json( '{
		"realm" : "http:\/\/localhost",
		"files" : "{files}localhost\/",
		"temp" : "{temp}localhost\/",
		"jobs" : "{jobs}localhost\/",
		"stylesheets": [ "tachyons.min.css", "\/localhost\/style.css" ],
		"cookie_path" : "\/",
		"cache_ttl" : 7200,
		"mail_from" : "admin@localhost",
		"timezone" : "America\/New_York",
		"allow_upload" : 1,
		"ext_whitelist" : {
			"text"		: "css, js, txt, html, vtt",
			"images"	: "ico, jpg, jpeg, gif, bmp, png, tif, tiff, svg, webp", 
			"fonts"		: "ttf, otf, woff, woff2",
			"audio"		: "ogg, oga, mpa, mp3, m4a, wav, wma, flac",
			"video"		: "avi, mp4, mkv, mov, ogg, ogv",
			"documents"	: "doc, docx, ppt, pptx, pdf, epub",
			"archives"	: "zip, rar, gz, tar"
		},
		"max_url_size" : 1024,
		"max_search_words" : 20,
		"frame_whitelist" : [
			"https:\/\/www.youtube.com", 
			"https:\/\/player.vimeo.com",
			"https:\/\/archive.org",
			"https:\/\/peertube.mastodon.host",
			"https:\/\/lbry.tv",
			"https:\/\/odysee.com",
			"https:\/\/utreon.com"
		], 
		"security_policy" : {
			"content-security-policy": {
				"default-src"			: "''none''",
				"img-src"			: "*",
				"base-uri"			: "''self''",
				"style-src"			: "''self''",
				"script-src"			: "''self''",
				"font-src"			: "''self''",
				"form-action"			: "''self''",
				"frame-ancestors"		: "''self''",
				"frame-src"			: "*",
				"media-src"			: "''self''",
				"connect-src"			: "''self''",
				"worker-src"			: "''self''",
				"child-src"			: "''self''",
				"require-trusted-types-for"	: "''script''"
			},
			"permissions-policy": {
				"accelerometer"			: [ "none" ],
				"camera"			: [ "none" ],
				"fullscreen"			: [ "self" ],
				"geolocation"			: [ "none" ],
				"gyroscope"			: [ "none" ],
				"interest-cohort"		: [],
				"payment"			: [ "none" ],
				"usb"				: [ "none" ],
				"microphone"			: [ "none" ],
				"magnetometer"			: [ "none" ]
			}, 
			"common-policy": [
				"X-XSS-Protection: 1; mode=block",
				"X-Content-Type-Options: nosniff",
				"X-Frame-Options: SAMEORIGIN",
				"Referrer-Policy: no-referrer, strict-origin-when-cross-origin"
			]
		}, 
		"tag_white" : {
			"p"		: [ "style", "class", "align", 
						"data-pullquote", "data-video", 
						"data-media" ],
			
			"div"		: [ "style", "class", "align" ],
			"span"		: [ "style", "class" ],
			"br"		: [ "style", "class" ],
			"hr"		: [ "style", "class" ],
			
			"h1"		: [ "style", "class" ],
			"h2"		: [ "style", "class" ],
			"h3"		: [ "style", "class" ],
			"h4"		: [ "style", "class" ],
			"h5"		: [ "style", "class" ],
			"h6"		: [ "style", "class" ],
			
			"strong"	: [ "style", "class" ],
			"em"		: [ "style", "class" ],
			"u"	 	: [ "style", "class" ],
			"strike"	: [ "style", "class" ],
			"del"		: [ "style", "class", "cite" ],
			
			"ol"		: [ "style", "class" ],
			"ul"		: [ "style", "class" ],
			"li"		: [ "style", "class" ],
			
			"code"		: [ "style", "class" ],
			"pre"		: [ "style", "class" ],
			
			"sup"		: [ "style", "class" ],
			"sub"		: [ "style", "class" ],
			
			"a"		: [ "style", "class", "rel", 
						"title", "href" ],
			"img"		: [ "style", "class", "src", "height", "width", 
						"alt", "longdesc", "title", "hspace", 
						"vspace", "srcset", "sizes",
						"data-srcset", "data-src", 
						"data-sizes" ],
			"figure"	: [ "style", "class" ],
			"figcaption"	: [ "style", "class" ],
			"picture"	: [ "style", "class" ],
			"table"		: [ "style", "class", "cellspacing", 
							"border-collapse", 
							"cellpadding" ],
			
			"thead"		: [ "style", "class" ],
			"tbody"		: [ "style", "class" ],
			"tfoot"		: [ "style", "class" ],
			"tr"		: [ "style", "class" ],
			"td"		: [ "style", "class", "colspan", 
						"rowspan" ],
			"th"		: [ "style", "class", "scope", 
						"colspan", "rowspan" ],
			
			"caption"	: [ "style", "class" ],
			"col"		: [ "style", "class" ],
			"colgroup"	: [ "style", "class" ],
			
			"summary"	: [ "style", "class" ],
			"details"	: [ "style", "class" ],
			
			"q"		: [ "style", "class", "cite" ],
			"cite"		: [ "style", "class" ],
			"abbr"		: [ "style", "class" ],
			"blockquote"	: [ "style", "class", "cite" ],
			"body"		: []
		}, 
		"classes" : {
			"body_classes"			: "",
			
			"heading_classes"		: "",
			"heading_wrap_classes"		: "content", 
			"heading_h_classes"		: "",
			"heading_a_classes"		: "",
			
			"code_wrap_classes"		: "measure overflow-auto",
			"code_classes"			: "code",
			
			"center_classes"		: "tc",
			"left_classes"			: "tl",
			"right_classes"			: "tr",
			
			"form_classes"			: "pa4 black-80 measure",
			"legend_classes"		: "mb2 f4 measure",
			"fieldset_classes"		: "",
			
			"label_classes"			: "f6 b db mb2"",
			"special_classes"		: "normal black-60",
			"input_classes"			: "input-reset db border-box black-80 w-100 ba b--black-50 pa2 mb2",
			"desc_classes"			: "f6 lh-copy black-70 db mb2",
			
			"text_label_classes"		: "f6 b db mb2",
			"text_special_classes"		: "normal black-60",
			"text_desc_classes"		: "f6 lh-copy black-70 db mb2",
			"text_input_classes"		: "input-reset db border-box black-80 w-100 ba b--black-50 pa2 mb2",
			
			"password_label_classes"	: "f6 b db mb2",
			"password_special_classes"	: "normal black-60",
			"password_text_classes"		: "input-reset db border-box black-80 w-100 ba b--black-50 pa2 mb2",
			"password_desc_classes"		: "f6 lh-copy black-70 db mb2",
			
			"email_label_classes"		: "f6 b db mb2",
			"email_special_classes"		: "normal black-60",
			"email_input_classes"		: "input-reset db border-box black-80 w-100 ba b--black-50 pa2 mb2",
			"email_desc_classes"		: "f6 lh-copy black-70 db mb2",
			
			"multiline_label_classes"	: "f6 b db mb2",
			"multiline_special_classes"	: "normal black-60",
			"multiline_input_classes"	: "db border-box black-80 w-100 ba b--black-50 pa2 mb2",
			"multiline_desc_classes"	: "f6 lh-copy black-70 db mb2",
			
			"search_form_classes"		: "mw7 center pa4 br2-ns ba b--black-10",
			"search_form_wrap_classes"	: "pa4-l",
			"search_fieldset_classes"	: "cf bn ma0 pa0",
			"search_legend_classes"		: "pa0 f5 f4-ns mb3 black-80",
			"search_label_classes"		: "clip",
			"search_special_classes"	: "normal black-60",
			"search_input_classes"		: "input-reset bn fl black-80 bg-white pa3 lh-solid w-100 w-75-m w-80-l br2-ns br--left-ns",
			"search_button_classes"		: "button-reset fl pv3 tc bn bg-animate bg-black-70 hover-bg-black white pointer w-100 w-25-m w-20-l br2-ns br--right-ns",
			
			"check_label_classes"		: "pa0 ma0 lh-copy b f6 pointer",
			"radio_label_classes"		: "pa0 ma0 lh-copy b f6 pointer",
			
			"submit_classes"		: "f6 input-reset pointer dim pa2 mb2 dib white bg-dark-gray",
			"alt_classes"			: "f6 input-reset pointer dim pa2 mb2 dib white bg-navy",
			"warn_classes"			: "f6 input-reset pointer dim pa2 mb2 dib white bg-light-red",
			"action_classes"		: "f6 input-reset pointer dim pa2 mb2 dib white bg-green",
			
			"table_wrap_classes"		: "collapse overflow-auto",
			"table_classes"			: "f6 w-100 mw8 center table",
			"table_header_classes"		: "lh-copy bg-white table-header",
			"table_body_classes"		: "lh-copy table-body",
			"table_footer_classes"		: "lh-copy table-footer",
			"table_row_classes"		: "stripe-dark table-row",
			"table_row_odd_classes"		: "table-row-odd",
			"table_row_even_classes"	: "table-row-even",
			"table_th_classes"		: "fw6 tl pa2 table-th",
			"table_td_classes"		: "pa2 table-td",
	       		"table_td_center_classes"	: "tc",
	       		"table_td_left_classes"		: "tl",
	       		"table_td_right_classes"	: "tr"
		}
	}' ) );-- --


-- Default language
INSERT INTO languages (
	id, iso_code, label, display, is_default
) VALUES ( 1, 'en', 'English', 'English', 1 );-- --

-- Others
INSERT INTO languages (
	iso_code, label, display
) VALUES 
( 'ar', 'عربى', 'Arabic' ),
( 'be', 'Беларуская мова', 'Belarusian' ),
( 'bn', 'বাংলা', 'Bengali' ),
( 'bo', 'ལྷ་སའི་སྐད་', 'Tibetan' ),
( 'ca', 'Català', 'Catalan' ),
( 'cs', 'Čeština', 'Czech' ),
( 'da', 'Dansk', 'Danish' ),
( 'de', 'Deutsch', 'German' ),
( 'el', 'Ελληνικά', 'Greek' ),
( 'eo', 'Esperanto', 'Esperanto' ),
( 'es', 'Español', 'Spanish' ), 
( 'et', 'Eesti', 'Estonian' ),
( 'fa', 'فارسی', 'Farsi' ),
( 'fi', 'Suomi', 'Finnish' ),
( 'fr', 'Français', 'French' ),
( 'ga', 'Gaeilge', 'Gaelic' ),
( 'gu', 'ગુજરાતી', 'Gujarati' ),
( 'he', 'עברית‬', 'Hebrew' ),
( 'hi', 'हिंदी', 'Hindi' ),
( 'hr', 'Hrvatski', 'Croatian' ),
( 'hu', 'Magyar', 'Hungarian' ),
( 'hy', 'Հայերեն', 'Armenian' ),
( 'ia', 'Interlingua', 'Interlingua' ),
( 'it', 'Italiano', 'Italian' ),
( 'jp', '日本語', 'Japanese' ),
( 'ka', 'ქართული ენა', 'Georgian' ),
( 'kn', 'ಕನ್ನಡ', 'Kannada' ),
( 'ko', '조선말', 'Korean' ),
( 'lt', 'Lietuvių kalba', 'Lithuanian' ),
( 'lo', 'ພາສາລາວ', 'Lao' ),
( 'lv', 'Latviešu valoda', 'Latvian' ),
( 'ml', 'Melayu', 'Malay' ),
( 'my', 'မြန်မာဘာသာ', 'Myanmar' ),
( 'nl', 'Nederlands', 'Dutch' ),
( 'no', 'Norsk', 'Norwegian' ),
( 'pa', 'ਪੰਜਾਬੀ', 'Punjabi' ),
( 'pt', 'Português', 'Portuguese' ),
( 'pl', 'Język polski', 'Polish' ),
( 'ro', 'Limba română', 'Romanian' ),
( 'ru', 'русский', 'Russian' ),
( 'sl', 'Slovenska', 'Slovenian' ),
( 'sk', 'Slovenčina', 'Slovak' ),
( 'si', 'සිංහල', 'Sinhalese' ),
( 'sr', 'Srpski', 'Serbian' ),
( 'sv', 'Svenska', 'Swedish' ),
( 'ta', 'தமிழ்', 'Tamil' ),
( 'te', 'తెలుగు', 'Telugu' ),
( 'th', 'ภาษาไทย', 'Thai' ),
( 'tk', 'türkmençe', 'Turkmen' ),
( 'tr', 'Türkçe', 'Turkish' ),
( 'uk', 'Українська', 'Ukranian' ),
( 'ur', 'اُردُو‬', 'Urdu' ),
( 'uz', 'oʻzbek tili', 'Uzbek' ),
( 'vi', 'Tiếng Việt', 'Vietnamese' ),
( 'zh', '中文', 'Chinese' );-- --


INSERT INTO document_types( content ) VALUES ( json( '{ "label" : "document" }' ) );
INSERT INTO documents ( summary, type_id, settings ) 
	VALUES ( 
		'Test document1', 
		1, 
		json( '{ "setting_id" : "test1", "title" : "This is Not a Document", "lang" : "en" }' ) 
	);-- --

INSERT INTO history ( content, user_id ) VALUES ( json( '{ "label":"insert" }' ), 1 );-- --

UPDATE document_types SET content = json( '{ "label" : "historical", "lang" : "en" }' ) WHERE id = 1;-- --

INSERT INTO pages ( document_id, sort_order ) VALUES ( 1, 0 );-- --

INSERT INTO block_types( content, view_template, create_template, edit_template, delete_template ) 
	VALUES (
	json( '{ 
		"label"			: "textarea", 
		"id"			: "block-text", 
		"name"			: "block-text", 
		"rows"			: 10,
		"cols"			: 60,
		"required"		: "required",
		"value"			: "",
		"extra"			: " data-feature=\"autoheight, droppable\"",
		
		"create_template"	: {
			"input_before"		: "{event:input_before}",
			"input_multiline_before": "{event:input_multiline_before}",
			"label_before"		: "{event:label_before}",
			"label"			: "{lang:forms:page_block:create_label}",
			"label_after"		: "{event:label_after}",
			"input_field_before"	: "{event:input_before}",
			"special_before"	: "{event:special_before}",
		     	"special"		: "{lang:forms:page_block:create_special}",
		     	"special_after"		: "{event:special_after}",
		     	"input_before"		: "{event:input_before}",
		     	"input_field_before"	: "{event:input_field_before}",
		     	"input_field_after"	: "{event:input_field_after}",
		     	"desc_before"		: "{event:desc_before}",
		     	"desc"			: "{lang:forms:page_block:create_desc}",
		     	"desc_after"		: "{event:desc_after}",
		     	"input_multiline_after"	: "{event:input_multiline_after}",
		     	"input_after"		: "{event:input_after}"
		},
		"edit_template"		: {
			"input_before"		: "{event:input_before}",
			"input_multiline_before": "{event:input_multiline_before}",
			"label_before"		: "{event:label_before}",
			"label"			: "{lang:forms:page_block:edit_label}",
			"label_after"		: "{event:label_after}",
			"input_field_before"	: "{event:input_before}",
			"special_before"	: "{event:special_before}",
		     	"special"		: "{lang:forms:page_block:edit_special}",
		     	"special_after"		: "{event:special_after}",
		     	"input_before"		: "{event:input_before}",
		     	"input_field_before"	: "{event:input_field_before}",
		     	"input_field_after"	: "{event:input_field_after}",
		     	"desc_before"		: "{event:desc_before}",
		     	"desc"			: "{lang:forms:page_block:edit_desc}",
		     	"desc_after"		: "{event:desc_after}",
		     	"input_multiline_after"	: "{event:input_multiline_after}",
		     	"input_after"		: "{event:input_after}"
		},
		"delete_template"	: {
			"input_before"		: "{event:input_before}",
			"input_warn_before"	: "{event:input_warn_before}",
			"value"			: "{lang:forms:page_block:delete_warn}",
			"input_warn_after"	: "{event:input_warn_after}",
			"input_after"		: "{event:input_after}"
		},
		"view_template"		: {
			"before_block"		: "{event:before_block}",
			"before_full_block"	: "{event:before_full_block}", 
			"before_block_body"	: "{event:before_block_body}", 
			"after_block_body"	: "{event:after_block_body}", 
			"after_full_block"	: "{event:after_full_block}", 
			"after_block"		: "{event:after_block}"
	     }
	}' ), 
	'{before_block}
<article class="{block_classes}">{before_full_block}
	{before_block_body}
	<div class="{block_body_wrap_classes}">
		<div class="{block_body_content_classes}">{value}</div>
	</div>{after_block_body}
	</div>{after_full_block}
</article>{after_block}', 
	'{input_before}{input_multiline_before}
{label_before}<label for="{id}" class="{label_classes}">{label}
	{special_before}<span class="{special_classes}"
	>{special}</span>{special_after}</label>{label_after} 
{input_field_before}<textarea id="{id}" name="{name}" rows="{rows} cols="{cols}" 
	placeholder="{placeholder}" aria-describedby="{id}-desc"
	 class="{input_classes}" {required}{extra}></textarea>{input_field_after}
{desc_before}<small id="{id}-desc" class="{desc_classes}" 
	{desc_extra}>{desc}</small>{desc_after}{input_after}
{input_multiline_after}{input_after}',
	
	'{input_before}{input_multiline_before}
{label_before}<label for="{id}" class="{label_classes}">{label}
	{special_before}<span class="{special_classes}"
	>{special}</span>{special_after}</label>{label_after} 
{input_field_before}<textarea id="{id}" name="{name}" rows="{rows} cols="{cols}" 
	placeholder="{placeholder}" aria-describedby="{id}-desc"
		class="{input_classes}" {required}{extra}>{value}</textarea>{input_field_after}
{desc_before}<small id="{id}-desc" class="{desc_classes}" 
	{desc_extra}>{desc}</small>{desc_after}{input_after}
{input_multiline_after}{input_after}', 
	'{input_before}{input_warn_before}<input type="submit" id="{id}", 
	name="{name}" value="{value}" class="{warn_classes}" 
	{extra}>{input_warn_after}{input_after}'
);

INSERT INTO page_blocks ( type_id, page_id, content, sort_order ) 
	VALUES ( 1, 1, json( '{ "body": "Test content heading", "render" : [ "heading" ], "lang" : "en" }' ), 0 );-- --

INSERT INTO page_blocks ( type_id, page_id, content, sort_order ) 
	VALUES ( 1, 1, json( '{ "body": "This is a line of test content", "render" : [], "lang" : "en" }' ), 1 );-- --

INSERT INTO page_blocks ( type_id, page_id, content, sort_order ) 
	VALUES ( 1, 1, json( '{ "body": "මෙම පාඨය සිංහල භාෂාවෙන් ඇත", "render" : [], "lang" : "si" }' ), 1 );-- --
	
INSERT INTO page_blocks ( type_id, page_id, content, sort_order ) 
	VALUES ( 1, 1, json( '{ "body": "The above text is in Sinhalese", "render" : [], "lang" : "en" }' ), 1 );-- --

UPDATE page_blocks SET content = json( '{ "body": "Este texto fue cambiado a Español", "render" : [], "lang" : "es" }' ) 
	WHERE id = 2;

INSERT INTO page_blocks ( type_id, page_id, content, sort_order ) 
	VALUES ( 1, 1, json( '{ "body": "The second block of text was changed to Spanish", "render" : [], "lang" : "en" }' ), 1 );-- --


INSERT INTO operations ( label, pattern, settings ) 
	VALUES 
	( 'homepage', '', json( '{ 
		"event" : "home_request",
		"scope" : "global",
		"method" : "get",
		"realm" : "\/"
	}' ) ),
	( 'search content', '', json( '{ 
		"event" : "search_request",
		"scope" : "global",
		"method" : "get",
		"realm" : "\/search\/{all}"
	}' ) ),
	( 'insert document table of contents', '[doc_contents]', json( '{ 
		"event" : "insert_doc_toc",
		"scope" : "document",
		"method" : "text",
		"realm" : ""
	}' ) ),
	( 'insert page table of contents', '[page_contents]', json( '{ 
		"event" : "insert_page_toc",
		"scope" : "page",
		"method" : "text",
		"realm" : ""
	}' ) ),
	( 'insert block table of contents', '[block_contents]', json( '{ 
		"event" : "insert_block_toc",
		"scope" : "block",
		"method" : "text",
		"realm" : ""
	}' ) ),
	( 'sum range in selected block', '=SUM({ranges})', json( '{ 
		"event" : "block_range_sum",
		"scope" : "block",
		"method" : "text",
		"realm" : ""
	}' ) );-- --
