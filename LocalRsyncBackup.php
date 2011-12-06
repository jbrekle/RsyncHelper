#!/usr/bin/php
<?php
require_once 'SystemWrapper.php';
class LocalRsyncBackup extends SystemWrapper {
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

    function mount($dev, $folder, $readMode, $mountMode) {
        $retArr = array();
        $retCode = 0;
        if ($mountMode == 'remount') {
            return ($this->exec("mount -o remount,$readMode $dev $folder") != 0);
        } else if ($mountMode == 'mount') {
            return ($this->exec("mount -o $readMode $dev $folder") != 0);
        } else if ($mountMode == 'umount') {
            return ($this->exec("umount $dev") != 0);
        }
        return false; //$mountMode unknown
    }

    function backup($FROM, $TO_FOLDER, $TO_DEVICE, $EXLUDELIST) {
        $retArr = array();
        $retCode = 0;

        $MOUNT_RO = true;

        #check for presence
        $retCode = $this->exec("mount | grep -q $TO_DEVICE");
        if ($retCode != 0) {
            $this->log("Error: target device not mounted");
            return false;
        }

        # Festplatte rw remounten falls gewünscht!
        if ($MOUNT_RO) {
            if ($this->mount($TO_DEVICE, $TO_FOLDER, "rw", "remount")) {
                $this->log("Error: Could not remount $TO_DEVICE readwrite");
                return false;
            } else
                $this->log("remounted $TO_DEVICE as readwrite");
        }

        #here the real syncing happens
        #use rsync with inplace to make it faster
        #print stats to a log file
        # safe links ignores links that point out of the syncing folder
        $retCode = $this->exec("rsync -a --delete --delete-excluded --exclude-from=$EXLUDELIST --stats -h --inplace $FROM $TO_FOLDER ");

        $this->log("Finished rsync backup from $FROM");

        if ($retCode == 0 || $retCode == 23) {
            $this->log("rsync: success");
        } else {
            $this->log("error (return code of rsync is $retCode) ");
            return false;
        }

        # write through
        $this->exec("sync");

        if ($MOUNT_RO) {
            if ($this->mount($TO_DEVICE, $TO_FOLDER, "ro", "remount")) {
                $this->log("Error: Could not remount $TO_DEVICE readonly");
                return false;
            } else {
                $this->log("remounted $TO_DEVICE as readonly");

                # check for max mount count
                $mountCount = $this->exec("tune2fs -l $TO_DEVICE | grep \"^Mount count\" | awk 'BEGIN { FS = \"              \" } ;{exit $2}' ");
                $this->log("$TO_DEVICE has been mounted $mountCount times");

                $maxMountCount = $this->exec("tune2fs -l $TO_DEVICE | grep \"^Maximum mount count\" |  awk 'BEGIN { FS = \"      \" } ;{exit $2}'");
                $this->log("maximum is $maxMountCount");
                if ($mountCount >= $maxMountCount - 1) {
                    $this->log("$TO_DEVICE needs to be checked (has been mounted too often)");
                    $this->mount($TO_DEVICE, $TO_FOLDER, "", "umount");
                    $retCode = $this->exec("/sbin/e2fsck -p $TO_DEVICE", $retArr, $retCode);
                    if ($retCode != 0) {
                        $this->log("could not e2fsck $TO_DEVICE (returned $retCode)");
                        return false;
                    }
                    $this->mount($TO_DEVICE, $TO_FOLDER, "ro", "mount");
                    $this->log("$TO_DEVICE has been checked");
                }
            }
        } 

        $this->log("Sync of $FROM completed successfully");
        return true;
    }
}
?>
