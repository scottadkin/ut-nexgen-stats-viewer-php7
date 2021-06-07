# ut-nexgen-stats-viewer-php7
 Version of NexgenStatsViewer105 compatible with php 7 and above


# How to upgrade
- Make a back up of the previous version if you wish.
- Replace getstats.php in your utstats main directory.

# How to add gametypes
To add new gametypes you simply add the following at the top of the file after the php tags.

- You can only have a maximum of 5 lists with a total of 30 combined players.

```php
new NexgenPlayerList("localhost", "utstats", "root", "", "Test Title", "Capture the Flag (Insta)", 5);
//new NexgenPlayerList("HOST", "DATABASE NAME", "MYSQL USER", "MYSQL PASSWORD", "DISPLAY TITLE", "GAMETYPE NAME", "TOTAL players");
```

