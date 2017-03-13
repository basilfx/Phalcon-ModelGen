<?php

namespace BasilFX\ModelGen;

use PhpParser;

class Utils
{
    /**
     * Parse a piece of PHP code and return it as statements.
     */
    public static function parseCode($code)
    {
        $code = "<?php\n" . $code;

        $parser = (new PhpParser\ParserFactory)->create(PhpParser\ParserFactory::PREFER_PHP7);
        $statements = $parser->parse($code);

        return $statements;
    }

    /**
     * Create AST class nodes given a piece of PHP code.
     *
     * @param $code
     * @return array[\PhpParser\Node]
     */
    public static function classMethods($code)
    {
        $code = "class Wrapper { $code }";
        return Utils::parseCode($code)[0]->stmts;
    }

    /**
     * Create AST class nodes given a piece of PHP code and mark each method
     * as unique.
     *
     * @param $code
     * @return array[\PhpParser\Node]
     */
    public static function classMethodsUnique($code)
    {
        $methods = Utils::classMethods($code);

        foreach ($methods as $method) {
            $method->unique = true;
        }

        return $methods;
    }

    /**
     * Create an AST class node given a piece of PHP code.
     *
     * @param $code
     * @return \PhpParser\Node
     */
    public static function classMethod($code)
    {
        return Utils::classMethods($code)[0];
    }

    /**
     * Create an AST class node given a piece of PHP code and mark the method
     * as unique.
     *
     * @param $code
     * @return \PhpParser\Node
     */
    public static function classMethodUnique($code)
    {
        return Utils::classMethodsUnique($code)[0];
    }

    /**
     * Encode an argument for annotation representation.
     *
     * @param array $argument Argument to encode.
     * @param bool $root If true, the argument is not a nested argument list
     *                   (only applicable when $argument is an array).
     * @return string String encoding of $argument.
     */
    public static function encode($argument, $root)
    {
        if (is_string($argument)) {
            return var_export($argument, true);
        } elseif (is_bool($argument)) {
            return $argument ? "true" : "false";
        } elseif (is_array($argument)) {
            $output = [];

            if (is_array_assoc($argument)) {
                foreach ($argument as $key => $value) {
                    $output[] = "$key=" . self::encode($value, false);
                }
            } else {
                foreach ($argument as $value) {
                    $output[] = self::encode($value, false);
                }
            }

            // Convert to string, but braces/parenthesis depend on parameters
            $output = implode(", ", $output);

            if ($root) {
                return "(" . $output . ")";
            } elseif (is_array_assoc($argument)) {
                return "{" . $output . "}";
            } else {
                return "[" . $output . "]";
            }
        } else {
            return $argument;
        }
    }

    /**
     * Encode a annotation, given its name and arguments.
     */
    public static function annotation($name, $arguments = null)
    {
        $result = "@" . $name;

        if ($arguments) {
            $result = $result . self::encode($arguments, true);
        }

        return $result;
    }

    /**
     * Resolve an array of arguments according to a given definitions. If the
     * input arguments are given as associative array, the arguments will be
     * resolved by name. Otherwise, they resolved by index.
     *
     * When resolving by index, an optional positional argument cannot be
     * positioned after a required argument (e.g. [1, 2, foo = bar, 3] is not
     * valid).
     */
    public static function resolveArguments($arguments, $definitions = null)
    {
        // Arguments can be null if directly returned from annotation parser.
        if ($arguments === null) {
            $arguments = [];
        }

        // Allow for empty definitions (e.g. no valid arguments).
        if ($definitions === null) {
            $definitions = [];
        }

        // Separate the arguments into positional and named ones.
        $positional = [];
        $named = [];

        foreach (array_keys($arguments) as $key) {
            if (is_int($key)) {
                if (count($named) > 0) {
                    throw new Exception("Positional argument after named");
                }

                $positional[] = $arguments[$key];
            } else {
                if (!array_key_exists($key, $definitions)) {
                    throw new Exception("Argument '$key' is undefined.");
                }

                $named[$key] = $arguments[$key];
            }
        }

        if (count($positional) > count($definitions)) {
            $actual = count($arguments);
            $expected = count($expected);

            throw new Exception(
                "Too many arguments, got $actual, expected $expected."
            );
        }

        // Prepend the positional arguments.
        $result = array_combine(
            array_slice(array_keys($definitions), 0, count($positional)),
            $positional
        );

        // Then add the named arguments.
        $definitions = array_slice($definitions, count($positional));

        foreach ($definitions as $key => $definition) {
            if (array_key_exists($key, $named)) {
                $result[$key] = $named[$key];
            } else {
                if (array_get($definition, "required")) {
                    throw new Exception("Missing (named) argument '$key'.");
                }

                $result[$key] = array_get($definition, "default");
            }
        }

        return $result;
    }
}
