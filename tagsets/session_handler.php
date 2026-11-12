<?php
// part of orsee. see orsee.org

class orsee_session_handler implements SessionHandlerInterface {
    public function open(string $aSavaPath, string $aSessionName): bool {
        return orsee_session_open($aSavaPath,$aSessionName);
    }

    public function close(): bool {
        return orsee_session_close();
    }

    public function read(string $aKey): string {
        return (string)orsee_session_read($aKey);
    }

    public function write(string $aKey, string $aVal): bool {
        return orsee_session_write($aKey,$aVal);
    }

    public function destroy(string $aKey): bool {
        return orsee_session_destroy($aKey);
    }

    public function gc(int $aMaxLifeTime): int {
        return orsee_session_gc($aMaxLifeTime);
    }
}

function orsee_session_register_handler() {
    session_set_save_handler(new orsee_session_handler(), true);
}

function orsee_session_open($aSavaPath, $aSessionName) {
       global $aTime;
       orsee_session_gc( $aTime );
       return true;
}

function orsee_session_close() {
       return true;
}

function orsee_session_read( $aKey ) {
       $query = "SELECT DataValue FROM ".table('http_sessions')." WHERE SessionID=:aKey";
       $pars=array(':aKey'=>$aKey);
       $result = or_query($query,$pars);
       if(pdo_num_rows($result) == 1) {
             $r = pdo_fetch_assoc($result);
             return $r['DataValue'];
       } else {
             $query = "INSERT INTO ".table('http_sessions')." (SessionID, LastUpdated, DataValue)
                       VALUES (:aKey, NOW(), '')";
             or_query($query,$pars);
             return "";
       }
}

function orsee_session_write( $aKey, $aVal ) {
    site__database_config();
    $pars=array(':aKey'=>$aKey, ':aVal'=>$aVal);
    $query = "UPDATE ".table('http_sessions')." SET DataValue = :aVal, LastUpdated = NOW() WHERE SessionID = :aKey";
    or_query($query,$pars);
    return true;
}

function orsee_session_destroy( $aKey ) {
    site__database_config();
    $pars=array(':aKey'=>$aKey);
    $query = "DELETE FROM ".table('http_sessions')." WHERE SessionID = :aKey";
    or_query($query,$pars);
    return true;
}

function orsee_session_gc( $aMaxLifeTime ) {
    site__database_config();
    if (!isset($aMaxLifeTime) || (!$aMaxLifeTime)) $aMaxLifeTime=60*60;
    $pars=array(':aMaxLifeTime'=>$aMaxLifeTime);
    $query = "DELETE FROM ".table('http_sessions')." WHERE UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(LastUpdated) > :aMaxLifeTime";
    $done=or_query($query,$pars);
    return pdo_num_rows($done);
}


?>
