<?php


namespace phylogram\CronScheduleCleaning;


class CronScheduleFinder
{
    private array $cronSchedules;

    /**
     * CronScheduleFinder constructor.
     * @param string $pattern
     * @param bool $strict
     * @param bool $force_on_corrupted_md5
     */
    public function __construct(string $pattern, bool $strict = false, bool $force_on_corrupted_md5=false)
    {
        $crons = _get_cron_array();
        $filtered = [];
        foreach ($crons as $timestamp => $hooks) {
            foreach ($hooks as $hook => $crons) {
                if (\preg_match($pattern, $hook)) {
                    foreach ($crons as $idx => $cron_definition) {
                        $filtered[$hook][] = new CronSchedule((int) $timestamp, $hook, $cron_definition, $strict, $force_on_corrupted_md5);
                    }
                }
            }
        }
        $this->cronSchedules = $filtered;
    }

    public function getCronSchedules(): array
    {
        return $this->cronSchedules;
    }
}