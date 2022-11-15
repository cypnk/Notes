
-- SQL Syntax and behavior test DO NOT USE IN PRODUCTION

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
		"max_url_size" : 512,
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
		"stream_chunk_limit" : 50000,
		"stream_chunk_size" : 4096,
		"token_bytes" : 8,
		"max_log_size" : 5000000,
		"nonce_hash" : "tiger160,4",
		"frame_whitelist" : []
	}' ) ),
	
	 -- Web realm specific (testing on localhost)
	( json( '{
		"realm" : "http:\/\/localhost",
		"files" : "{files}localhost\/",
		"temp" : "{temp}localhost\/",
		"jobs" : "{jobs}localhost\/",
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
		"max_search_words" : 20,
		"frame_whitelist" : [
			"https:\/\/www.youtube.com", 
			"https:\/\/player.vimeo.com",
			"https:\/\/archive.org",
			"https:\/\/peertube.mastodon.host",
			"https:\/\/lbry.tv",
			"https:\/\/odysee.com",
			"https:\/\/utreon.com"
		]
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
INSERT INTO page_blocks ( page_id, content, sort_order ) 
	VALUES ( 1, json( '{ "body": "Test content heading", "render" : [ "heading" ], "lang" : "en" }' ), 0 );-- --

INSERT INTO page_blocks ( page_id, content, sort_order ) 
	VALUES ( 1, json( '{ "body": "This is a line of test content", "render" : [], "lang" : "en" }' ), 1 );-- --

INSERT INTO page_blocks ( page_id, content, sort_order ) 
	VALUES ( 1, json( '{ "body": "මෙම පාඨය සිංහල භාෂාවෙන් ඇත", "render" : [], "lang" : "si" }' ), 1 );-- --
	
INSERT INTO page_blocks ( page_id, content, sort_order ) 
	VALUES ( 1, json( '{ "body": "The above text is in Sinhalese", "render" : [], "lang" : "en" }' ), 1 );-- --

UPDATE page_blocks SET content = json( '{ "body": "Este texto fue cambiado a Español", "render" : [], "lang" : "es" }' ) 
	WHERE id = 2;

INSERT INTO page_blocks ( page_id, content, sort_order ) 
	VALUES ( 1, json( '{ "body": "The second block of text was changed to Spanish", "render" : [], "lang" : "en" }' ), 1 );-- --


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
