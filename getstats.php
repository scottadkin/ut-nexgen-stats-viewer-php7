<?php

/*
PHP7 compatible nexgen stats viewer created by Scott Adkin 07/06/2021
Added support for custom lists 08/06/2021
*/


//For the standard behaviour(top rankings) use the following syntax:
//new NexgenPlayerList(HOST, DATABASE, USER, PASSWORD, DISPLAY_TITLE, GAMETYPE_NAME, TOTAL_PLAYERS, ALWAYS_SET_LAST_TO_FALSE);
new NexgenPlayerList("localhost", "utstats", "root", "", "Test Title", "Capture the Flag (Insta)", 5, false);



//For custom lists like kills,deaths,playtime... use the following syntax.
//new NexgenPlayerList(HOST, DATABASE, USER, PASSWORD, DISPLAY_TITLE, ALWAYS_0, TOTAL_PLAYERS, TYPE); You can find valid types in readme.md

new NexgenPlayerList("localhost", "utstats", "root", "", "Most Playtime (Hours)", 0, 10, "gametime");
new NexgenPlayerList("localhost", "utstats", "root", "", "Most Kills", 0, 10, "kills");
new NexgenPlayerList("localhost", "utstats", "root", "", "Most Monster Kills", 0, 5, "spree_monster");


const MAX_PLAYERS = 30;
const MAX_LISTS = 5;


$totalPlayers = 0;
$totalLists = 0;


class NexgenPlayerList{

    public function __construct($host, $database, $user, $password, $title, $gametype, $totalPlayers, $specialType){

        $this->host = $host;
        $this->database = $database;
        $this->user = $user;
        $this->password = $password;
        $this->title = $title;
        $this->gametype = $gametype;
        $this->totalPlayers = $totalPlayers;

        $this->playerIds = [];
        $this->rankings = [];

        $this->specialData = [];

        $this->players = [];

        $this->connect();

        if($this->gametype !== 0){

            $this->setGameId();

            if(isset($this->gametypeId)){


                $this->setRankingData();
                $this->setPlayerData();
                $this->print();
            }
        }else{

            //$this->testDisplay();

            $this->setSpecialData($specialType, $totalPlayers);
            $this->setPlayerData();
            $this->printSpecial();
        }
    }

    private function setSpecialData($type, $limit){

        $type = strtolower($type);

        $validTypes = [
            "gametime",
            "frags",
            "kills",
            "deaths",
            "suicides",
            "teamkills",
            "flag_taken",
            "flag_dropped",
            "flag_return",
            "flag_capture",
            "flag_cover",
            "flag_seal",
            "flag_assist",
            "flag_kills",
            "flag_pickedup",
            "dom_cp",
            "ass_obj",
            "spree_monster",
            "spree_god",
            "pu_pads",
            "pu_armour",
            "pu_keg",
            "pu_invis",
            "pu_belt",
            "pu_amp"
        ];

        $index = array_search($type, $validTypes);

        if($index !== false){

            $query = "SELECT `pid`,SUM(`".$validTypes[$index]."`) as `total_value` FROM `uts_player` GROUP BY `pid` ORDER BY `total_value` DESC LIMIT ?";

            if($stmt = $this->db->prepare($query)){

                $stmt->bind_param("d", $limit);

                $stmt->execute();

                $result = $stmt->get_result();

                $this->specialData = [];

                while($d = $result->fetch_assoc()){

                    if($type === "gametime"){
                        $d["total_value"] = sprintf("%.2f", $d["total_value"] / (60 * 60));
                    }

                    $this->playerIds[] = $d['pid'];

                   // print_r($d);
                   $this->specialData[] = $d;
                }


                $stmt->close();


            }else{
                echo $this->db->error;
            }

        }else{

            die($type." is not a supported type");
        }
    }

    private function printSpecial(){

        echo "beginlist \"".$this->cleanString($this->title)."\"\r\n";

        for($i = 0; $i < count($this->specialData); $i++){

            $d = $this->specialData[$i];

            $player = $this->players[$d['pid']];

            if(isset($player)){

                $country = $player['country'];

                if($country === ""){
                    $country = "xx";
                }

                echo "addplayer \"".$player['name']."\" ".$d['total_value']." ".$country." nc\r\n";
           }
        }
    }


    private function connect(){

        $this->db = new mysqli($this->host, $this->user, $this->password, $this->database);

        if($this->db->connect_errno){
            echo "Failed to connect to database<br/>";
            echo $this->db->connect_error;
            die();
        }
    }

    private function setGameId(){



        $query = "SELECT `id` FROM `uts_games` WHERE `name`=? LIMIT 1";

        if($stmt = $this->db->prepare($query)){
            $stmt->bind_param("s", $this->gametype);
            $stmt->execute();
            $result = $stmt->get_result();

            $d = $result->fetch_assoc();
    
            if(isset($d['id'])){
                $this->gametypeId = $d['id'];
            }

            $stmt->close();

        }else{
            echo $this->db->error;
        }
        
    }

    private function setRankingData(){

        $query = "SELECT `pid`,`rank`,`prevrank` FROM uts_rank WHERE `gid`=? ORDER BY `rank` DESC LIMIT ?";

        if($stmt = $this->db->prepare($query)){
            $stmt->bind_param("dd", $this->gametypeId, $this->totalPlayers);
            $stmt->execute();
            $result = $stmt->get_result();

            while($d = $result->fetch_assoc()){

                $this->rankings[] = $d;
                $this->playerIds[] = $d['pid'];
            }

            $stmt->close();

        }else{
            echo $this->db->error;
        }
    }

    private function setPlayerData(){

        $qMarks = "";
        $paramLetters = "";

        $totalPlayers = count($this->playerIds);

        if($totalPlayers === 0) return;



        for($i = 0; $i < $totalPlayers; $i++){

            $qMarks.= "?";

            $paramLetters.="s";

            if($i < $totalPlayers - 1){
                $qMarks.=",\n";
            }
        }

        $query = "SELECT `id`,`name`,`country` FROM uts_pinfo WHERE `id` in(".$qMarks.")";

        if($stmt = $this->db->prepare($query)){

            $stmt->bind_param($paramLetters, ...$this->playerIds);
            $stmt->execute();
            $result = $stmt->get_result();

            while($d = $result->fetch_assoc()){

                $this->players[$d['id']] = $d;
            }

            $stmt->close();

          

        }else{

            echo $this->db->error;
        }

    }


    private function cleanString($input){

        $find = ["\"","\\"];
        $replace = ["",""];

        return str_replace($find, $replace, $input);
    }

    private function print(){


        $fixedTitle = $this->cleanString($this->title);

        echo "beginlist \"".$fixedTitle."\"\r\n";

        for($i = 0; $i < count($this->rankings); $i++){

            $r = $this->rankings[$i];

            
            $fixedRank = sprintf("%.2f", $r["rank"]);

            if($r['prevrank'] > $r['rank']){
                $icon = "down";
            }else if($r['prevrank'] < $r['rank']){
                $icon = "up";
            }else{
                $icon = "nc";
            }

            $playerDetails = $this->players[$r['pid']];

            $country = "xx";

            if($playerDetails['country'] !== ""){
                $country = $playerDetails['country'];
            }

            if(isset($playerDetails)){

                $fixedPlayerName = $this->cleanString($playerDetails['name']);
            
                echo "addplayer \"".$fixedPlayerName."\" ".$fixedRank." ".$country." ".$icon."\r\n";
            }
        }
    }
}



?>