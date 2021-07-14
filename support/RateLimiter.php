<?php


namespace Support;

use Carbon\Carbon;
use Carbon\CarbonInterval;
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

    public function __construct(int $limit, CarbonInterval $windowSize, bool $debug = false)
    {
        $this->limit = $limit;
        $this->windowSize = $windowSize;
        $this->debug = $debug;
        $this->mode = self::MODE_DELAY;
    }

    public function setMode(string $mode)
    {
        $modes = [
            self::MODE_DELAY,
            self::MODE_FAIL,
        ];

        if (! in_array($mode, $modes)) {
            throw new Exception('Invalid mode. Must be in list: ' . implode(', ', $modes));
        }

        $this->mode = $mode;
    }

    public function hit()
    {
        if ($this->debug && $this->window) {
            echo "Was new window created before tokens exhausted?" . PHP_EOL;
            var_dump($this->window['end']->isBefore(Carbon::createFromTimestamp(microtime()))) . PHP_EOL;
        }

        if (! $this->window || $this->window['end']->isBefore(Carbon::createFromTimestamp(microtime()))) {
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
                    throw new Exception("Too many requests.");
            }
        }

        $this->tokens--;
    }

    protected function createWindow()
    {
        $now = Carbon::createFromTimestamp(microtime());

        $this->window = [
            'start' => $now,
            'end' => $now->copy()->add($this->windowSize),
        ];

        if ($this->debug) {
            echo "New window created. Start: {$this->window['start']->format('d/m/Y h:i:s.u')}. End: {$this->window['end']->format('d/m/Y h:i:s.u')}." . PHP_EOL;
        }

        $this->tokens = $this->limit;
    }

    protected function delay()
    {
        if ($this->debug) {
            echo "Delay initiated at " . Carbon::createFromTimestamp(microtime())->format('d/m/Y h:i:s.u') . "." . PHP_EOL;
        }

        time_sleep_until($this->window['end']->timestamp);

        if ($this->debug) {
            echo "Delay finished at " . Carbon::createFromTimestamp(microtime())->format('d/m/Y h:i:s.u') . "." . PHP_EOL;
        }

        $this->createWindow();
    }
}