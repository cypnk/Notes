
-- SQL Syntax and behavior test DO NOT USE IN PRODUCTION

INSERT INTO users ( username, user_clean, password ) 
	VALUES ( 'AzureDiamond', 'AzureDiamond', 'hunter2' );-- --

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
INSERT INTO documents ( abstract, type_id, settings ) 
	VALUES ( 
		'Test document1', 
		1, 
		json( '{ "setting_id" : "test1", "title" : "This is Not a Document", "lang" : "en" }' ) 
	);-- --

INSERT INTO history ( content, user_id ) VALUES ( json( '{ "label":"insert" }' ), 1 );-- --

UPDATE document_types SET content = json( '{ "label" : "historical", "lang" : "en" }' ) WHERE id = 1;-- --

INSERT INTO pages ( document_id, sort_order ) VALUES ( 1, 0 );-- --
INSERT INTO page_blocks ( page_id, content, sort_order ) 
	VALUES ( 1, json( '{ "body": "Test content heading", "render" : [ "heading" ] }' ), 0 );-- --

INSERT INTO page_blocks ( page_id, content, sort_order ) 
	VALUES ( 1, json( '{ "body": "This is a line of test content", "render" : [] }' ), 1 );-- --

INSERT INTO page_blocks ( page_id, content, sort_order ) 
	VALUES ( 1, json( '{ "body": "මෙම පාඨය සිංහල භාෂාවෙන් ඇත", "render" : [], "lang" : "si" }' ), 1 );-- --




