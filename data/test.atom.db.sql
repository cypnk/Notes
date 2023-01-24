
-- SQL Syntax and behavior test DO NOT USE IN PRODUCTION

INSERT INTO entries( content ) 
VALUES (
	json( '{
		"title" : "A test entry",
		"content" : [
			{
				"content_type" : "text\/plain",
				"body" : "Some plaintext"
			},
			{
				"content_type" : "video\/mp4",
				"src" : "file.mp4"
			}
		],
		"links" : [
			{
				"rel" : "alternate"
			}
		]
	}' )
);

UPDATE entry_stats SET published = CURRENT_TIMESTAMP 
	WHERE entry_id = 1;

INSERT INTO authors( id, name ) VALUES ( 1, 'Test Author' );

INSERT INTO entry_authors( author_id, entry_id ) VALUES ( 1, 1 );



