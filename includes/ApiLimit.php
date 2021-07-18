<?php

namespace Kvnc;


class ApiLimit

{
  
    protected int $times = 0;

   
    protected int $totalDuration = 0;

  
    protected array $microTimeArray = [];

      
    /**
     * __construct
     *
     * @param  mixed $times
     * @param  mixed $totalDuration
     * @return void
     */
    public function __construct(int $times, int $totalDuration)
    {
        $this->times = $times;
        $this->totalDuration = $totalDuration;
    }

       
    /**
     * listen
     *
     * @return void
     */
    public function listen(): void
    {
        $this->clear();
        $this->microTimeArray[] = microtime(true);

        if (!$this->haveSlot()) {
            $wait_duration = $this->blockTillHaveSlot();
            usleep($wait_duration);
        }
    }

    /**
     * Clear old microtimes
     */
    protected function clear(): void
    {
        $cutoff = microtime(true) - $this->totalDuration;

        $this->microTimeArray = array_filter($this->microTimeArray, function ($item) use ($cutoff) {
            return $item >= $cutoff;
        });
    }

    /**
     * Check if have available slot now
     *
     * @return bool
     */
    protected function haveSlot(): bool
    {
        return count($this->microTimeArray) < $this->times;
    }

    /**
     * Get the number of microseconds until we can run the next instance
     *
     * @return float
     */
    protected function blockTillHaveSlot(): float
    {
        $oldest = $this->microTimeArray[0];
        $is_free = $oldest + $this->totalDuration * 1000000;
        $now = microtime(true);

        return ($is_free < $now) ? 0 : $is_free - $now;
    }
}