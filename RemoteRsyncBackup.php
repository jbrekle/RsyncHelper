<?php
require_once 'SystemWrapper.php';

/**
 * the rsync class. 
 *
 * @author Jonas Brekle <jonas.brekle@gmail.com>
 */
class RemoteRsyncBackuper extends SystemWrapper{
    function __construct($log) {
        if($log instanceof SystemWrapper){
            $this->folder = $log->folder;
            $this->file = $log->file;

            $retArr = array();
            $retCode = 0;
            exec('whoami', $retArr, $retCode);
            if ($retArr[0] != 'root') {
                throw new Exception('need to be root');
            }
        } else {
            parent::__construct($log);
        }
    }

    function backup($FROM, $TO_FOLDER, $EXLUDELIST) {
        $retArr = array();
        $retCode = 0;
        
        #here the real syncing happens
        #use rsync with inplace to make it faster
        #print stats to a log file
        # safe links ignores links that point out of the syncing folder
        $retCode = $this->exec("rsync -ssh -a --delete --delete-excluded --exclude-from=$EXLUDELIST --stats -h --inplace $FROM $TO_FOLDER ");

        $this->log("Finished rsync backup from $FROM");

        if ($retCode == 0 || $retCode == 23) {
            $this->log("rsync: success");
        } else {
            $this->log("error (return code of rsync is $retCode) ");
            return false;
        }

        $this->log("Sync of $FROM completed successfully");
        return true;
    }

}

