CREATE TABLE [bounce_items] (
    [id] INTEGER PRIMARY KEY, 
    [disabled] INTEGER DEFAULT 0,
    [url] VARCHAR(1024) NOT NULL, 
    [created_at] INTEGER DEFAULT (strftime('%s','now')), 
    [name] VARCHAR(128));
CREATE TABLE [bounce_clicks] (
    [bounce_item_id] INTEGER NOT NULL REFERENCES [bounce_items](id) ON DELETE CASCADE ON UPDATE CASCADE,
    [bounce_clicker_id] INTEGER NOT NULL REFERENCES [bounce_clickers](id) ON DELETE CASCADE ON UPDATE CASCADE,
    [clicked_at] DATETIME DEFAULT (strftime('%s','now')),
    [ip_address] INTEGER NOT NULL, 
    [referer] VARCHAR(1024), 
    [user_agent] VARCHAR(256));
CREATE TABLE [bounce_clickers] (
    [id] INTEGER PRIMARY KEY, 
    [user_agent] CHAR(256), 
    [created_at] DATETIME DEFAULT (strftime('%s','now')));
CREATE TRIGGER [fk_bounce_clicks_bounce_items_del1] 
    BEFORE DELETE ON [bounce_items] WHEN (old.[id] IN (
        SELECT [bounce_item_id] FROM [bounce_clicks] GROUP BY [bounce_item_id]
    ))
    BEGIN
        DELETE FROM [bounce_clicks] WHERE [bounce_item_id] = old.[id];
    END;
CREATE TRIGGER [fk_bounce_clicks_bounce_items_upd1]
    BEFORE UPDATE ON [bounce_items] WHEN (old.[id] IN (
        SELECT [bounce_item_id] FROM [bounce_clicks] GROUP BY [bounce_item_id]
    ))
    BEGIN
        UPDATE [bounce_clicks] SET [bounce_item_id] = new.[id] WHERE [bounce_item_id] = old.[id];
    END;
