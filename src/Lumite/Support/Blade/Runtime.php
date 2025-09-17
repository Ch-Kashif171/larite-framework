<?php

namespace Lumite\Support\Blade;

class Runtime
{
    /**
     * @var string|null
     */
    private static ?string $extends = null;

    /**
     * @var array
     */
    private static array $sections = [];

    /**
     * @var array
     */
    private static array $sectionStack = [];

    /**
     * @var array
     */
    private static array $stacks = [];

    /**
     * @var array
     */
    private static array $pushStack = [];

    /**
     * @return void
     */
    public static function reset(): void
    {
        self::$extends = null;
        self::$sections = [];
        self::$sectionStack = [];
        self::$stacks = [];
        self::$pushStack = [];
    }

    /**
     * @param string $view
     * @return void
     */
    public static function setExtends(string $view): void
    {
        self::$extends = trim($view, "'\"");
    }

    /**
     * @return string|null
     */
    public static function getExtends(): ?string
    {
        return self::$extends;
    }

    /**
     * @param string $name
     * @return void
     */
    public static function startSection(string $name): void
    {
        self::$sectionStack[] = $name;
        ob_start();
    }

    /**
     * @return void
     */
    public static function endSection(): void
    {
        $content = ob_get_clean();
        $name = array_pop(self::$sectionStack);
        if ($name !== null) {
            self::$sections[$name] = $content;
        }
    }

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public static function section(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param string $content
     * @return void
     */
    public static function setSection(string $name, string $content): void
    {
        self::$sections[$name] = $content;
    }

    /**
     * @param string $name
     * @return void
     */
    public static function push(string $name): void
    {
        self::$pushStack[] = $name;
        ob_start();
    }

    public static function endPush(): void
    {
        $content = ob_get_clean();
        $name = array_pop(self::$pushStack);
        if ($name !== null) {
            self::$stacks[$name][] = $content;
        }
    }

    /**
     * @param string $name
     * @return string
     */
    public static function stack(string $name): string
    {
        if (!isset(self::$stacks[$name])) {
            return '';
        }
        return implode('', self::$stacks[$name]);
    }
    
}


