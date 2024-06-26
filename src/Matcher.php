<?php
namespace Regex;

use Regex\Internal\GroupKeys;
use Regex\Internal\Pcre;

final class Matcher implements \Countable, \IteratorAggregate
{
    private array $matches;
    private string $subject;
    private GroupKeys $groupKeys;

    public function __construct(Pcre $pcre, string $subject, GroupKeys $groupKeys)
    {
        [$this->matches, $exception] = $pcre->fullMatchWithException($subject);
        if ($exception) {
            throw $exception;
        }
        $this->subject = $subject;
        $this->groupKeys = $groupKeys;
    }

    public function test(): bool
    {
        return !empty($this->matches);
    }

    public function first(): Detail
    {
        if (empty($this->matches)) {
            throw new NoMatchException();
        }
        return new Detail($this->matches[0], $this->subject, $this->groupKeys, 0);
    }

    public function firstOrNull(): ?Detail
    {
        if (empty($this->matches)) {
            return null;
        }
        return new Detail($this->matches[0], $this->subject, $this->groupKeys, 0);
    }

    public function count(): int
    {
        return \count($this->matches);
    }

    /**
     * @return Detail[]
     */
    public function all(): array
    {
        $details = [];
        foreach ($this->matches as $index => $match) {
            $details[] = new Detail($match, $this->subject, $this->groupKeys, $index);
        }
        return $details;
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->all());
    }
}
