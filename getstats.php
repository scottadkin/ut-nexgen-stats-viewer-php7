<?php

/*
PHP7 compatible nexgen stats viewer created by Scott Adkin 07/06/2021
*/

new NexgenPlayerList("localhost", "utstats", "root", "", "Test Title", "Capture the Flag (Insta)", 5);
//new NexgenPlayerList("localhost", "database", "user", "password", "Another Title that's boring", "Tournament Deathmatch (Insta)", 22);
//new NexgenPlayerList("localhost", "database", "user", "password", "Another Title that's boring", "Tournament Deathmatch (Insta)", 3);


const MAX_PLAYERS = 30;
const MAX_LISTS = 5;


$totalPlayers = 0;
$totalLists = 0;


class NexgenPlayerList{

    public function __construct($host, $database, $user, $password, $title, $gametype, $totalPlayers){

        $this->host = $host;
        $this->database = $database;
        $this->user = $user;
        $this->password = $password;
        $this->title = $title;
        $this->gametype = $gametype;
        $this->totalPlayers = $totalPlayers;

        $this->playerIds = [];
        $this->rankings = [];

        $this->players = [];

        $this->connect();
        $this->setGameId();

        if(isset($this->gametypeId)){

            $this->setRankingData();
            $this->setPlayerData();
            $this->print();
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

    private function print(){

        $find = ["\"","\\"];
        $replace = ["",""];

        $fixedTitle = str_replace($find, $replace, $this->title);

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

                $fixedPlayerName = str_replace($find, $replace, $playerDetails['name']);
            
                echo "addplayer \"".$fixedPlayerName."\" ".$fixedRank." ".$country." ".$icon."\r\n";
            }
        }
    }
}



?>