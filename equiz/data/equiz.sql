DROP TABLE IF EXISTS Tags;
CREATE TABLE Tags (
	tag TEXT PRIMARY KEY
);
INSERT INTO Tags values("test");

DROP TABLE IF EXISTS Quiz;
CREATE TABLE Quiz (
	id INTEGER PRIMARY KEY,
	title TEXT NOT NULL,
	duetime TIMESTAMP,
	tag TEXT REFERENCES Tags (tag) ON DELETE SET NULL,
	descrip TEXT
);
INSERT INTO Quiz
 (id, title, duetime, tag, descrip)
 VALUES
 (NULL, 'test', datetime(CURRENT_TIMESTAMP, 'localtime'), "test", "description of this Quiz");


insert into quiz_ select id,title,duetime,tag,descrip from quiz;
alter table quiz_ rename to Quiz;

DROP TABLE IF EXISTS Question;
CREATE TABLE Question (
	id INTEGER PRIMARY KEY,
	quizId INTEGER REFERENCES Quiz (id) ON DELETE SET NULL,
	seq INTEGER NOT NULL,
	type INTEGER NOT NULL,
	body TEXT NOT NULL,
	options TEXT NOT NULL,
	answers TEXT NOT NULL,
	mtime TIMESTAMP DEFAULT (datetime(CURRENT_TIMESTAMP, 'localtime')),
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
	UNIQUE (email)
);
INSERT INTO PartInfo (id, name, email)
 VALUES (NULL, "Leo Wang", "leo.wang@alcatel-lucent.com");


DROP TABLE IF EXISTS Submission;
CREATE TABLE Submission (
	quizId INTEGER NOT NULL REFERENCES Quiz (id) ON DELETE CASCADE,
	pId INTEGER NOT NULL REFERENCES PartInfo (id) ON DELETE CASCADE,
	subtime TIMESTAMP NOT NULL DEFAULT (datetime(CURRENT_TIMESTAMP, 'localtime')),
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

DROP TABLE IF EXISTS SubInfo;
CREATE TABLE SubInfo (
	tag TEXT NOT NULL REFERENCES Tags (tag) ON DELETE CASCADE,
	pId INTEGER NOT NULL REFERENCES PartInfo (id) ON DELETE CASCADE,
	PRIMARY KEY (tag, pId)
);

DROP TABLE IF EXISTS RegInfo;
CREATE TABLE RegInfo (
        token CHARACTER(18),
        name TEXT,
        email TEXT NOT NULL,
        tags TEXT,
        op INTEGER NOT NULL DEFAULT 1,
	rtime TIMESTAMP NOT NULL DEFAULT (datetime(CURRENT_TIMESTAMP, 'localtime')),
        PRIMARY KEY (token, email)
);


CREATE TRIGGER update_q_mtime UPDATE ON Question  
  BEGIN 
    UPDATE Question SET mtime = datetime(CURRENT_TIMESTAMP, 'localtime') WHERE id = old.id; 
  END; 

