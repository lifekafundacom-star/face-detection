<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

abstract class BaseRepository
{
    protected PDO $db;

    public function __construct()
    {
        $config = require __DIR__ . '/../Config/config.php';
        $this->db = Database::connection($config['db']);
    }
}
