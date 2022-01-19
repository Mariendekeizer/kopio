<?php

declare(strict_types=1);
namespace App\Backup;

use App\Exception\ConnectionErrorException;
use App\Exception\BackupFailedException;

class MySqlBackup extends AbstractBackup
{
    public function __construct(string $name, string $type, array $source, string $destination, string $retention)
    {
        parent::__construct($name, $type, $source, $destination, $retention);
        $this->keysToCheck = ['host', 'port', 'username', 'password', 'database'];
    }

    public function checkSource(): void
    {
        try {
            $conn = new \PDO("mysql:host=" . $this->source['host'] . ";port=" . $this->source['port'] . ";dbname=".$this->source['database'], $this->source['username'], $this->source['password']);
        } catch(\PDOException $e) {

            throw new ConnectionErrorException($e->getMessage());
        }
    }

    public function executeBackup(): void
    {
        $command = "mysqldump --user=" . $this->source['username'] . " --password=" . $this->source['password'] . " --host=" . $this->source['host'] . " --port=" . $this->source['port'] . " " . $this->source['database'] . " > " . $this->destination . DIRECTORY_SEPARATOR .  date("YmdHis") . '.sql';
            
        system($command, $return);

        if ($return != 0) {
            throw new BackupFailedException('Failed to create MySql backup for database: ' . $this->source['database'] . ' on host: ' . $this->source['host'] . ' with return code ' . $return);
        }
    }
}