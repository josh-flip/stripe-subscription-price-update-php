<?php


namespace Support;

use Exception;

/**
 * Class RateLimiter
 * A fixed-window token bucket rate limiting implementation.
 */
class RateLimiter
{
    protected $limit;
    protected $windowSize;
    protected $tokens;
    protected $window;
    protected $mode;
    protected $debug;

    const MODE_FAIL = 'fail';
    const MODE_DELAY = 'delay';

    public function __construct($limit, $windowSize, $debug = false)
    {
        $this->limit = $limit;
        $this->windowSize = $windowSize;
        $this->debug = $debug;
        $this->mode = self::MODE_DELAY;
    }

    public function setMode($mode)
    {
        $modes = [
            self::MODE_DELAY,
            self::MODE_FAIL,
        ];

        if (! in_array($mode, $modes)) {
            throw Exception('Invalid mode. Must be in list: ' . implode(', ', $modes));
        }

        $this->mode = $mode;
    }

    public function hit()
    {
        if (! $this->window || $this->window['end'] < time()) {
            // Create a new window if this is the first action
            // or if we have passed into the next window.
            $this->createWindow();
        }

        if ($this->tokens === 0) {
            switch ($this->mode) {
                case self::MODE_DELAY:
                    $this->delay();
                    break;
                case self::MODE_FAIL:
                    throw new Exception("Too many requests, provided limit of {$this->limit} action(s) per {$this->windowSize} second(s) exceeded.");
            }
        }

        $this->tokens--;
    }

    protected function createWindow()
    {
        $this->window = [
            'start' => time(),
            'end' => time() + $this->windowSize,
        ];

        $this->tokens = $this->limit;
    }

    protected function delay()
    {
        if ($this->debug) {
            echo "Delay initiated at " . time() . "." . PHP_EOL;
        }

        sleep($this->window['end'] - time());

        if ($this->debug) {
            echo "Delay finished at " . time() . "." . PHP_EOL;
        }

        $this->createWindow();
    }
}