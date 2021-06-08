# ut-nexgen-stats-viewer-php7
 Version of NexgenStatsViewer105 compatible with php 7 and above

# Update 08/06/21
- Added support for custom gametypes


# How to upgrade
- Make a back up of the previous version if you wish.
- Replace getstats.php in your utstats main directory.


- You can only have a maximum of 5 lists with a total of 30 combined players.
- Lists are displayed in the order you specify.

# How to add standard lists(Top Rankings)
To add new gametypes you simply add the following at the top of the file after the php tags.

```php
//new NexgenPlayerList(HOST, DATABASE, USER, PASSWORD, DISPLAY_TITLE, GAMETYPE_NAME, TOTAL_PLAYERS, ALWAYS_SET_LAST_TO_FALSE);
new NexgenPlayerList("localhost", "utstats", "root", "", "Test Title", "Capture the Flag (Insta)", 5, false);
```



# How to add custom lists(kills, deaths, playtime...)

To add new custom lists you simply add the following at the top of the file after the php tags.

```php
//new NexgenPlayerList(HOST, DATABASE, USER, PASSWORD, DISPLAY_TITLE, ALWAYS_0, TOTAL_PLAYERS, TYPE); You can find valid types below.
new NexgenPlayerList("localhost", "utstats", "root", "", "Most Playtime (Hours)", 0, 10, "gametime");
```

# Valid custom types (Ignore text in brackets)
- gametime (Combined playtime in hours)
- frags
- kills
- deaths
- suicides
- teamkills
- flag_taken
- flag_dropped
- flag_return
- flag_capture
- flag_cover
- flag_seal
- flag_assist
- flag_kills
- flag_pickedup
- dom_cp (Domination Control Point Caps)
- ass_obj (Assault Objective Caps)
- spree_monster (Total Monster Kills)
- spree_god (Total Godlikes)
- pu_pads (Total Thigh Pads Pickups)
- pu_armour (Total Armour Pickups)
- pu_keg (Total Super Health Pickups)
- pu_invis (Total Invisibility Pickups)
- pu_belt (Total Shield Belt Pickups)
- pu_amp (Total UDamage Pickups)