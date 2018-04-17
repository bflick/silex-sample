<?php
namespace Tools;

require_once(__DIR__ . '/ProcOpenProcess.php');

class MysqlDumper extends ProcOpenProcess
{
    const INNODB = 'INNODB';
    const MYISAM = 'MYISAM';

    const DEFAULT_OPTIONS = '--skip-disable-keys';
    const DEFAULT_MYISAM_OPTIONS = '--skip-lock-tables';
    const DEFAULT_INNODB_OPTIONS = '--single-transaction';

    const MYSQL = 'mysql';
    const MYSQLDUMP = 'mysqldump';

    /**
     * @var string $options
     */
    protected $options = '';

    /**
     * @var string $table
     */
    protected $table;

    /**
     * @var string $toLoc
     */
    protected $toLoc;

   /**
     * @var string $fromSchema
     */
    protected $fromSchema;

    /**
     * @var string $engine
     */
    protected $engine;

    public function __construct($toLoc, $fromSchema, $table=null, $limit=null, $logFilePath=null, $engine=null)
    {
        if (null !== $engine) {
            if (!is_string($engine) || !($engine === self::MYISAM || $engine === self::INNODB)) {
                throw new Exception("engine must be either myisam or innodb");
            }
        }
        if ($limit !== null) {
            if (!(is_array($limit) || is_int($limit)) || (is_int($limit) && $limit <= 0)) {
                throw new Exception("limit must be an integer greater than zero.");
            }
        }
        if (null === $logFilePath) {
           $logFilePath = '/tmp/mysql-dumper.log';
        }
        if (null !== $table && !is_string($table)) {
            throw new Exception("table must be a string.");
        }
        if (!is_string($toLoc)) {
            throw new Exception("toLoc must be a string.");
        }
        if (!is_string($fromSchema)) {
            throw new Exception("fromSchema must be a string.");
        }
        $this->table = $table;
        $this->toLoc = $toLoc;
        $this->fromSchema = $fromSchema;
        // @todo make an options parameter to override this
        $this->options = self::DEFAULT_OPTIONS;
        if ($engine == self::INNODB || $engine == null) {
            $this->options .= ' ' . self::DEFAULT_INNODB_OPTIONS;
            $this->engine = self::INNODB;
        } else {
            $this->options .= ' ' . self::DEFAULT_MYISAM_OPTIONS;
            $this->engine = self::MYISAM;
        }
        if ($limit !== null) {
            if (is_array($limit)) {
                $columnsInSet = WhereStringBuilder::GetColumnsInSet($limit);
                $this->options .= "--where='" . $columnInSet . "'";
            } elseif (is_int($limit)) {
                $this->options .= " --where='1 LIMIT " . $limit . "'";
            }
        }
        if (substr($this->toLoc, -6) === '.' . self::MYSQL) {
            $output = ' > ' . $this->toLoc;
        } else {
            $output = ' | ' . self::MYSQL . ' ' . $this->toLoc;
        }
        $procCmd = self::MYSQLDUMP . ' ' . $this->fromSchema . ' ' . $this->table . ' '
                 . $this->options . $output;

        parent::__construct($procCmd, $logFilePath);
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    /**
     * @return string
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }
}
