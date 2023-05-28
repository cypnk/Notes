
-- Event table
CREATE TABLE event_logs (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
	content TEXT NOT NULL DEFAULT '{ "label" : "event", "body" : "" }' COLLATE NOCASE, 
	label TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.label' ), 'event' )
	) STORED NOT NULL,
	body TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.body' ), '' )
	) STORED NOT NULL,
	host TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.host' ), NULL )
	) STORED,
	secure TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.secure' ), NULL )
	) STORED,
	ip TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.ip' ), NULL )
	) STORED,
	method TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.method' ), NULL )
	) STORED,
	uri TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.uri' ), NULL )
	) STORED,
	user_agent TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.user_agent' ), NULL )
	) STORED,
	query_string TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.query_string' ), NULL )
	) STORED,
	language TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.language' ), NULL )
	) STORED,
	file_range TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.file_range' ), NULL )
	) STORED,
	
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	status INTEGER DEFAULT 0
);-- --
CREATE INDEX idx_log_label ON event_logs ( label );-- --
CREATE INDEX idx_log_host ON event_logs ( host )
	WHERE host IS NOT NULL;-- --
CREATE INDEX idx_log_secure ON event_logs ( secure )
	WHERE secure IS NOT NULL;-- --
CREATE INDEX idx_log_ip ON event_logs ( ip )
	WHERE ip IS NOT NULL;-- --
CREATE INDEX idx_log_method ON event_logs ( method )
	WHERE method IS NOT NULL;-- --
CREATE INDEX idx_log_uri ON event_logs ( uri )
	WHERE uri IS NOT NULL;-- --
CREATE INDEX idx_log_ua ON event_logs ( user_agent )
	WHERE user_agent IS NOT NULL;-- --
CREATE INDEX idx_log_qs ON event_logs ( query_string )
	WHERE query_string IS NOT NULL;-- --
CREATE INDEX idx_log_lang ON event_logs ( language )
	WHERE language IS NOT NULL;-- --
CREATE INDEX idx_log_fr ON event_logs ( file_range )
	WHERE file_range IS NOT NULL;-- --
CREATE INDEX idx_log_created ON event_logs ( created );-- --
CREATE INDEX idx_log_status ON event_logs ( status );-- --

-- Event searching
CREATE VIRTUAL TABLE log_search 
	USING fts4( body, tokenize=unicode61 );-- --


CREATE TRIGGER log_insert AFTER INSERT ON event_logs FOR EACH ROW
WHEN NEW.body IS NOT ""
BEGIN
	INSERT INTO log_search( docid, body ) 
		VALUES ( NEW.id, NEW.body );
END;-- --

CREATE TRIGGER log_delete BEFORE DELETE ON event_logs FOR EACH ROW 
BEGIN
	DELETE FROM log_search WHERE docid = OLD.id;
END;-- --


