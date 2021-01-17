<?php
declare(strict_types=1);


namespace phylogram\CronScheduleCleaning;


class CronScheduleManager
{
    /**
     * @var CronSchedule[]
     */
    private array $filtered;
    /**
     * @var CronSchedule[]
     */
    private array $crons = [];
    private string $hook;
    /**
     * @var CronSchedule[]
     */
    private array $keeps = [];

    /**
     * CronScheduleManager constructor.
     *
     * Will look for actions with more than one callable in the cron array Will keep the newest
     *
     * @param CronSchedule[] $crons
     * @param string $hook
     * @param int $n number of crons min to target
     */
    public function __construct(array $crons, string $hook, int $n=1)
    {
        $this->crons = $crons;

        $timestamps = \array_map(
            static fn(CronSchedule $cronSchedule): int => $cronSchedule->getTimestamp(),
            $crons
        );

        $newest = \max($timestamps);

        $this->filtered = [];

        if (\count($this->crons) > $n) {
            $dropped = false;
            foreach ($crons as $cron) {
                if ($dropped === false && $cron->getTimestamp() === $newest) {
                    $this->keeps[] = $cron;
                    $dropped = true;
                    continue;
                }
                $this->filtered[] = $cron;
            }
        }
        $this->hook = $hook;
    }

    public function drop(): bool
    {
        $drops = \array_map(
            static fn(CronSchedule $cronSchedule): bool => $cronSchedule->remove(),
            $this->filtered
        );

        $rights = \count(\array_filter($drops));
        $all = \count($this->filtered);
        $failed = $all - $rights;
        $correct =  $failed === 0;
        echo $correct ? "\nDeleted all {$all} crons for {$this->hook}\n" : "\nFailed to delete {$failed} of {$all} for {$this->hook}\n";
        return $correct;

    }

    /**
     * @return CronSchedule[]
     */
    public function getFiltered(): array
    {
        return $this->filtered;
    }

    /**
     * @return CronSchedule[]
     */
    public function getCrons(): array
    {
        return $this->crons;
    }

    /**
     * Check if the filtering has worked
     * @return bool
     */
    public function isCorrect(): bool {
        return
            \count($this->keeps) === 1
            && (\count($this->crons) - \count($this->filtered)) === 1;
    }

    public function __toString()
    {
        $crons = \count($this->crons);
        $filtered = \count($this->filtered);
        $keeps = \implode(', ', $this->keeps);
        return
            "Cron Manager for hook {$this->hook} with {$crons} crons and {$filtered} to delete\n\tWill be preserver:\t{$keeps}\n\t"
            . \implode("\n\t",
                \array_map(
                    static fn(CronSchedule $cronSchedule): string => "Will be deleted: {$cronSchedule}",
                    $this->filtered
                )
            );
    }

    /**
     * @return string
     */
    public function getHook(): string
    {
        return $this->hook;
    }

    /**
     * @return array
     */
    public function getKeeps(): array
    {
        return $this->keeps;
    }

}