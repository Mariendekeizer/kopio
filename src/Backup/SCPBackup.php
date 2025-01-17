<?php

declare(strict_types=1);
namespace App\Backup;

use App\Exception\BackupFailedException;
use Symfony\Component\Filesystem\Filesystem;

class SCPBackup extends AbstractBackup
{
    private $tmpLocation;
    private $filesystem;

    public function __construct(string $name, string $type, array $source, string $destination, string $retention)
    {
        parent::__construct($name, $type, $source, $destination, $retention);

        $this->filesystem = new Filesystem();
        $this->keysToCheck = ['username', 'host', 'private_key', 'locations', 'tmp_location'];
        $this->tmpLocation = rtrim($this->source['tmp_location'], '/') . DIRECTORY_SEPARATOR . $this->generateRandomString() . DIRECTORY_SEPARATOR;   
    }

    public function checkSource(): void {}

    public function executeTmpBackup(): void
    {
        foreach ($this->source['locations'] as $key => $src) {
            $this->filesystem->mkdir($this->tmpLocation . $key);
            $command = 'scp -r -i ' . $this->source['private_key'] . " " . $this->source['username'] . '@' . $this->source['host'] . ':' . rtrim($src, "/") . DIRECTORY_SEPARATOR . ' '  .  $this->tmpLocation . $key;
            system($command, $return);
    
            if ($return != 0) {
                throw new BackupFailedException('Failed to create local backup for source: ' . $src . 'width error code ' . $return);
            }
        }
    }

    public function executePermanentBackup(): void
    {
        $currentDir = getcwd();
        chdir($this->tmpLocation);

        $command = "tar -cvf " . $this->destination . DIRECTORY_SEPARATOR . date("YmdHis") . ".tar *";
        system($command, $return);

        chdir($currentDir);

        if ($return != 0) {
            throw new BackupFailedException('Failed to copy files from tmp directory: ' . $this->source['tmp_location'] . 'width error code ' . $return);
        }
    }

    public function deleteTmpLocation()
    {
        $this->filesystem->remove($this->tmpLocation);
    }

    public function executeBackup(): void
    {
        $this->executeTmpBackup();
        $this->executePermanentBackup();
        $this->deleteTmpLocation();
    }
}