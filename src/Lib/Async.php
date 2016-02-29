<?php
namespace Sluggard\Lib;

use Sluggard\SluggardApp;

class async
{
    private $app;
    private $child;

    public function __construct(SluggardApp $app) {
        $this->app = $app;
    }

    /**
     * Do something asynchronously.
     *
     * This neat little method sets up a UNIX socket for IPC, and then forks the current process.
     * The child is then able to talk to the parent, by way of a return value.
     *
     * @param callable $function A callback that is running in the forked instance. Anything returned will be captured by the main process.
     *
     * @return int The PID of the child process, for keeping track of your children.
     */
    public function async($function) {
        socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $sockets);
        list($parent, $child) = $sockets;

        // We're a child now
        if(($pid = pcntl_fork()) == 0) {
            socket_close($child);
            socket_write($parent, serialize(call_user_func($function, $this)));
            socket_close($parent);

            exit;
        }

        socket_close($parent);

        $this->child[$pid] = $child;

        return $pid;
    }

    /**
     * Read the value returned by a child process.
     *
     * @param int $pid The PID of the child process to read.
     *
     * @return mixed The stored value is returned if the child has exited, otherwise false.
     */
    public function read($pid) {
        if(isset($this->child[$pid])) {
            if(!is_resource($this->child[$pid])) {
                $output = $this->child[$pid];
                unset($this->child[$pid]);
                return $output;
            }
        }

        return false;
    }

    /**
     * Wait for a child process to exit and return its value.
     *
     * Similar to the read function but will wait for the child to exit if necessary
     *
     * @param int $pid The PID of the child process to read.
     *
     * @return mixed The returned value of the child process.
     */
    public function wait($pid) {
        if(isset($this->child[$pid])) {
            pcntl_waitpid($pid, $status);
            if(is_resource($this->child[$pid])) {
                $output = unserialize(socket_read($this->child[$pid], 4096));
                socket_close($this->child[$pid]);
            } else {
                $output = $this->child[$pid];
            }
            unset($this->child[$pid]);
            return $output;
        }
    }
}