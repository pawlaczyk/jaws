<?php
/**
 * Jaws File Mutex class
 *
 * @category    Mutex
 * @package     Core
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2019 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Mutex_File extends Jaws_Mutex
{
    /**
     * lock files prefix
     * @var     string  $lockPrefix
     * @access  private
     */
    private $lockPrefix = 'lock_';

    /**
     * lock files directory
     * @var     string  $lockDirectory
     * @access  private
     */
    private $lockDirectory;

    /**
     * Constructor
     *
     * @access  public
     * @return  void
     */
    function __construct()
    {
        $this->lockDirectory = rtrim(sys_get_temp_dir(), '/\\');
    }

    /**
     * Acquire exclusive access
     *
     * @access  public
     * @param   int     $lkey   Lock identifier
     * @param   float   $nowait Wait for the exclusive access to be acquired?
     * @return  bool    True if exclusive access Acquired otherwise False
     */
    function acquire($lkey, $nowait  = false)
    {
        if (!isset($this->mutexs[$lkey])) {
            $this->mutexs[$lkey] = fopen($this->lockDirectory . '/'. $this->lockPrefix . (string)$lkey, 'a+');
        }

        while (!($lock = flock($this->mutexs[$lkey], LOCK_EX | LOCK_NB)) && !$nowait) {
            //Exclusive access not acquired, try again
            usleep(mt_rand(0, 100)); // 0-100 microseconds
        }

        return $lock;
    }

    /**
     * Release exclusive access
     *
     * @access  public
     * @param   int     $lkey   Lock identifier
     * @return  void
     */
    function release($lkey)
    {
        if (isset($this->mutexs[$lkey])) {
            flock($this->mutexs[$lkey], LOCK_UN);
            fclose($this->mutexs[$lkey]);
            parent::release($lkey);
        }
    }

}