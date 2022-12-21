
-- Event table
CREATE TABLE event_logs (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
	content TEXT NOT NULL DEFAULT '{ "label" : "event", "body" : "" }' COLLATE NOCASE, 
	label TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.label' ), "event" )
	) STORED NOT NULL,
	body TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.body' ), "" )
	) STORED NOT NULL,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	status INTEGER DEFAULT 0
}
CREATE INDEX idx_log_label ON event_logs ( label );-- --
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


