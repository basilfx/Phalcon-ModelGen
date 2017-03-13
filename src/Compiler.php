<?php

namespace BasilFX\ModelGen;

use BasilFX\ModelGen\Generators;
use BasilFX\ModelGen\Visitor;

use PhpParser;

class Compiler
{
    /**
     * @var array Array of code generators.
     */
    private $generators = null;

    /**
     * @var bool True if each file that is processed should get (re-)compiled.
     */
    private $compileAlways = true;

    /**
     * @var string Path to caching directory.
     */
    private $cacheDirectory = null;

    /**
     * @var string The PHP stream prefix (if used as stream).
     */
    private $prefix = "modelgen";

    /**
     * Generate a new compiler instance.
     */
    public function __construct()
    {
        $this->generators = [
            new Generator\Behaviors(),
            new Generator\Injects(),
            new Generator\Identity(),
            new Generator\Fields(),
            new Generator\Related(),
            new Generator\Table(),
            new Generator\GettersSetters(),
            new Generator\ToArray(),
            new Generator\Validators()
        ];
    }

    /**
     * Compile a single source file.
     */
    public function compile($sourceFile)
    {
        $compiledPath = ensure_slash($this->cacheDirectory) .
            sha1($sourceFile) . ".php";

        if ($this->compileAlways || !is_file($compiledPath)) {
            $this->process($sourceFile, $compiledPath);
        }

        return $compiledPath;
    }

    /**
     * Process a single source file and generate an output file.
     */
    public function process($sourceFile, $compiledFile)
    {
        // Read the source file.
        $code = file_get_contents($sourceFile);
        $parser = (new PhpParser\ParserFactory)->create(
            PhpParser\ParserFactory::PREFER_PHP7
        );

        $statements = $parser->parse($code);

        // Transform the code. Each generator gets a full pass.
        foreach ($this->generators as $generator) {
            $statements = $this->firstPass($statements);
        }

        foreach ($this->generators as $generator) {
            $statements = $this->secondPass($statements, $generator);
        }

        foreach ($this->generators as $generator) {
            $statements = $this->thirdPass($statements, $generator);
        }

        // Generate the new output code.
        $printer = new PhpParser\PrettyPrinter\Standard();
        $code = $printer->prettyPrintFile($statements);

        // Write the code to the output file.
        $compiledDirectory = dirname($compiledFile);

        if (!is_dir($compiledDirectory)) {
            mkdir($compiledDirectory, 0777, true);
        }

        file_put_contents($compiledFile, $code);
    }

    /**
     * Retrieve class information and annotations.
     */
    private function firstPass(&$statements)
    {
        $traverser = new PhpParser\NodeTraverser();
        $traverser->addVisitor(new Visitor\ClassInfo());

        return $traverser->traverse($statements);
    }

    /**
     * Read annotations and add it as metadata to nodes.
     */
    private function secondPass(&$statements, $generator)
    {
        $traverser = new PhpParser\NodeTraverser();
        $traverser->addVisitor(new Visitor\Annotation($generator));

        return $traverser->traverse($statements);
    }

    /**
     * Apply the transformations.
     */
    private function thirdPass(&$statements, $generator)
    {
        $traverser = new PhpParser\NodeTraverser();
        $traverser->addVisitor(new Visitor\Transformer($generator));

        return $traverser->traverse($statements);
    }

    /**
     * Register this compiler instances as a PHP stream. This allowes one to
     * require/include files via 'prefix:///path/to/file.php' (note the three
     * slashes), that will automatically get generated based on this instance
     * settings.
     *
     * @return bool True if the stream was registered successfully.
     */
    public function registerAsStream()
    {
        // A stream is created for each context via PHP internals. The array
        // below is used to pass a compiler to the stream, depending on the
        // prefix of the stream.
        if (isset(Stream::$mappings[$this->prefix])) {
            throw new Exception(
                "Stream with prefix '{$this->prefix}' is already registered."
            );
        }

        Stream::$mappings[$this->prefix] = $this;

        // Register the stream to PHP internals.
        return stream_wrapper_register($this->prefix, Stream::class);
    }

    /**
     * Get the value of cache always.
     *
     * @return bool
     */
    public function getCompileAlways()
    {
        return $this->compileAlways;
    }

    /**
     * Set the value of cache always.
     *
     * @param bool $compileAlways
     */
    public function setCompileAlways($compileAlways)
    {
        $this->compileAlways = $compileAlways;
    }

    /**
     * Get the value of cache directory.
     *
     * @return string
     */
    public function getCacheDirectory()
    {
        return $this->cacheDirectory;
    }

    /**
     * Set the value of cache directory.
     *
     * @param bool $cacheDirectory
     */
    public function setCacheDirectory($cacheDirectory)
    {
        $this->cacheDirectory = $cacheDirectory;
    }

    /**
     * Get the value of stream prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set the value of stream prefix.
     *
     * @param mixed $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }
}
