<?php
class Updater {
    public $folderStorage = __DIR__.'/storage/update/';

    public function __construct() {
        if(!is_dir($this->folderStorage)) {
            mkdir($this->folderStorage, 0777, true);
        }
    }

    public function check() {
        print "\n";
        print "\n";
        print "NetTeam Digital Signage\n";
        print "Güncellemeler kontrol ediliyor...\n";
        print "\n";
        
        $ntdsVersionZipFilename = $this->folderStorage.'ntds.zip';
        $ntdsVersionFilename = 'ntds.version';
        $ntdsVersion = '';
        if(file_exists($ntdsVersionFilename)) {
            $ntdsVersion = file_get_contents($ntdsVersionFilename);
        }
        $ntdsLatestVersion = file_get_contents('https://www.netteam.com.tr/netteam-digital-signage/ntds.version');
        if($ntdsLatestVersion != $ntdsVersion) {
            print "Sunucu sürümü: $ntdsVersion\n";
            print "Yeni bir sunucu sürümü bulundu: $ntdsLatestVersion\n";
            print "\n";
            print "Yeni sürüm indiriliyor...";
            
            if($this->downloadURL('https://www.netteam.com.tr/netteam-digital-signage/ntds.zip', $ntdsVersionZipFilename)) {
                print "\rİndirme tamamlandı.         \n";
                sleep(1);
                $this->unzip($ntdsVersionZipFilename, __DIR__.'/../');
            } else {
                print "\rİndirme sırasında hata oluştu!\n";
            }
        } else {
            print "Sunucunuz zaten güncel: $ntdsVersion\n";
        }
        print "\n";
        
        $ntdsUIVersionZipFilename = $this->folderStorage.'ntds-ui.zip';
        $ntdsUIVersionFilename = $this->folderStorage.'ntds-ui.version';
        $ntdsUIVersion = '';
        if(file_exists($ntdsUIVersionFilename)) {
            $ntdsUIVersion = file_get_contents($ntdsUIVersionFilename);
        }
        
        $ntdsUILatestVersion = file_get_contents('https://www.netteam.com.tr/netteam-digital-signage/ntds-ui.version');
        if($ntdsUILatestVersion != $ntdsUIVersion) {
            print "UI sürümü: $ntdsUIVersion\n";
            print "Yeni bir UI sürümü bulundu: $ntdsUILatestVersion\n";
            print "\n";
            print "Yeni sürüm indiriliyor...";
            
            if($this->downloadURL('https://www.netteam.com.tr/netteam-digital-signage/ntds-ui.zip', $ntdsUIVersionZipFilename)) {
                file_put_contents($ntdsUIVersionFilename, $ntdsUILatestVersion);
                print "\rİndirme tamamlandı.         \n";
                //sleep(1);
                //$this->unzip($ntdsUIVersionZipFilename, $this->folderStorage);
            } else {
                print "\rİndirme sırasında hata oluştu!\n";
            }
        } else {
            print "UI sürümünüz zaten güncel: $ntdsUIVersion\n";
        }
        print "\n";
        print "\n";
    }

    public function downloadURL($url, $filename) {
        if(!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        $tmpFilename = $filename.".tmp";
        if(file_exists($tmpFilename)) {
            unlink($tmpFilename);
        }
        $success = file_put_contents($tmpFilename, file_get_contents($url));
        if($success) {
            if(file_exists($filename)) {
                unlink($filename);
            }
            rename($tmpFilename, $filename);
            return true;
        } else {
            unlink($tmpFilename);
            return false;
        }
    }

    public function exec($command) {
        print "============================================================================================\n";
        print "Komut çalıştırılıyor: $command\n";
        print "--------------------------------------------------------------------------------------------\n";
        exec($command, $output, $retval);
        foreach ($output as $line) {
            print $line."\n";
        }
        print "============================================================================================\n";
        return $retval;
    }

    public function unzip($filename, $targetFolder) {
        print "============================================================================================\n";
        print "ZIP dosyası açılıyor\n";
        print "Kaynak\t: $filename\n";
        print "Hedef\t: $targetFolder\n";
        print "--------------------------------------------------------------------------------------------\n";
        $success = false;
        $zip = new ZipArchive;
        if ($zip->open($filename) === TRUE) {
            $zip->extractTo($targetFolder);
            $zip->close();
            $success = true;
        }
        if($success) {
            print "ZIP dosyası açıldı ve çıkartıldı.\n";
        } else {
            print "ZIP dosyası açılamadı.\n";
        }
        print "============================================================================================\n";
    }
}

$updater = new Updater();
$updater->check();
