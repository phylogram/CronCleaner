<?php


namespace phylogram\CronScheduleCleaning;


class RemoveCorruptedCrons
{

    private array $faulty = [];

    public function __construct()
    {

        $crons = _get_cron_array();
        foreach ( $crons as $timestamp => $timestamp_crons) {
            foreach ( $timestamp_crons as $hook => $hook_crons) {
                foreach ($hook_crons as $md5 => $cron_definition) {
                    $cron = new CronSchedule($timestamp, $hook, $cron_definition);
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

}