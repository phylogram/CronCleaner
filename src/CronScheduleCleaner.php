<?php
declare(strict_types=1);


namespace phylogram\CronScheduleCleaning;


class CronScheduleCleaner
{
    /**
     * @var CronScheduleManager[]
     */
    private array $dropables;

    /**
     * CronScheduleCleaner constructor.
     * @param string $pattern look for in actions
     * @param bool $strict
     */
    public function __construct(string $pattern, bool $strict)
    {
        $crons = _get_cron_array();
        $filtered = [];
        foreach ($crons as $timestamp => $hooks) {
            foreach ($hooks as $hook => $crons) {
                if (\preg_match($pattern, $hook)) {
                    foreach ($crons as $idx => $cron_definition) {
                        $filtered[$hook][] = new CronSchedule((int) $timestamp, $hook, $cron_definition, $strict);
                    }
                }
            }
        }

        $filtered = \array_filter(
            $filtered,
            static fn (array $schedules): bool => \count($schedules) !== 1
        );

        $this->dropables = \array_map(
            static fn ($hook, array $crons): CronScheduleManager => new CronScheduleManager($crons, $hook),
            \array_keys($filtered),
            $filtered
        );

    }

    /**
     * @return CronScheduleManager[]
     */
    public function getDropables(): array
    {
        return $this->dropables;
    }

    public function removeDropables(): bool
    {
        $drops = \array_map(
            static fn (CronScheduleManager $cronScheduleManager): bool => $cronScheduleManager->drop(),
            $this->dropables
        );
        $result = \count(\array_filter($drops)) === \count($drops);
        echo "\n" . \json_encode($result) . "\n";
        return $result;
    }

    public function isCorrect(): bool
    {
        foreach ($this->dropables as $dropable) {
            if ( ! $dropable->isCorrect()) {
                return false;
            }
        }
        return true;
    }

    public function __toString()
    {
        $correct = \json_encode($this->isCorrect());
        return "Cleaner Status: {$correct}\n\n" . implode("\n------------\n", $this->dropables);
    }


}