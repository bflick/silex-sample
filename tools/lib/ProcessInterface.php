<?php
namespace Tools;

interface ProcessInterface
{
    /**
     * @return int|bool sh exit code of command or true if running
     */
    public function check();
    /**
     * @param $timeout optional
     * @return array the output of the process
     */
    public function collect($timeout=null);
    /**
     * @param resource $handle
     * @return bool -  success value
     */
    public function input($handle);
    /**
     * Write the errors to STDERR
     */
    public function printErr();
    /**
     * Start the process
     */
    public function start();
    /**
     * Wait for process to finish, and return success status
     *
     * @return bool whether process exited successfully
     */
    public function wait();
}