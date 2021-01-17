<?php


namespace phylogram\CronScheduleCleaning;


class RemoveCorruptedCrons
{

    /**
     * @var CronSchedule[]
     */
    private array $faulty = [];

    public function __construct()
    {

        $crons = _get_cron_array();
        foreach ( $crons as $timestamp => $timestamp_crons) {
            foreach ( $timestamp_crons as $hook => $hook_crons) {
                foreach ($hook_crons as $md5 => $cron_definition) {
                    $cron = new CronSchedule($timestamp, $hook, $cron_definition, false, true);
                    $args_checker = $cron->getArgsChecker();
                    if ($args_checker->check()) {
                        $this->faulty[] = $cron;
                    }
                }
            }
        }

    }

    /**
     * @return array
     */
    public function getFaulty(): array
    {
        return $this->faulty;
    }

    public function __toString()
    {
        return \implode("\n", $this->faulty);
    }

    public function drop()
    {
        return \array_map(static fn (CronSchedule $cronSchedule) => $cronSchedule->remove(), $this->faulty);
    }

}