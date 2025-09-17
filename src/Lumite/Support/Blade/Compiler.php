<?php

namespace Lumite\Support\Blade;

class Compiler
{
    /**
     * @var string 
     */
    private string $viewsPath;

    /**
     * @var string 
     */
    private string $cachePath;

    /**
     * @var array 
     */
    private static array $customDirectives = [];

    /**
     * @param string $viewsPath
     * @param string $cachePath
     */
    public function __construct(string $viewsPath, string $cachePath)
    {
        $this->viewsPath = rtrim($viewsPath, '/\\');
        $this->cachePath = rtrim($cachePath, '/\\');
    }

    /**
     * @param string $viewFile
     * @return string
     */
    public function getCompiledPath(string $viewFile): string
    {
        $hash = md5($viewFile . '|' . filemtime($viewFile));
        return $this->cachePath . DIRECTORY_SEPARATOR . $hash . '.php';
    }

    /**
     * @param string $viewFile
     * @param string $compiledFile
     * @return bool
     */
    public function isExpired(string $viewFile, string $compiledFile): bool
    {
        if (!file_exists($compiledFile)) {
            return true;
        }
        return filemtime($viewFile) > filemtime($compiledFile);
    }

    /**
     * @param string $viewFile
     * @return string
     */
    public function compile(string $viewFile): string
    {
        if (!is_dir($this->cachePath)) {
            @mkdir($this->cachePath, 0777, true);
        }

        $compiledPath = $this->getCompiledPath($viewFile);

        if ($this->isExpired($viewFile, $compiledPath)) {
            $contents = file_get_contents($viewFile);
            $compiled = $this->compileString($contents);
            file_put_contents($compiledPath, $compiled);
        }

        return $compiledPath;
    }

    /**
     * @param string $value
     * @return string
     */
    public function compileString(string $value): string
    {
        $value = $this->compileEchos($value);
        $value = $this->compileRawEchos($value);
        $value = $this->compileIfStatements($value);
        $value = $this->compileLoops($value);
        $value = $this->compileLayouts($value);
        $value = $this->compileIncludes($value);
        $value = $this->compilePhp($value);
        $value = $this->compileCsrf($value);
        $value = $this->compileErrors($value);
        $value = $this->compileCustomDirectives($value);
        return $value;
    }

    /**
     * @param string $value
     * @return string
     */
    private function compileEchos(string $value): string
    {
        // Escaped echo: {{ $var }}
        return preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/s', function ($matches) {
            $expr = trim($matches[1]);
            return '<?php echo e(' . $expr . '); ?>';
        }, $value);
    }

    /**
     * @param string $value
     * @return string
     */
    private function compileRawEchos(string $value): string
    {
        // Raw echo: {!! $var !!}
        return preg_replace_callback('/\{!!\s*(.*?)\s*!!\}/s', function ($matches) {
            $expr = trim($matches[1]);
            return '<?php echo ' . $expr . '; ?>';
        }, $value);
    }

    /**
     * @param string $value
     * @return string
     */
    private function compileIfStatements(string $value): string
    {
        $patterns = [
            '/@if\s*\((.*)\)/' => '<?php if ($1): ?>',
            '/@elseif\s*\((.*)\)/' => '<?php elseif ($1): ?>',
            '/@else/' => '<?php else: ?>',
            '/@endif/' => '<?php endif; ?>',
        ];
        return preg_replace(array_keys($patterns), array_values($patterns), $value);
    }

    /**
     * @param string $value
     * @return string
     */
    private function compileLoops(string $value): string
    {
        $patterns = [
            '/@foreach\s*\((.*)\)/' => '<?php foreach ($1): ?>',
            '/@endforeach/' => '<?php endforeach; ?>',
            '/@for\s*\((.*)\)/' => '<?php for ($1): ?>',
            '/@endfor/' => '<?php endfor; ?>',
            '/@while\s*\((.*)\)/' => '<?php while ($1): ?>',
            '/@endwhile/' => '<?php endwhile; ?>',
        ];
        return preg_replace(array_keys($patterns), array_values($patterns), $value);
    }

    /**
     * @param string $value
     * @return string
     */
    private function compileIncludes(string $value): string
    {
        // @include('partials.name', ['x' => 1])
        return preg_replace_callback('/@include\s*\((.*?)\)/', function ($matches) {
            $inside = trim($matches[1]);
            $parts = explode(',', $inside, 2);
            $name = trim($parts[0]);
            $data = isset($parts[1]) ? trim($parts[1]) : '[]';
            return '<?php echo \\Lumite\\Support\\Blade\\Engine::renderInclude(' . $name . ', ' . $data . '); ?>';
        }, $value);
    }

    /**
     * @param string $value
     * @return string
     */
    private function compilePhp(string $value): string
    {
        // @php ... @endphp
        $value = preg_replace('/@php\s*/', '<?php ', $value);
        $value = preg_replace('/@endphp/', ' ?>', $value);
        return $value;
    }

    /**
     * @param string $value
     * @return string
     */
    private function compileCsrf(string $value): string
    {
        // @csrf
        return preg_replace('/@csrf/', '<?php echo csrf_field(); ?>', $value);
    }

    /**
     * @param string $value
     * @return string
     */
    private function compileLayouts(string $value): string
    {
        // @extends('layout.name')
        $value = preg_replace_callback('/@extends\s*\((.*)\)/', function ($m) {
            return '<?php \\Lumite\\Support\\Blade\\Runtime::setExtends(' . $m[1] . '); ?>';
        }, $value);

        // Shorthand: @section('name', expr)
        $value = preg_replace_callback('/@section\s*\(\s*([\'\"][^\'\"]+[\'\"])\s*,\s*(.*)\)/', function ($m) {
            return '<?php \\Lumite\\Support\\Blade\\Runtime::startSection(' . $m[1] . '); ?>'
                . '<?php echo ' . $m[2] . '; ?>'
                . '<?php \\Lumite\\Support\\Blade\\Runtime::endSection(); ?>';
        }, $value);

        // @section('name') ... @endsection
        $value = preg_replace('/@section\s*\((.*)\)/', '<?php \\Lumite\\Support\\Blade\\Runtime::startSection($1); ?>', $value);
        $value = preg_replace('/@endsection/', '<?php \\Lumite\\Support\\Blade\\Runtime::endSection(); ?>', $value);

        // @yield('name')
        $value = preg_replace('/@yield\s*\((.*)\)/', '<?php echo \\Lumite\\Support\\Blade\\Runtime::section($1); ?>', $value);

        // @push('name') ... @endpush and @stack('name')
        $value = preg_replace('/@push\s*\((.*)\)/', '<?php \\Lumite\\Support\\Blade\\Runtime::push($1); ?>', $value);
        $value = preg_replace('/@endpush/', '<?php \\Lumite\\Support\\Blade\\Runtime::endPush(); ?>', $value);
        $value = preg_replace('/@stack\s*\((.*)\)/', '<?php echo \\Lumite\\Support\\Blade\\Runtime::stack($1); ?>', $value);

        return $value;
    }

    /**
     * @param string $value
     * @return string
     */
    private function compileErrors(string $value): string
    {
        // @error('field') ... @enderror
        $value = preg_replace_callback('/@error\s*\((.*)\)/', function ($matches) {
            $field = trim($matches[1], '\'"');
            return '<?php if (has_error(' . $matches[1] . ')): ?>';
        }, $value);
        
        $value = preg_replace('/@enderror/', '<?php endif; ?>', $value);
        
        return $value;
    }

    /**
     * @param string $name
     * @param callable $compiler
     * @return void
     */
    public static function directive(string $name, callable $compiler): void
    {
        self::$customDirectives[$name] = $compiler;
    }

    /**
     * @param string $value
     * @return string
     */
    private function compileCustomDirectives(string $value): string
    {
        if (empty(self::$customDirectives)) {
            return $value;
        }

        foreach (self::$customDirectives as $name => $handler) {
            // @name(expr)
            $patternWithExpr = '/@' . preg_quote($name, '/') . '\s*\((.*)\)/';
            $value = preg_replace_callback($patternWithExpr, function ($matches) use ($handler) {
                $expr = trim($matches[1]);
                return (string) $handler($expr);
            }, $value);

            // @name (no expr)
            $patternNoExpr = '/@' . preg_quote($name, '/') . '(?![\w\(])/';
            $value = preg_replace_callback($patternNoExpr, function () use ($handler) {
                return (string) $handler('');
            }, $value);
        }

        return $value;
    }
    
}


