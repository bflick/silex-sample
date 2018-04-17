<?php
namespace Tools;

require_once(__DIR__ . '/ProcessInterface.php');

class ProcOpenProcess implements ProcessInterface
{
    const KEYSPLIT = '|';

    /**
     * @var float $timeBegan
     */
    private $timeBegan = 0.0;

    /**
     * @var mixed $Statuses
     */
    protected static $Statuses = array();

    /**
     * @var int $pid
     */
    private $pid = 0;

    /**
     * @var resource $resource
     */
    private $resource;

    /**
     * @var array $pipes
     */
    private $pipes = array();

    /**
     * @var array $output
     */
    protected $output = array();

    /**
     * @var  string $logFilePath
     */
    protected $logFilePath;

    /**
     * @var int $exitcode
     */
    public $exitcode = 1;

    /**
     * @var int $Timeout
     */
    protected static $Timeout = 1000;

    /**
     * @var string exec which is used prefixing proc_open command
     */
    private $exec = 'exec';

    /**
     * @var bool $cleanupLog
     */
    private $cleanupLog = false;

    /// @todo add env option
    public function __construct($command, $logFilePath, $env=null, $exec=null)
    {
        if (null !== $exec) {
            if (!is_string($exec)) {
                throw new Exception("exec prefix must be a string");
            }
            $this->exec = $exec;
        }
        if (!is_string($command)) {
            throw new Exception("command should be a string");
        }
        $this->command = $command;
        if (null === $logFilePath) {
            $logFilePath = '/tmp/' . md5(microtime());
            $this->cleanupLog = true;
        }
        if (!is_string($logFilePath)) {
            throw new Exception("log file path must be a valid filepath.");
        } elseif (!is_file($logFilePath)) {
            @touch($logFilePath);
            if (!is_file($logFilePath)) {
                throw new Exception("log file path must be a valid filepath.");
            }
        }
        $this->logFilePath = $logFilePath;
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, array(&$this, 'signalHandler'), false);
            pcntl_signal(SIGINT, array(&$this, 'signalHandler'), false);
        }
    }

    public function __destruct()
    {
        if ($this->cleanupLog) {
            unlink($this->logFilePath);
        }
        unset(self::$Statuses[$this->getPidUtime()]);
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
    }

    public static function CheckProcessesHaveExited()
    {
        foreach (self::$Statuses as $key => $status) {
            if ($status['running'] === false) {
                yield $key => $status;
            }
        }
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @return string
     */
    public function getPidUtime()
    {
        return strval($this->pid) . self::KEYSPLIT . strval($this->timeBegan);
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function exitWithProcessRetCode()
    {
        unset(self::$Statuses[$this->getPidUtime()]);
        $this->printErr();
        $this->closePipes();
        if (is_resource($this->resource)) {
            $status = proc_get_status($this->resource);
            if (!$status['running']) {
                $this->exitcode = $status['exitcode'];
                proc_close($this->resource);
            } else {
                $this->exitcode = proc_terminate($this->resource, SIGTERM);
                if (is_resource($this->resource)) {
                    $this->exitcode = $this->getProcStatus()['exitcode'];
                }
            }
        }
        exit($this->exitcode);
    }

    public function start()
    {
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("file", $this->logFilePath, "a") // stderr is a file to write to
        );
        $this->resource = proc_open($this->exec . ' ' . $this->command, $descriptorspec, $this->pipes);
        if (!$this->resource) {
            echo Color::colorize('red', "Can't start process: " . $this->command, true);
            exit($this->exitcode);
        }
        $status = $this->getProcStatus();
        $this->pid = $status['pid'];
        $this->timeBegan = microtime(true);
        self::$Statuses [$this->getPidUtime()]= $status;
    }

    /**
     * @return bool|int - true if running exitcode of process if finished
     *                    false if theres an error.
     */
    public function check()
    {
        if (is_resource($this->resource)) {
            $status = $this->getProcStatus();
            self::$Statuses [$this->getPidUtime()]= $status;
            if (0 === pcntl_waitpid($this->pid, $pcntlStatus, WNOHANG)) {
                return true;
            }
            if (!$status['running']) {
                $this->collect();
                $this->closePipes();
                if (pcntl_wifexited($pcntlStatus)) {
                    $this->exitcode = pcntl_wexitstatus($pcntlStatus);
                } else {
                    proc_close($this->resource);
                    $this->exitcode = $status['exitcode'];
                }
                return $this->exitcode;
            } else {
                return true;
            }
        }
        return false;
    }

    public function input($handle)
    {
        if (is_resource($handle) && is_resource($this->pipes[0])) {
            if (!feof($handle)) {
                return stream_copy_to_stream($handle, $this->pipes[0]);
            }
        }
        return false;
    }

    public function collect($to=null)
    {
        if (is_int($to)) {
            $timeout = $to;
        } else {
            $timeout = 5;
        }
        if (is_resource($this->pipes[1])) {
            stream_set_blocking($this->pipes[1], true);
            if (false !== ($numStreamsChanged = stream_select($this->pipes, $write, $except, $timeout))) {
                if ($numStreamsChanged) {
                    if ($raw = stream_get_contents($this->pipes[1])) {
                        $this->output = array('raw' => $raw);
                    }
                }
            }
            if (is_resource($this->pipes[1])) {
                stream_set_blocking($this->pipes[1], false);
            }
        }
        return $this->output;
    }

    /**
     * @return bool - true if  successfully finished
     */
    public function wait()
    {
        if (is_resource($this->resource)) {
            $this->collect();
            $this->closePipes();
            $status = $this->getProcStatus();
            if (is_resource($this->resource)) {
                if (!$status['running']) {
                    $this->exitcode = $status['exitcode'];
                    proc_close($this->resource);
                } else {
                    $this->exitcode = proc_close($this->resource);
                }
            } else {
                pcntl_waitpid($this->pid, $pcntlStatus, WUNTRACED);
                if (pcntl_wifexited($pcntlStatus)) {
                    $this->exitcode = pcntl_wexitstatus($pcntlStatus);
                } else {
                    echo Color::colorize('red', "Warning: dangling resource.", true);
                }
            }
        }
        if ($this->exitcode !== 0) {
            return false;
        }
        return true;
    }

    public function printErr($out=STDERR)
    {
        if (false === is_resource($out)) {
            $out = STDERR;
        }
        list($pid, $utime) = explode(self::KEYSPLIT, $this->getPidUtime());
        if (isset($this->pipes[2]) && is_resource($this->pipes[2])) {
            stream_set_blocking($this->pipes[2], true);
            $output = '';
            if (false !== ($numStreamsChanged = stream_select($this->pipes, $write, $except, 5))) {
                if ($numStreamsChanged) {
                    if ($err = stream_get_contents($this->pipes[2]))  {
                        $output .= $err;
                    }
                }
            } else {
                fwrite($out, Color::colorize('red', "Couldn't read error stream output file ("
                                    . $this->logFilePath . ") for process " . $pid . " begun "
                                    . date("Y-m-d H:i:s.u", $utime)  . ".", true));
            }
            fwrite($out, Color::colorize('red', $output, true));
            stream_set_blocking($this->pipes[2], false);
        } else {
            fwrite($out, Color::colorize('red', "No resource found for process (" . $pid
                                           . " begun " . date("Y-m-d H:i:s.u", $utime) . ") error log.", true));
        }
        flush();
    }

    public function signalHandler($signo)
    {
        // Only handling sigterm and sigint
        if (is_resource($this->resource)) {
            $this->closePipes();
            if (is_resource($this->resource))  {
                $this->exitcode = proc_terminate($this->resource, $signo);
            }
        }
        exit($this->exitcode);
    }

    private function getProcStatus()
    {
        $status = null;
        if (is_resource($this->resource)) {
            $status = proc_get_status($this->resource);
            $status['table'] = $this->table;
        }
        return $status;
    }

    private function closePipes()
    {
        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
    }

}