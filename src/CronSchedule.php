<?php
declare(strict_types=1);


namespace phylogram\CronScheduleCleaning;


class CronSchedule
{
    private int $timestamp;
    private string $hook;
    /**
     * @var array from wordpress, example:
     * [
            "schedule" => "job_manager_usage_tracking_two_weeks",
            "args" => [],
            "interval" => 1296000,
        ],
     *
     */
    private array $cronDefinition;
    private bool $strict;
    private bool $force_on_corrupted_md5;

    /**
     * CronSchedule constructor.
     * @param int $timestamp
     * @param string $hook
     * @param array $cron_definition
     * @param $strict bool throw Error (or just send message an false)
     * @param bool $force_on_corrupted_md5
     */
    public function __construct(int $timestamp, string $hook, array $cron_definition, bool $strict=false, $force_on_corrupted_md5=false)
    {
        $this->timestamp = $timestamp;
        $this->hook = $hook;
        $this->cronDefinition = $cron_definition;
        $this->strict = $strict;
        $this->force_on_corrupted_md5 = $force_on_corrupted_md5;
    }

    public function remove(): bool
    {
        $result = wp_unschedule_event(
            $this->timestamp,
            $this->hook,
            $this->cronDefinition['args']
        );
        $reasons = $this->findTroubles();
        if ($this->strict) {
            throw new \RuntimeException($reasons);
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getHook(): string
    {
        return $this->hook;
    }

    public function __toString()
    {
        $date = \date('c', $this->timestamp);
        return "Cron {$this->hook} at {$date}";
    }

    /**
     * https://developer.wordpress.org/reference/functions/wp_unschedule_event/
     * @noinspection MultipleReturnStatementsInspection Whatever, that's wp
     *
     */
    private function findTroubles(): string
    {
        $numeric = is_numeric($this->timestamp);
        if (! $numeric) {
            $type = \gettype($this->timestamp);
            return "timestamp {$this->timestamp} of type {$type} not numeric";
        }
        if ($this->timestamp < 0) {
            return "time stamp {$this->timestamp} is smaller than 0";
        }

        if ( ! \array_key_exists('args', $this->cronDefinition)) {
            return 'Not found args in cron definition, only ' . print_r($this->cronDefinition, true);
        }

        $args = $this->cronDefinition['args'];

        try {
            $args_serialized = \serialize($args);
        } catch (\Exception $exception) {
            return "Error in serializing {$exception->getMessage()}";
        }

        if (\strlen($args_serialized) < 10) {
            return "Serializes args are too short. That can not be right!: $args";
        }

        try {
            $md5 = \md5($args_serialized);
        } catch (\Exception $exception) {
            return "Error in md5 {$exception->getMessage()}";
        }
        $crons = _get_cron_array();
        if (! \array_key_exists($this->timestamp, $crons)) {
            return "Did not find timestamp {$this->timestamp} in cron array";
        }
        $crons = $crons[$this->timestamp];

        if (! \array_key_exists($this->hook, $crons)) {
            return "Did not find hook {$this->hook} in cron array [{$this->timestamp}], only "  .\implode(', ', \array_keys($crons));
        }

        $crons = $crons[$this->hook];

        if (! \array_key_exists($md5, $crons)) {

            $available_md5s = \json_encode(\array_keys($crons));

            if ($this->force_on_corrupted_md5 === true) {
                $result = \json_encode($this->forceDelete());
                return "Did not find md5 {$md5} in cron array [{$this->timestamp}, {$this->hook}]. Forced delete with result: {$result} Found {$available_md5s} md5 hashes";

            }

            return "Did not find md5 {$md5} in cron array [{$this->timestamp}, {$this->hook}]. Found {$available_md5s} md5 hashes";
        }

        $crons = $crons[$md5];
        $crons = \print_r($crons, true);

        return "No clue what happend ts: {$this->timestamp}, hook {$this->hook}, md5 {$md5}, cron {$crons}";

    }

    /**
     * \wp_unschedule_event can fail, because of wrong md5 hashes … force it
     */
    public function forceDelete(): bool
    {

        $crons = _get_cron_array();
        $hook_crons = $crons[$this->timestamp][$this->hook];
        $n = \count($hook_crons);
        if ( $n !== 1) {
            $hashes = \json_encode(\array_keys($hook_crons));
            throw new \RuntimeException("Could not decide which one to one to delete. Found; {$hashes}");
        }
        if ($n === 0) {
            throw new \LogicException('Did not find any hashes. Check the code');
        }

        $hash = \array_keys($hook_crons)[0];

        unset($crons[$this->timestamp][$this->hook][$hash]);
        if ( empty( $crons[ $this->timestamp ][ $this->hook ] ) ) {
            unset( $crons[ $this->timestamp ][ $this->hook ] );
        }
        if ( empty( $crons[ $this->timestamp ] ) ) {
            unset( $crons[ $this->timestamp ] );
        }
        return _set_cron_array( $crons );

    }
}