<?php
namespace Tools;

require_once(__DIR__ . '/MysqlDumper.php');
require_once(__DIR__ . '/WhereStringBuilder.php');

class MysqlCloneTool {
    const TOO_MANY_PROCS = 50;

    const MYSQL = 'mysql';

    const SQL_SEP = ";\n";

    /**
     * @var mysqli $Db
     */
    protected static $Db;

    // WAIT: 1000 minutes (default)
    /**
     * @var int $Timeout
     */
    protected static $Timeout = 1000;

    /**
     * @var bool $dumpall
     */
    protected $dumpall = false;

    /**
     * @var bool $match
     */
    protected $match = false;

    /**
     * @var int $retries
     */
    protected $retries = 1;

    /**
     * @var int $myIsamDumpRetry
     */
    protected $myIsamDumpRetry;

    /**
     * @var int $percent
     */
    protected $percent = 100;

    /**
     * @var int $tableCount
     */
    protected $tableCount = 0;

    /**
     * @var int $procs
     */
    protected $procs = 3;

   /**
     * @var string $backupDbSchema
     */
    protected $backupDbSchema;

    /**
     * @var string $updateDbSchema
     */
    protected $updateDbSchema;

    /**
     * @var string $cloneDbSchema
     */
    protected $cloneDbSchema;

    /**
     * @var string $userLogFile
     */
    protected $userLogFile;

    /**
     * @var WhereStringBuilder $whereStringBuilder
     */
    protected $whereStringBuilder;

    /**
     * @var array $tables
     */
    protected $tables = array();

    /**
     * @var array $tables
     */
    protected $tableEngines = array();

    /**
     * @var array $isamTables
     */
    protected $isamTables = array();

    /**
     * @var array $innodbTables
     */
    protected $innodbTables = array();

    /**
     * @var array $tableLimits
     */
    protected $tableLimits = array();

    /**
     * @var array $isamTablesRetryMap
     */
    protected $isamTablesRetryMap = array();

    /**
     * @var array $innodbTablesRetryMap
     */
    protected $innodbTablesRetryMap = array();

    /**
     * @var array $mysqlDumpers
     */
    protected $mysqlDumpers = array();

    /**
     * @param array $options - the options for member variables
     */
    public function __construct(array $options)
    {
        // @todo write signal handler allowing pause
        // @todo parse whereconf opt file
        if (!is_a(self::$Db, 'mysqli')) {
            self::$Db = new mysqli();
        }
        self::$Db->connect();
        self::$Db->autocommit(false);
        $this->tables = array();
        $this->updateDbSchema = $options['update'];
        $this->cloneDbSchema = $options['clone'];
        $this->backupDbSchema = $options['backup'];
        if (!$this->updateDbSchema || !$this->cloneDbSchema) {
            throw new Exception("the 'clone' and 'update' options should both be set in 1st parameter array.");
        }
        if (null !== $options['whereconf']) {
            $whereConf = $options['whereconf'];
            if (!is_string($whereConf)) {
                throw new Exception("whereconf file path must be a valid filepath.");
            } elseif (!is_file($whereConf)) {
                throw new Exception("whereconf file path must be a valid filepath.");
            } else {
                if (null === $where = json_decode(file_get_contents($whereConf), true)) {
                    throw new Exception("Unable to parse json whereconf file.");
                }
                if (null !== $options['limit']) {
                    throw new Exception("whereconf and limit options are mutually exclusive.");
                }
                $this->whereStringBuilder = new WhereStringBuilder($where, self::$Db);
            }
        }
        if (null !== $options['dumpall']) {
            $this->dumpall = $options['dumpall'];
        }
        if (null !== $options['match']) {
            $this->match = $options['match'];
        }
        if (null !== $options['limit']) {
            $this->percent = intval($options['limit']);
        }
        if ($this->percent <= 0 || $this->percent > 100) {
            throw new Exception("limit should be an integer between 1 and 100.");
        }
        if (null !== $options['retries']) {
            $this->retries = intval($options['retries']);
        }
        if ($this->retries <= 0) {
            throw new Exception("retries should be a non-negative integer.");
        }
        if (null !== $options['forks']) {
            $this->procs = intval($options['forks']);
        }
        if ($this->procs <= 0 || $this->procs >= self::TOO_MANY_PROCS) {
            throw new Exception("forks should be a non-negative integer below " . (string) self::TOO_MANY_PROCS . ".");
        }
        if ($options['username']) {
            $this->userLogFile = '/home/' . $options['username'] . '/data/logs/' . 'mysql-clone-db.proc_err.log';
        } else {
            $this->userLogFile = '/tmp/mysql-db-clone.proc_err.log';
        }
        $this->assertDatabasesValid();
        // I want this to occur before any backup is made as well so the script dies if databases don't exist
        $this->assertDatabasesExist();
    }

    public function __destruct()
    {
        if (self::$Db->thread_id) {
            self::$Db->close();
        }
    }

    /**
     * @param int $to
     */
    public static function SetTimeout($to)
    {
        if (null === $to) {
            return;
        }
        $timeout = intval($to);
        if ($timeout <= 0) {
            throw new \Exception('Timeout must be a positive integer value in minutes.');
        }
        self::$Timeout = $timeout;
    }

    /**
     * @return array
     */
    public static function MysqlHasErrors() {
        $ret = array('code'=> 0, 'message' => '');
        if (self::$Db->connect_errno) {
            $ret['message'] =  Color::colorize('red', "Database connection failed: " . self::$Db->connect_error, true);
            $ret['code'] = self::$Db->connect_errno;
        } elseif (self::$Db->errno) {
            $ret['message'] =  Color::colorize('red', "Query failed: " . self::$Db->error, true);
            $ret['code'] = self::$Db->errno;
        }
        return $ret;
    }

    /**
     * Return true if backup successful.
     *
     * @return bool
     */
    public function backupUpdateDb()
    {
        if ($this->backupDbSchema) {
            $updateBackupName = $this->backupDbSchema;
        } else {
            $updateBackupName = getcwd() . '/' . $this->updateDbSchema . '_' . time() . '.' . self::MYSQL;
        }
        echo Color::colorize('light grey', "Backing up {$this->updateDbSchema} to {$updateBackupName}", true);
        $dumper = new MysqlDumper($updateBackupName, $this->updateDbSchema, null, null, $this->userLogFile);
        $execTimeBegin = microtime(true);
        $dumper->start();
        if ($dumper->wait()) {
            $taken = number_format((microtime(true) - $execTimeBegin) / 60, 3);
            echo Color::colorize('green', "Info: taken {$taken} minutes to complete backup.", true);
            echo Color::colorize('green', "Successful backup {$this->updateDbSchema} to {$updateBackupName}", true);
            return true;
        }
        echo Color::colorize('red', "Failure to backup {$this->updateDbSchema} to {$updateBackupName}.", true);
        return false;
    }

    /**
     * @throws Exception
     */
    public function executeDatabaseClone()
    {
        $execBeginTime = microtime(true);
        $this->runSqlGetDatabaseTables();
        $completedTables = array();
        $innodbTableCount = count($this->innodbTables);
        $isamTableCount = count($this->isamTables);
        $tableCount = $isamTableCount + $innodbTableCount;
        $completed = $tableCount > 0 ? false : true;
        $i = 0;
        $j = 0;
        $k = 0;
        $tableRetry = 0;
        while ($i < $tableCount) {
            if (microtime(true) - $execBeginTime > self::$Timeout * 60) {
                echo Color::colorize('red', "Taken over " . self::$Timeout . " minutes, quitting.", true);
                break;
            }
            if ($j < $innodbTableCount) {
                $this->doInnoDbDumpProcess($j, $i, $tableRetry);
            }
            foreach (MysqlDumper::CheckProcessesHaveExited() as $pidUtime => $proc) {
                list($pid, $utime) = explode(MysqlDumper::KEYSPLIT, $pidUtime);
                if (array_search($proc['table'], $completedTables) === false) {
                    echo Color::colorize('green', "mysqldump for " . $proc['table'] . " has finished.", true);
                    $completedTables []= $proc['table'];
                    ++$i;
                }
                unset($this->mysqlDumpers[$pid]);
            }
            if ($k < $isamTableCount && count($this->mysqlDumpers) < $this->procs) {
                $this->startDumpProcess(array_values($this->isamTables)[$k++], MysqlDumper::MYISAM);
            }
            foreach ($this->mysqlDumpers as $dumper) {
                if (0 === $this->checkRetryDumpProcess($dumper, $completedTables)) {
                    ++$i;
                }
            }
            if ($i === $tableCount) {
                $completed = true;
            }
        }
        if ($completed) {
            echo Color::colorize('yellow', "It took ");
            echo Color::colorize('red', number_format((microtime(true) - $execBeginTime) / 60, 5));
            echo Color::colorize('yellow', " minutes to clone the database.", true);
        } else {
            echo Color::colorize('red', "The clone did not finish before timing out.", true);
            if (count($this->mysqlDumpers) > 0) {
                current($this->mysqlDumpers)->exitWithProcessRetCode();
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function assertDatabasesExist()
    {
        self::$Db->autocommit(true);
        $result = self::$Db->query($this->getSqlSelectSchema($this->updateDbSchema));
        if (!$result->fetch_object()) {
            throw new \Exception("The schema to be updated does not exist in the database.");
        }
        $result = self::$Db->query($this->getSqlSelectSchema($this->cloneDbSchema));
        if (!$result->fetch_object()) {
            throw new \Exception("The schema to be cloned does not exist in the database.");
        }
        if ($this->backupDbSchema) {
            $result = self::$Db->query($this->getSqlSelectSchema($this->backupDbSchema));
            if (!$result->fetch_object()) {
                throw new \Exception("The backup schema provided does not exist in the database.");
            }
        }
        self::$Db->autocommit(false);
    }

    /**
     * @throws Exception
     */
    protected function assertDatabasesValid()
    {
        if ($this->updateDbSchema === $this->backupDbSchema) {
            throw new \Exception("Backup schema and update schema must be different.");
        }
        if ($this->updateDbSchema === $this->cloneDbSchema) {
            throw new \Exception("Clone schema and update schema must be different.");
        }
        if ($this->backupDbSchema === $this->cloneDbSchema) {
            throw new \Exception("Backup schema and clone schema must be different.");
        }
    }

    /**
     * Clean the tables member array if the match option is on.
     *
     * @throws Exception
     */
    protected function cleanTablesMember()
    {
        $lastTable = null;
        $lastIsamTable = null;
        $lastInnodbTable = null;
        $lastIsamTableOrderIndex = null;
        $lastInnodbTableOrderIndex = null;
        $missingFromCloneTables = array();
        foreach ($this->tables as $idx => $table) {
            $sql = $this->getSqlSelectCloneDbTable($table);
            $result = self::$Db->query($sql);
            $ok = self::$Db->commit();
            if (!$ok) {
                $ok = self::$Db->rollback();
                $status = self::MysqlHasErrors();
                echo $status['message'];
                exit($status['code']);
            }
            $lastTable = null;
            if ($row = $result->fetch_object()) {
                $engine = strtoupper($row->ENGINE);
                $this->tableEngines[$table] = $engine;
                if ($engine == "MYISAM") {
                    if ($lastIsamTable) {
                        $lastTable = $lastIsamTable;
                    }
                    $entry = null;
                    if ($lastTable) {
                        $entry = ($this->isamTables[$lastTable]+1);
                    }
                    if (!$entry) {
                        $entry = 1;
                    }
                    $this->isamTables[$table] = $entry;
                    $this->isamTablesRetryMap[$table] = 0;
                    $lastIsamTable = $table;
                } elseif ($engine == MysqlDumper::INNODB) {
                    if ($lastInnodbTable) {
                        $lastTable = $lastInnodbTable;
                    }
                    $entry = null;
                    if ($lastTable) {
                        $entry = ($this->innodbTables[$lastTable]+1);
                    }
                    if (!$entry) {
                        $entry = 1;
                    }
                    $this->innodbTables[$table] = $entry;
                    if ($this->dumpall) {
                        $this->innodbTablesRetryMap[$table] = 0;
                    }
                    $lastInnodbTable = $table;
                } else {
                    $lastIsamTable =  null;
                    $lastInnodbTable = null;
                }
                $this->tableCount++;
                if (is_object($result) && $result->num_rows) {
                    $result->free();
                }
            } else {
                // just in case the match option was used.
                $missingFromCloneTables []= $table;
            }
        }
        if (count($this->innodbTables) + count($this->isamTables) !== count($this->tables) - count($missingFromCloneTables)) {
            $badTables = array_diff($this->tables, $this->isamTables, $this->innodbTables, $missingFromCloneTables);
            throw new Exception("The database did not give info about some tables:". implode(' ', $badTables), true);
        }
        // set the numeric indexes to 0 -> N-1
        $this->tables = array_values($this->tables);
        // reverse key to value mapping so integers map to table strings.
        $this->innodbTables = array_flip($this->innodbTables);
        $this->isamTables = array_flip($this->isamTables);
        if ($this->percent !== 100) {
            $tables = array_merge($this->innodbTables, $this->isamTables);
            foreach ($tables as $table) {
                $countResult = self::$Db->query($this->getSqlCountTableRows($table, $this->cloneDbSchema));
                $ok = self::$Db->commit();
                if ($ok) {
                    $countRow = $countResult->fetch_row();
                    $count = $countRow[0];
                    // @todo add option to give specific table limits, or distinct spec
                    if (intval($count) > 0) {
                        $this->tableLimits[$table] = (int)floor($count * ($this->percent / 100)) + 1;
                    } else {
                        $this->tableLimits[$table] = 1;
                    }
                    if (is_object($countResult) && $countResult->num_rows) {
                        $countResult->free();
                    }
                } else {
                    $err = self::MysqlHasErrors();
                    echo $err['message'];
                    echo "Rollback\n";
                    self::$Db->rollback();
                    $err2 = self::MysqlHasErrors();
                    echo $err['message'];
                    $exitVal = $err2['code'] !== 0 ? $err2['code'] : $err['code'];
                    exit($exitVal);
                }
            }
        }
    }

    /**
     * Fill the tables array for the object.
     */
    protected function runSqlGetDatabaseTables()
    {
        if ($err = self::MysqlHasErrors()) {
            if ($err['code'] !== 0) {
                echo $err['message'];
                exit($err['code']);
            }
        }
        if ($this->match) {
            $schema = $this->updateDbSchema;
        } else {
            $schema = $this->cloneDbSchema;
        }
        $result = self::$Db->query($this->getSqlInformationSchemaTables($schema));
        $ok = self::$Db->commit();
        if ($ok) {
            while ($row = $result->fetch_object()) {
                $table = $row->TABLE_NAME;
                $this->tables []= $table;
            }
            if (is_object($result) && $result->num_rows) {
                $result->free();
            }
        } else {
            self::$Db->rollback();
            echo "Rollback get information schema table.\n";
            exit(-1);
        }
        try {
            $this->cleanTablesMember();
        } catch (Exception $e) {
            echo Color::colorize('red', "Something went wrong, sorry.", true);
            echo $e->getMessage();
            exit(1);
        }
        if ($this->whereStringBuilder) {
            // This class method involves some user interaction.
            list($this->innodbTables, $this->isamTables, $this->tableLimits) = $this->whereStringBuilder->build($this->cloneDbSchema, $this->innodbTables, $this->isamTables);
            $this->tables = array_merge($this->innodbTables, $this->isamTables);
            if (count($this->tables) !== count($this->innodbTables) + count($this->isamTables)) {
                echo Color::colorize('red', "The number of tables returned by where string builder is wrong.", true);
                exit(1);
            }
        }
    }

    protected function startDumpProcess($table, $engine)
    {
        $process = new MysqlDumper($this->updateDbSchema, $this->cloneDbSchema, $table,
                                   $this->tableLimits[$table], $this->userLogFile, $engine);
        $process->start();
        $this->mysqlDumpers [$process->getPid()]= $process;
    }

    protected function checkRetryDumpProcess(MysqlDumper $dumper, array &$completedTables)
    {
        $checkProc = $dumper->check();
        switch ($checkProc) {
        case 0:
            if (array_search($dumper->getTable(), $completedTables, true) === false) {
                echo Color::colorize('green', "mysqldump for " . $dumper->getTable() . " has finished.", true);
                $completedTables []= $dumper->getTable();
            } else {
                $checkProc = false;
            }
            unset($this->mysqlDumpers[$dumper->getPid()]);
            break;
        case true:  // process still running
            break;
        case false: // process not found
            echo Color::colorize('red', "Process not found for " . $dumper->getTable() . " mysqldump.", true);
        default:
            echo Color::colorize('yellow', $dumper->getCommand() . " exit status: " . strval($checkProc), true);
            usleep(20000);
            $this->retryDumpProcess($dumper);
        }
        return $checkProc;
    }

    protected function retryDumpProcess(MysqlDumper $dumper)
    {
        $dumper->printErr();
        if ($dumper->getEngine() === MysqlDumper::INNODB) {
            $retry = ++$this->innodbTablesRetryMap[$dumper->getTable()];
        } else {
            $retry = ++$this->isamTablesRetryMap[$dumper->getTable()];
        }
        if ($retry < $this->retries) {
            echo Color::colorize('red', "Retrying mysqldump for " . $dumper->getTable(), true);
            $dumper->start();
        } else {
            $dumper->exitWithProcessRetCode();
        }
    }

    /**
     * Depending on the dumpall option, use mysqldump or plain queries to update innodb tables.
     *
     * @param & int $index - into `$this->innodbTables`
     * @param & int $retIndex - the counter for tables completed
     * @param & int $tableRetry - the number of times the table has been retried
     */
    private function doInnoDbDumpProcess(&$index, &$retIndex, &$tableRetry)
    {
        if ($this->dumpall) {
            if (count($this->mysqlDumpers) < $this->procs) {
                $table = array_values($this->innodbTables)[$index++];
                $this->startDumpProcess($table, MysqlDumper::INNODB);
            }
        } else {
            $table = array_values($this->innodbTables)[$index++];
            echo Color::colorize('green', "About to commit an update/insert into {$this->updateDbSchema}.{$table}", true);
            $queryString = 'SET FOREIGN_KEY_CHECKS=0' . self::SQL_SEP . $this->getSqlDropTable($table);
            $queryString .= self::SQL_SEP . $this->getSqlCreateTable($table);
            $queryString .= self::SQL_SEP . $this->getSqlInsertIntoTable($table) . self::SQL_SEP;
            $queryString .= 'SET FOREIGN_KEY_CHECKS=1' . self::SQL_SEP;
            echo Color::colorize('cyan', $queryString, true);
            self::$Db->begin_transaction();
            if (self::$Db->multi_query($queryString)) {
                ++$retIndex;
                $tableRetry = 0;
                do {
                    $result = self::$Db->store_result();
                    if (is_object($result) && $result->num_rows) {
                        $result->free();
                    }
                } while (self::$Db->next_result());
            } else {
                self::$Db->rollback();
                $err = self::MysqlHasErrors();
                echo $err['message'];
                if (++$tableRetry < $this->retries) {
                    --$index;
                    continue;
                }
                exit ($err['code']);
            }
        }
    }

    /**
     * Get the count for some table
     *
     * @param string $table
     */
    private function getSqlCountTableRows($table, $schema)
    {
        return "SELECT COUNT(*) FROM {$schema}.{$table}";
    }

    /**
     * Create a table in update schema like that in clone schema.
     *
     * @param string $table
     * @return string
     */
    private function getSqlCreateTable($table)
    {
        return "CREATE TABLE {$this->updateDbSchema}.{$table} LIKE {$this->cloneDbSchema}.{$table}";
    }

    /**
     * Drop table from update schema if it exists.
     *
     * @param string $table
     * @return string
     */
    private function getSqlDropTable($table)
    {
        return "DROP TABLE IF EXISTS {$this->updateDbSchema}.{$table}";
    }

    /**
     * Get the tables that will be cloned from information schema.
     *
     * @return string
     */
    private function getSqlInformationSchemaTables($schema)
    {
        return "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='{$schema}'";
    }

    /**
     * Insert into the update schema the table from the clone schema.
     *
     * @return string
     */
    private function getSqlInsertIntoTable($table)
    {
        if ($this->percent !== 100) {
            $addendum = ' LIMIT ' . $this->tableLimits[$table];
        }
        return "INSERT INTO {$this->updateDbSchema}.{$table} SELECT * FROM {$this->cloneDbSchema}.{$table}" . $addendum;
    }

    /**
     * @param string $table
     * @return string
     */
    private function getSqlSelectCloneDbTable($table)
    {
        return "SELECT TABLE_NAME, ENGINE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='{$this->cloneDbSchema}' AND TABLE_NAME='{$table}'";
    }

    /**
     * Get schema name if it exists.
     *
     * @param string $schema
     * @return string
     */
    private function getSqlSelectSchema($schema)
    {
        return  "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='$schema'";
    }
}