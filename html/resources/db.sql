CREATE TABLE todolist_items(
    id          INTEGER PRIMARY KEY,
    todoitem    TEXT    NOT NULL,
    ip          TEXT,
    date        TEXT
);

CREATE TABLE listorder(
    position    INTEGER PRIMARY KEY,
    item_id     INTEGER NOT NULL,
    FOREIGN KEY (item_id) REFERENCES todolist_items(id)
);

INSERT INTO todolist_items VALUES (0, "Sample item #1", "127.0.0.1", "1970-01-01 00:00:00");
INSERT INTO todolist_items VALUES (1, "Sample item Two", "127.0.0.1", "1970-01-01 00:00:01");
INSERT INTO listorder VALUES (0,0), (1,1);