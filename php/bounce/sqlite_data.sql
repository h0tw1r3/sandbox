BEGIN TRANSACTION;
INSERT INTO "bounce_items" (url, name) VALUES ('http://google.com/profiles/h0tw1r3','Jeffrey Clark''s Google Profile');
INSERT INTO "bounce_items" (url, name) VALUES ('http://zaplabs.com/','Zaplabs Website!');
COMMIT;
