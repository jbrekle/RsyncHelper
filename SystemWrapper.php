<?php
/**
 * the base class for a all backup classes. handles interaction with the console. linux only
 *
 * @author Jonas Brekle <jonas.brekle@gmail.com>
 */
class SystemWrapper {
    public $folder = "";
    public $file = "";
    public $logStr = "";

    function __construct($folder) {
        $this->folder = $folder;
        $this->file = $folder . date('Y-m-d_H:i:s') . '.log';

        $retArr = array();
        $retCode = 0;
        exec('whoami', $retArr, $retCode);
        if ($retArr[0] != 'root') {
            throw new Exception('need to be root');
        }
    }

    function log($str) {
        $str .= "\n";
        echo $str;
        $this->logStr .= $str;
        file_put_contents($this->file, $str, FILE_APPEND);
    }
    
    function notify($str){
        $this->log($str);
        exec('notify-send "Backup: " "'.$str.'" -i /usr/share/pixmaps/gnome-schedule/gnome-schedule.svg -t 1000');
    }
    
    
    # my exec wrapper with logging
    function exec($str) {
        $retCode = 1;
        $str = "ionice -c3 nice -n 19 $str >> " . $this->file ." 2>&1" ;
        $this->log("executing cmd: \"".$str."\"");
        system($str, $retCode);
        $this->log("exit status: ".$retCode);
        $this->log("");
        return $retCode;
    }

    function cleanUp() {
        # write through
        $this->exec("sync");

        $this->log("clean up old log files");
        $this->exec("find " . $this->folder . " -name \"*.log\" -mtime +14 -exec rm {} \;");
    }
}

?>
