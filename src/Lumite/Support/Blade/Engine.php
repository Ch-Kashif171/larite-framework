<?php

namespace Lumite\Support\Blade;

class Engine
{
    /**
     * @var Compiler 
     */
    private Compiler $compiler;

    /**
     * @var string 
     */
    private string $viewsPath;

    /**
     * @var string 
     */
    private string $cachePath;

    /**
     * @param string $viewsPath
     * @param string $cachePath
     */
    public function __construct(string $viewsPath, string $cachePath)
    {
        $this->viewsPath = rtrim($viewsPath, '/\\');
        $this->cachePath = rtrim($cachePath, '/\\');
        $this->compiler = new Compiler($this->viewsPath, $this->cachePath);
    }

    /**
     * @param string $view
     * @param array $data
     * @param bool $returnString
     * @return mixed
     */
    public function render(string $view, array $data = [], bool $returnString = false): mixed
    {
        return $this->renderInternal($view, $data, $returnString, false);
    }

    /**
     * @param string $view
     * @param array $data
     * @param bool $returnString
     * @param bool $preserveRuntime
     * @return mixed
     */
    private function renderInternal(string $view, array $data, bool $returnString, bool $preserveRuntime): mixed
    {
        if (!$preserveRuntime) {
            Runtime::reset();
        }

        $file = $this->resolveViewPath($view);
        $compiled = $this->compiler->compile($file);

        extract($data, EXTR_SKIP);

        if ($returnString) {
            ob_start();
            include $compiled;
            $layout = \Lumite\Support\Blade\Runtime::getExtends();
            if ($layout) {
                $layoutCompiled = $this->compiler->compile($this->resolveViewPath($layout));
                include $layoutCompiled;
            }
            return ob_get_clean();
        }

        include $compiled;
        $layout = \Lumite\Support\Blade\Runtime::getExtends();
        if ($layout) {
            $layoutCompiled = $this->compiler->compile($this->resolveViewPath($layout));
            include $layoutCompiled;
        }
        return true;
    }

    /**
     * @param string $view
     * @param array $data
     * @return string
     */
    public static function renderInclude(string $view, array $data = []): string
    {
        // Simple include without going through the full engine to avoid circular references
        $path = str_replace('.', DIRECTORY_SEPARATOR, trim($view, '\\/'));
        $full = ROOT_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $path;
        
        if (file_exists($full . '.blade.php')) {
            $compiler = new Compiler(ROOT_PATH . '/views', ROOT_PATH . '/storage/views');
            $compiled = $compiler->compile($full . '.blade.php');
            extract($data, EXTR_SKIP);
            ob_start();
            include $compiled;
            return ob_get_clean();
        }
        
        if (file_exists($full . '.php')) {
            extract($data, EXTR_SKIP);
            ob_start();
            include $full . '.php';
            return ob_get_clean();
        }
        
        throw new \RuntimeException("Include view not found: " . $view);
    }

    /**
     * @param string $view
     * @return string
     */
    private function resolveViewPath(string $view): string
    {
        // dot notation
        $path = str_replace('.', DIRECTORY_SEPARATOR, trim($view, '\\/'));
        $full = ROOT_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $path;

        if (file_exists($full . '.blade.php')) {
            return $full . '.blade.php';
        }
        if (file_exists($full . '.php')) {
            return $full . '.php';
        }

        throw new \RuntimeException("View not found: " . $view);
    }
    
}


