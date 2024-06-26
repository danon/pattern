<?php
namespace Regex\Internal;

use Regex\Detail;
use Regex\GroupException;
use Regex\Matcher;
use Regex\NoMatchException;

abstract class CompiledPattern
{
    private Pcre $pcre;
    private Groups $groups;
    private string $delimited;

    public function __construct(string $pattern, ExceptionFactory $factory)
    {
        $parsed = new ParsedPattern($pattern);
        if ($parsed->errorMessage) {
            throw $factory->exceptionFor($parsed->errorMessage);
        }
        $this->pcre = new Pcre($pattern);
        $this->groups = new Groups($parsed->groupKeys());
        $this->delimited = $pattern;
    }

    public function test(string $subject): bool
    {
        return $this->pcre->test($subject);
    }

    public function count(string $subject): int
    {
        return $this->pcre->count($subject);
    }

    public function first(string $subject): Detail
    {
        $detail = $this->firstOrNull($subject);
        if ($detail === null) {
            throw new NoMatchException();
        }
        return $detail;
    }

    public function firstOrNull(string $subject): ?Detail
    {
        $match = $this->pcre->matchFirst($subject);
        if (empty($match)) {
            return null;
        }
        return new Detail($match, $subject, $this->groups->keys, 0);
    }

    /**
     * @return string[]
     */
    public function search(string $subject): array
    {
        return $this->pcre->search($subject)[0];
    }

    /**
     * @return string[]|null[]
     */
    public function searchGroup(string $subject, $nameOrIndex): array
    {
        $group = new GroupKey($nameOrIndex);
        if ($this->groups->keys->groupExists($group)) {
            $index = $this->groups->keys->unambiguousIndex($group);
            return $this->pcre->search($subject)[$index];
        }
        throw new GroupException($group, 'does not exist');
    }

    public function match(string $subject): Matcher
    {
        return new Matcher($this->pcre, $subject, $this->groups->keys);
    }

    /**
     * @return Detail[]|\Iterator
     */
    public function matchPartial(string $subject): \Iterator
    {
        [$matches, $exception] = $this->pcre->fullMatchWithException($subject);
        foreach ($matches as $index => $match) {
            yield new Detail($match, $subject, $this->groups->keys, $index);
        }
        if ($exception) {
            throw $exception;
        }
    }

    public function replace(string $subject, string $replacement, int $limit = -1): string
    {
        return $this->pcre->replace($subject, $replacement, $limit)[0];
    }

    public function replaceCount(string $subject, string $replacement, int $limit = -1): array
    {
        return $this->pcre->replace($subject, $replacement, $limit);
    }

    public function replaceGroup(string $subject, $nameOrIndex, int $limit = -1): string
    {
        return $this->pcre->replaceCallback($subject,
            new ReplaceGroup($this->groups->keys, new GroupKey($nameOrIndex)), $limit);
    }

    public function replaceCallback(string $subject, callable $replacer, int $limit = -1): string
    {
        return $this->pcre->replaceCallback($subject,
            new ReplaceFunction($replacer, $subject, $this->groups->keys), $limit);
    }

    /**
     * @return string[]|null[]
     */
    public function split(string $subject, int $maxSplits = -1): array
    {
        if ($maxSplits < 0) {
            return $this->pcre->split($subject, -1);
        }
        return $this->pcre->split($subject, $maxSplits + 1);
    }

    /**
     * @param string[] $subjects
     * @return string[]
     */
    public function filter(array $subjects): array
    {
        return $this->pcre->filter($subjects);
    }

    /**
     * @param string[] $subjects
     * @return string[]
     */
    public function reject(array $subjects): array
    {
        return \array_diff_key($subjects, $this->filter($subjects));
    }

    /**
     * @return string[]|null[]
     */
    public function groupNames(): array
    {
        return $this->groups->names();
    }

    /**
     * @param string|int $nameOrIndex
     */
    public function groupExists($nameOrIndex): bool
    {
        return $this->groups->keys->groupExists(new GroupKey($nameOrIndex));
    }

    public function groupCount(): int
    {
        return $this->groups->count();
    }

    public function delimited(): string
    {
        return $this->delimited;
    }

    public function __toString(): string
    {
        return $this->delimited;
    }
}
