<?php
namespace Test\Unit\_system;

use PHPUnit\Framework\TestCase;
use Regex\Pattern;
use Regex\ExecutionException;
use function Test\Fixture\Functions\catching;
use function Test\Fixture\Functions\systemErrorHandler;

class _errorHandler extends TestCase
{
    private Pattern $pattern;

    /**
     * @before
     */
    public function pattern()
    {
        $this->pattern = new Pattern('(?=word\K)');
    }

    /**
     * @test
     */
    public function first()
    {
        systemErrorHandler(function (): void {
            catching(fn() => $this->pattern->first('word'))
                ->assertException(ExecutionException::class);
        });
    }

    /**
     * @test
     */
    public function search()
    {
        systemErrorHandler(function (): void {
            catching(fn() => $this->pattern->search('word'))
                ->assertException(ExecutionException::class);
        });
    }

    /**
     * @test
     */
    public function match()
    {
        systemErrorHandler(function (): void {
            catching(fn() => $this->pattern->match('word'))
                ->assertException(ExecutionException::class);
        });
    }
}
