<?php

include_once("functions.php");
include_once("base.class.php");

define("VLM_PLAYER_ADMIN", 1);

class playersPending extends baseClass {
    var $idplayers_pending,
        $email,
        $password,
        $playername,
        $updated,        
        $seed;

    function playersPending($email = 0, $seed = 0, $row = null) {
        if (!is_null($email) && $seed !== 0) {
            $this->constructFromEmailSeed($email, $seed);
        } else if (!is_null($row) && is_array($row)) {
            $this->constructFromRow($row);
        }
    }

  function checkNonconformity($ws = null) 
  {
    $players = new players(0, null, $this);
    $players->checkNonconformity($ws);

    $this->error_string = $players->error_string;
    return $players->error_status;
  }

  function constructFromRow($row) 
  {
    $this->idplayers_pending = $row['idplayers_pending'];
    $this->playername = $row['playername'];
    $this->password = $row['password'];
    $this->email = $row['email'];
    $this->seed = $row['seed'];
    return True;
  }

  function constructFromEmailSeed($email, $seed) {
    $seed = intval($seed);
    return $this->constructFromQuery("email = '$email' AND seed = $seed");
  }

    function constructFromQuery($where) {
        $query= "SELECT * FROM players_pending WHERE $where";
        $result = $this->queryRead($query);
        if ($result && mysql_num_rows($result) === 1)  {
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
            return $this->constructFromRow($row);
        } else {
            $this->set_error("FAILED : Construct player_pending object from query");
            return False;
        }
    }

    function insert() {
        $query = sprintf("INSERT INTO `players_pending` SET `email`='%s', `password`='%s', `playername`='%s', `seed`=%d",
            $this->email,
            $this->password,
            $this->playername,
            $this->seed
            );
        return $this->queryWrite($query);
    }
    
    function delete() {
        //NB: on efface tous les pending avec le même email (quelque soit le seed)
        $query = sprintf("DELETE FROM players_pending WHERE email = '%s'", $this->email);
        return $this->queryWrite($query);
    }

    function mailValidationMessage() {
        $message  = getLocalizedString("Welcome into Virtual Loup de Mer !")."\n\n";
        $message .= getLocalizedString("You have requested to create an account on VLM.\nPlease, click on the link below or copy/paste it in your browser.")."\n";
        $message .= "http://".$_SERVER['HTTP_HOST']."/create_player.php?createplayer=validate&seed=".$this->seed."&emailid=".urlencode($this->email)."&jvlm=1\n";
        $message .= "\n".getLocalizedString("After activation, use your email address to connect.")."\n";
        $message .= getLocalizedString("Login id")." : ".$this->email."\n";
        return mailInformation($this->email, getLocalizedString("Validate your account"), $message);
    }

    function validate() {
        if (!$this->constructFromEmailSeed($this->email, $this->seed)) return False;
        $players = new players(0, null, $this);
        if ($players->error_status) {
            $this->set_error($players->error_string);
        }
        return !$this->error_status;
    }
    
    function create() {
        if (!$this->validate()) return False;
        $players = new players(0, null, $this);        
        $players->insert();
        if ($players->error_status) {
            $this->set_error($players->error_string);
            return !$this->error_status;
        }
        $this->delete();
        return !$this->error_status;
    } 
            
    //setters
    function setPassword($password) {
        $this->password = hash('sha256', $password);
    }
    
    function setSeed() {
        $this->seed = rand();
    }

    function dump() {
        $dump = sprintf("`email`='%s', `password`='%s', `playername`='%s', `seed`=%d",
            $this->email,
            $this->password,
            $this->playername,
            $this->seed
            );
        return $dump;
    }
}

class players extends baseClass {
    //DB attributes
    var $idplayers = null;
    var $email = null;
    var $password = null;
    var $playername = null;
    var $permissions = 0;
    var $updated = null;
    var $created = null; //FIXME this one seems not correct

    //computed attributes
    var $boatsitidlist = null;
    var $ownedboatidlist = null;
    var $recentlyboatsittedidlist = null;
          
    function players($idplayers = 0, $email = null, $pending = null, $row = null) {
        if ($idplayers !== 0) {
            $this->constructFromId($idplayers);
        } else if (!is_null($email)) {
            $this->constructFromEmail($email);
        } else if (!is_null($pending)) {
            $this->constructFromPending($pending);
        } else if (!is_null($row) && is_array($row)) {
            $this->constructFromRow($row);
        }
    }        
        
    function constructFromQuery($where) {
        $query= "SELECT * FROM players WHERE ".$where;
        $result = $this->queryRead($query);
        if ($result && mysql_num_rows($result) === 1)  {
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
            return $this->constructFromRow($row);
        } else {
            $this->set_error("FAILED : Construct player object from query");
            return False;
        }
    }

    function constructFromRow($row) {
        $this->idplayers = $row['idplayers'];
        $this->email = $row['email'];
        $this->password = $row['password'];
        $this->playername = $row['playername'];
        $this->permissions = $row['permissions'];
//        $this->description = $row['description'];
        //FIXME : et les autres attributs
        return True;
    }

    function constructFromId($id) {
        $id = intval($id);
        return $this->constructFromQuery("idplayers = $id");
    }

    function constructFromEmail($email) {
        return $this->constructFromQuery("email = '$email'");
    }

    function constructFromPending($pending) {
        $this->email = $pending->email;
        $this->playername = $pending->playername;
        $this->password = $pending->password;
    }

    function checkPassword($password) {
        return hash("sha256", $password) === $this->password;
    }

    function query_addupdate() {
        $query = sprintf("SET `email`='%s', `password`='%s', `playername`='%s', `permissions`=%d",
            $this->email,
            $this->password,
            $this->playername,
            $this->permissions
            );
        return $query;
    }
    
    function insert() {
        $query = sprintf("INSERT INTO `players` %s", $this->query_addupdate());
        if (!$this->queryWrite($query)) return False;
        $this->idplayers = mysql_insert_id($GLOBALS['masterdblink']);
        $this->logPlayerEvent("Player created.");
        return True;
    }

    function update() {
        $query = sprintf("UPDATE `players` %s WHERE `email` = '%s' AND `idplayers` = %d",
            $this->query_addupdate(),
            $this->email,
            intval($this->idplayers)
            );
        $this->logPlayerEvent("Player updated.");
        return $this->queryWrite($query);
    }

    function requestPasswordReset($WSRequest = false) 
    {
      if ($WSRequest)
      {
        $msg  = "You have requested a new password. Click on the link below to validate.\n";
        $msg .= "http://".$_SERVER['HTTP_HOST']."/jvlm?PwdResetKey=".urlencode($this->email).'|'.urlencode($this->password)."\n";
      }
      else
      {
        $msg  = "You have requested a new password. Click on the link below to validate.\n";
        $msg .= "http://".$_SERVER['HTTP_HOST']."/reset_password.php?resetpassword=validated&hashpassword=".urlencode($this->password)."&emailid=".urlencode($this->email)."\n";
      }
      $this->mailInformation("Password reset requested.", $msg);
      $this->logPlayerEvent("Password reset requested.");
    }        

    function modifyPassword($password) {
        $this->setPassword($password);
        if ($this->update()) {
            $this->logPlayerEvent("Password modified");
            $this->mailInformation("Your password has been updated");
            return True;
        } 
        return False;
    }

    function mailInformation($title, $message = null) {
        //wrapper
        return mailInformation($this->email, $title, $message);
    }


  function checkNonconformity($ws = null) 
  {
    if ($ws)
    {
      if (!isset($ws->answer['request']))
      {
        $ws->answer = [];
        $ws->answer['request'] = new stdClass();
      }
      
      $status = &$ws->answer['request'];
      $status->MailOK=true;
      $status->PlayerNameOK=true;
      $status->PasswordOK=true;
      $status->ErrorCode=null;
    }
    
    $pattern = "/^([\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+\.)*[\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+@((((([a-z0-9]{1}[a-z0-9\-]{0,62}[a-z0-9]{1})|[a-z])\.)+[a-z]{2,6})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?)$/i";
    if (preg_match($pattern, $this->email) < 1) {
      $this->set_error("Your email address doesn't seem to be valid");
      if ($ws)
      {
        $status->MailOK=false;
        $status->ErrorCode='NEWPLAYER01';
      }
      return False;
    }

    $pattern = "/^([\w\!\#_$\*\+\-\=\^\`{\|\}\~\.]+)*$/i";
    if (preg_match($pattern, $this->playername) < 1) {
      $this->set_error("Your playername contains invalid characters");
      if ($ws)
      {
        $status->PlayerNameOK=false;
        $status->ErrorCode='NEWPLAYER02';
      }
      
      return False;
    }

    
    if (strlen($this->playername) < 3 && $this->playername != "fm") 
    {
      $this->set_error(getLocalizedString("Your playername should have at least 3 characters."));
      if ($ws)
      {
        $status->PlayerNameOK=false;
        $status->ErrorCode='NEWPLAYER02';
      }

      return false;
    }

    if (strripos($this->playername, '--') !== False || strripos($this->playername, '  ') !== False) 
    {
      $this->set_error(getLocalizedString("Your playername should not be ascii art")); 
      if ($ws)
      {
        $status->PlayerNameOK=false;
        $status->ErrorCode='NEWPLAYER02';
      }
      return false;
    }

    $query = sprintf("SELECT * FROM players WHERE `email` = '%s'", $this->email);
    $result = $this->queryRead($query);        
    if (!($result && mysql_num_rows($result) === 0)) 
    {
      $this->set_error(getLocalizedString("Your email is already in use."));
      if ($ws)
      {
        $status->MailOK=false;
        $status->ErrorCode='NEWPLAYER01';
      }

      return false;
    }
    $query = sprintf("SELECT * FROM players WHERE UPPER(`playername`) = UPPER('%s')", $this->playername);
    $result = $this->queryRead($query);
    if (!($result && mysql_num_rows($result) === 0)) 
    {
      $this->set_error(getLocalizedString("Your playername is already in use."));
      if ($ws)
      {
        $status->PlayerNameOK=false;
        $status->ErrorCode='NEWPLAYER02';
      }
      return false;
    }

    return true ;
  }

    //Convenient bundle
    function logPlayerEventError($logmsg = null, $idusers = null) {
        if (!is_null($logmsg)) $this->set_error($logmsg);
        $this->logPlayerEvent($this->error_string, $idusers = null);
    }

    function logPlayerEvent($logmsg, $idusers = null) {
        logPlayerEvent($this->idplayers, $idusers, null, $logmsg);
    } 

    //setters
    function setPassword($password) {
        $this->password = hash('sha256', $password);
    }

    function unsetPref($key) {
    
        $query = sprintf("DELETE FROM `players_prefs` WHERE `idplayers` = %d AND `pref_name` = '%s';",
            intval($this->idplayers), $key);
        if ($this->queryWrite($query)) {
            $this->logPlayerEvent("Player prefs(".$key.') deleted');
            //$this->prefs[$key] = $value;
            return True;
        } else {
            return False;
        }
    }          

    function setPref($key, $val, $perm = null) {
        //FIXME : Should not be used except from playersPrefs class ?
        if (is_null($val)) return $this->unsetPref($key);
        $query = sprintf("REPLACE `players_prefs` SET `idplayers` = %d, `pref_name` = '%s', `pref_value` = '%s'",
            intval($this->idplayers), $key, mysql_real_escape_string($val) );
        if (!is_null($perm)) $query .= sprintf(", `permissions` = %d", $perm);
        if ($this->queryWrite($query)) {
            $this->logPlayerEvent("Player prefs(".$key.') updated');
            //$this->prefs[$key] = $value;
            return True;
        } else {
            return False;
        }
    }
    
    function setPrefPerm($key, $perm) {
        $query = sprintf("UPDATE `players_prefs` SET `permissions` = %d WHERE `idplayers` = %d AND `pref_name` = '%s'",
            $perm, intval($this->idplayers), $key);
        if ($this->queryWrite($query)) {
            $this->logPlayerEvent("Player prefs(".$key.') updated');
            //$this->prefs[$key] = $value;
            return True;
        } else {
            return False;
        }
    }
    
    //getters
    function getLang($deflang = 'en') {
        $val = $this->getPref("lang_ihm");
        if (is_null($val)) return $deflang;
        return $val['pref_value'];
    }
    
    function getPref($key) {
        $query = sprintf("SELECT `pref_name`, `pref_value`, `permissions` FROM `players_prefs` WHERE `idplayers` = %d AND `pref_name` = '%s'",
            intval($this->idplayers), $key);
        $result = $this->queryRead($query);
        if ($result && mysql_num_rows($result) === 1)  {
            $ret = mysql_fetch_array($result, MYSQL_ASSOC);
            $ret['permissions'] = intval($ret['permissions']);
            return $ret;
        } else {
            return null;
        }
    }
    
    function getPrefGroup($prefix = "") {
        $query = sprintf("SELECT `pref_name`, `pref_value`, `permissions` FROM `players_prefs` WHERE `idplayers` = %d AND `pref_name` LIKE '%s%%'",
            intval($this->idplayers), $prefix);
        $result = $this->queryRead($query);
        $grouplist = array();

        if ($result) {
            while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
                if (!is_null($row["pref_value"]) && $row["pref_value"] != "") $grouplist[$row["pref_name"]] = $row;
            }
        }
        return $grouplist;
    }
    
    function getManageableBoatIdList() {
        return array_unique(array_merge($this->getOwnedBoatIdList(), $this->getBoatsitIdList()));
    }
    
    function getOwnedBoatIdList() {
        if (!is_null($this->ownedboatidlist)) return $this->ownedboatidlist;
        $this->ownedboatidlist = $this->getBoatIdList("linktype =".PU_FLAG_OWNER);
        return $this->ownedboatidlist;
    }

    function hasMaxBoats() {
        return ( count($this->getOwnedBoatIdList()) >= MAX_BOATS_OWNED_PER_PLAYER );
    }

    function getBoatRecentlyBoatsittedIdList() {
        if (!is_null($this->recentlyboatsittedidlist)) return $this->recentlyboatsittedidlist;
        $boatidlist = Array();
        //FIXME : optimiser la requête ?
        $query  = "SELECT DISTINCT `idusers` FROM `user_action` WHERE `idplayers` = ".$this->idplayers." AND idusers NOT IN (SELECT DISTINCT idusers FROM playerstousers WHERE idplayers = ".$this->idplayers." AND linktype = ".PU_FLAG_OWNER." )";
        if ($res = $this->queryRead($query)) {
            while ($row = mysql_fetch_assoc($res)) {
                $boatidlist[$row['idusers']] = $row['idusers'];
                //FIXME : check result ?
            }
        }
        $this->recentlyboatsittedidlist = $boatidlist;
        return $this->recentlyboatsittedidlist;
    }

    function getBoatsitIdList() {
        if (!is_null($this->boatsitidlist)) return $this->boatsitidlist;
        $this->boatsitidlist = $this->getBoatIdList("linktype = ".PU_FLAG_BOATSIT);
        return $this->boatsitidlist;
    }

    function getBoatsitterList() {
        $ol = $this->getOwnedBoatIdList();
        if (count($ol) < 1) return Array();
        $olmysql = implode(',', $ol);
        $boatsitterlist = array();
        $query  = "SELECT DISTINCT `idplayers` FROM `playerstousers` WHERE `idusers` IN (".$olmysql.") AND linktype = ".PU_FLAG_BOATSIT;
        if ($res = $this->queryRead($query)) {
            while ($row = mysql_fetch_assoc($res)) {
                $boatsitterlist[$row['idplayers']] = $row['idplayers'];
                //FIXME : check result ?
            }
        }
//        $this->recentlyboatsittedidlist = $boatidlist;
        //FIXME : mettre en cache
        return $boatsitterlist;
    }

    function getDefaultBoat() {
        $boatlist = array_merge($this->getOwnedBoatIdList(), $this->getBoatsitIdList());
        //Fixme : should be configurable
        $default = 0;
        if (count($boatlist) > 0) {
            $default = $boatlist[0]; //fallback to firstboat
            foreach($boatlist as $idb) {
                $uo = getUserObject($idb);
                if ($uo->engaged > 0) $default = $idb; //betterchoice : racing boat
                if ($uo->engaged > 0 && $uo->getOwnerId() == $this->idplayers) return intval($idb); //found owned and racing boat
            }
        }
        return intval($default);
    }

    function getBoatIdList($linkfilter) {
        $boatidlist = Array();
        $query = "SELECT DISTINCT `idusers` FROM `playerstousers` WHERE `idplayers` = ".$this->idplayers." AND ".$linkfilter;
        if ($res = $this->queryRead($query)) {
            while ($row = mysql_fetch_assoc($res)) {
                $boatidlist[$row['idusers']] = $row['idusers'];
                //FIXME : check result ?
            }
        }
        return $boatidlist;
    }
    
    function getGrantedBoatList() {
        $boatlist = Array();
        $query = "SELECT PU.`idusers`, PU.`idplayers`, PU.`linktype` "
                ."FROM `playerstousers` as PU "
                ."WHERE PU.`idusers` IN ("
                ."SELECT DISTINCT PO.`idusers` FROM `playerstousers` as PO WHERE PO.`idplayers` = ".$this->idplayers." AND PO.linktype = ".PU_FLAG_OWNER
                .") ORDER BY PU.`idusers`, PU.`linktype`, PU.`idplayers`";
        if ($res = $this->queryRead($query)) {
            while ($row = mysql_fetch_assoc($res)) {
                $boatlist[] = $row;
                //FIXME : check result ?
            }
        }
        return $boatlist;
    }


    function getBoatCandidatesList() {
        $boatidlist = Array();
        $query = "SELECT DISTINCT MAIN.`idusers` FROM `users` as MAIN WHERE `email` LIKE '%".$this->email
                ."%' AND MAIN.`idusers` NOT IN (SELECT DISTINCT `idusers` FROM `playerstousers`)";
        if ($res = $this->queryRead($query)) {
            while ($row = mysql_fetch_assoc($res)) {
                $boatidlist[] = $row['idusers'];
                //FIXME : check result ?
            }
        }
        return $boatidlist;
    }
    
    //produit le Jid du player
    function getJid() {
        /* FIXME : Decision to make for good Jid as in <jid>@vlm_xmpp_host.v-l-m.org
         * - jid = idp is good because unicity and integrity but could be not very user friendly
         * - jid = "stripped" playername could be enough, but we need to check
         * => Using playername for now as a test (but unsafe and not for production)
         */
        return makeJid($this->playername);
    }
    
    function getFullJid() {
        return makeFullJid($this->playername);
    }

    //is ...
    function isAdmin() {
        return ($this->permissions & VLM_PLAYER_ADMIN);
    }
    
    function isOwner($idu) {
        return in_array($idu, $this->getOwnedBoatIdList());
    }

    //html renderers
    function htmlPlayername() {
        if (getPlayerId() === $this->idplayers) {
            return htmlPlayername($this->idplayers, $this->playername);
        } else {
            return htmlPlayername($this->idplayers, $this->playername, $this->getFullJid());
        }
    }

    function htmlIdplayersPlayername() {
        return htmlIdplayersPlayername($this->idplayers, $this->playername);
    }

    function htmlBoatOwnedList() {
        return $this->htmlBoatlist($this->getOwnedBoatIdList());
    }

    function htmlBoatManageableList() {
        return $this->htmlBoatlist($this->getManageableBoatIdList());
    }
    
    function htmlBoatlist($boatlist) {
        $ret = "<ul>";
        foreach ($boatlist as $id) {
            $user = getUserObject($id);
            if (is_null($user)) continue;
            $ret .= "<li>".$user->htmlIdusersUsernameLink()."&nbsp;";
            if (!in_array($user->idusers, $this->getOwnedBoatIdList())) $ret .= "(".getLocalizedString("as a boatsitter").")&nbsp;";
            $ret .= "-&nbsp;";

            if ($user->engaged > 0) {
                $raceobj = new races($user->engaged);
                $ret .= sprintf( getLocalizedString('boatengaged'), $raceobj->htmlRacenameLink(), $raceobj->htmlIdracesLink() );
            } else {
                $ret .= getLocalizedString('boatnotengaged');
            }
            $ret .= "</li>";
            
        }
        $ret .= "</ul>";
        return $ret;
    }

    function htmlBoatCandidatesList() {
        $listcandidate = $this->getBoatCandidatesList();
        $ret = "";
        
        if (count($listcandidate) > 0) {
            $ret .= "<h2>".getLocalizedString("These boats are maybe yours ?")."</h2>";
            $ret .= "<ul>\n";
            foreach ($listcandidate as $id) {
                $user = getUserObject($id);
                $ret .= "<li>".$user->htmlIdusersUsernameLink();
                $ret .= "&nbsp;<a href=\"attach_owner.php?boatpseudo=".$user->username."\">(".getLocalizedString("Attachment to this account").")</a>";
                $ret .= "</li>";
            }
            $ret .= "</ul><hr />";
        }
        return $ret;
    }

    function htmlBoatRecentlyBoatsittedList() {
        $list = $this->getBoatRecentlyBoatsittedIdList();
        return $this->htmlBoatlist($list);
    }

}

function makeJid($playername) {
    //FIXME : should normalize more
    return strtolower($playername);
}

function makeFullJid($playername) {
    return makeJid($playername).'@'.strtolower(VLM_XMPP_HOST);
}

//convenient htmlrenderes for inlining (see iterators)
function htmlPlayername($idplayers, $playername, $jid = null) {
    $ret  = "<a href=\"palmares.php?type=player&amp;idplayers=";
    $ret .= $idplayers;
    $ret .= "\">".$playername."</a>";
    if (!is_null($jid) && VLM_XMPP_ON) $ret .= "&nbsp;<a href=\"#\" onClick=\"converse.chats.open('".$jid."');\" title=\"".getLocalizedString("Click to chat")."\"><img src=\"/images/site/chaticon.png\" /></a>";
    return $ret;
}

function htmlIdplayersPlayername($idplayers, $playername) {
    $ret  = "<a href=\"palmares.php?type=player&amp;idplayers=";
    $ret .= $idplayers;
    $ret .= "\">(@".$idplayers.")&nbsp;".$playername."</a>";
    return $ret;
}

?>
