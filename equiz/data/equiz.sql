DROP TABLE IF EXISTS Quiz;
CREATE TABLE Quiz (
	id INTEGER PRIMARY KEY,
	title TEXT NOT NULL,
	duetime TIMESTAMP,
	tags TEXT NOT NULL,
	descrip TEXT
);

INSERT INTO Quiz
 (id, title, duetime, tags, descrip)
 VALUES
 (NULL, 'test', datetime('2010-12-31 23:59:59', '-8 hours'), "test", "description of this Quiz");

DROP TABLE IF EXISTS Question;
CREATE TABLE Question (
	id INTEGER PRIMARY KEY,
	quizId INTEGER REFERENCES Quiz (id) ON DELETE SET NULL,
	seq INTEGER NOT NULL,
	type INTEGER NOT NULL,
	body TEXT NOT NULL,
	options TEXT NOT NULL,
	answers TEXT NOT NULL,
	mtime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	comments TEXT,
	UNIQUE (quizId, seq)
);

INSERT INTO Question
 (id, quizId, seq, type, body, options, answers, comments)
 VALUES
 (NULL, 1, 1, 1, "Bill Gates is ____ man in the world.",
  "the most rich|the most richest|the richest", "2", "optional explanation of answers");
INSERT INTO Question
 (id, quizId, seq, type, body, options, answers)
 VALUES
 (NULL, 1, 2, 1, "He has been in China ____ 1960.",
  "since|for|ago", "0");
INSERT INTO Question
 (id, quizId, seq, type, body, options, answers)
 VALUES
 (NULL, 1, 3, 3, "input abc ___, input cde ___ ss.",
  "___", "abc|cde");
INSERT INTO Question
 (id, quizId, seq, type, body, options, answers)
 VALUES
 (NULL, 1, 4, 2, "please aaa and ccc.",
  "aaa|000|ccc|xxx", "0|2");


DROP TABLE IF EXISTS PartInfo;
CREATE TABLE PartInfo (
	id INTEGER PRIMARY KEY,
	name TEXT NOT NULL,
	email TEXT NOT NULL,
	tags TEXT
);
INSERT INTO PartInfo (id, name, email, tags)
 VALUES (NULL, "Leo Wang", "leo.wang@alcatel-lucent.com", "test");


DROP TABLE IF EXISTS Submission;
CREATE TABLE Submission (
	quizId INTEGER NOT NULL REFERENCES Quiz (id) ON DELETE CASCADE,
	pId INTEGER NOT NULL REFERENCES PartInfo (id) ON DELETE CASCADE,
	subtime TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	subValue TEXT NOT NULL,
	PRIMARY KEY (quizId, pId)
);

DROP TABLE IF EXISTS Token;
CREATE TABLE Token (
	token CHARACTER(18),
	quizId INTEGER NOT NULL REFERENCES Quiz (id) ON DELETE CASCADE,
	pId INTEGER NOT NULL REFERENCES PartInfo (id) ON DELETE CASCADE,
	stat TEXT NOT NULL DEFAULT 0,
	PRIMARY KEY (quizId, pId)
);
INSERT INTO Token (token, quizId, pId)
 VALUES ('testtest1234567890', 1, 1);

CREATE TABLE htmlCache (
quizId INTEGER NOT NULL REFERENCES Quiz (id) ON DELETE CASCADE,
type INTEGER NOT NULL,
body TEXT NOT NULL,
mtime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
UNIQUE (quizId, type)
);


CREATE TRIGGER update_q_mtime UPDATE ON Question  
  BEGIN 
    UPDATE Question SET mtime = CURRENT_TIMESTAMP WHERE id = old.id; 
  END; 

CREATE TRIGGER update_cache_mtime UPDATE ON htmlCache  
  BEGIN 
    UPDATE htmlCache SET mtime = CURRENT_TIMESTAMP WHERE quizId = old.quizId and type = old.type; 
  END; 

CREATE TRIGGER clear_htmlCache_updQ AFTER UPDATE ON Question  
  BEGIN 
    DELETE FROM htmlCache WHERE quizId = old.quizId or quizId = new.quizId; 
  END; 

CREATE TRIGGER clear_htmlCache_delQ AFTER DELETE ON Question  
  BEGIN 
    DELETE FROM htmlCache WHERE quizId = old.quizId; 
  END; 

CREATE TRIGGER clear_htmlCache_insQ AFTER INSERT ON Question  
  BEGIN 
    DELETE FROM htmlCache WHERE quizId = new.quizId; 
  END; 
