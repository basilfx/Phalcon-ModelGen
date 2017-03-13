<?php

namespace BasilFX\ModelGen\Visitor;

use BasilFX\ModelGen\Exception;
use BasilFX\ModelGen\AbstractGenerator;
use BasilFX\ModelGen\Generators;
use PhpParser;

/**
 *
 */
class Transformer extends PhpParser\NodeVisitorAbstract
{
    /**
     * @var mixed
     */
    private $class = null;

    /**
     * @var mixed
     */
    private $generator = null;

    /**
     * @param AbstractGenerator $generator
     */
    public function __construct(AbstractGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @param PhpParser\Node $node
     * @return mixed
     */
    public function enterNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Stmt\Class_) {
            $this->class = &$node;

            $node->meta["generated"] = [
                "methods" => [],
                "properties" => [],
                "annotations" => []
            ];
            $this->transformClass($node);
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassMethod) {
            $node->meta["generated"] = [
                "annotations" => []
            ];
            $this->transformClassMethod($node);
        } elseif ($node instanceof PhpParser\Node\Stmt\Property) {
            $node->meta["generated"] = [
                "annotations" => []
            ];
            $this->transformProperty($node);
        }

        return $node;
    }

    /**
     * @param PhpParser\Node $node
     * @return mixed
     */
    public function leaveNode(PhpParser\Node $node)
    {
        $classMeta = &$this->class->meta;

        // Related to annotations.
        if (isset($node->meta)) {
            if ($node->meta["generated"]["annotations"]) {
                $annotations = [];

                foreach ($node->meta["generated"]["annotations"] as $annotation) {
                    $annotations[] = " * " . $annotation;
                }

                // Inject the new comment inside.
                $comment = $node->getDocComment();

                $start = strpos($comment->getText(), "/**");
                $stop = strpos($comment->getText(), "*/");

                if ($start >= 0 && $stop >= 0) {
                    $comment->setText(
                        substr($comment->getText(), 0, $stop) . "\n" . implode("\n", $annotations) . "\n */"
                    );
                } else {
                    $comment->setText("/**\n" . implode("\n", $annotations) . "\n */");
                }
            }
        }

        // Related to methods and properties.
        if ($node instanceof PhpParser\Node\Stmt\Class_) {
            $generatedMethods = &$classMeta["generated"]["methods"];
            $generatedProperties = &$classMeta["generated"]["properties"];

            // Add generated methods.
            foreach ($generatedMethods as $methodName => &$methods) {
                // Check if a wrapper method is needed to invoke all
                // implementations with the same name.
                if (count($methods) > 1) {
                    foreach ($methods as $method) {
                        // Check if method should be unique. This is definately
                        // not the case if execution reaches here.
                        if (isset($method->unique)) {
                            throw new Exception(
                                "Multiple methods generated for " .
                                "'$methodName', but one was marked as unique."
                            );
                        }

                        // Give the method an unique name.
                        $method->name = $method->name . bin2hex(random_bytes(8));
                    }

                    $factory = new PhpParser\BuilderFactory();
                    $builder = $factory->method($methodName)->makePublic();

                    foreach ($methods as $method) {
                        $builder->addStmt(
                            new PhpParser\Node\Expr\MethodCall(
                                new PhpParser\Node\Expr\Variable("this"),
                                $method->name
                            )
                        );

                        // Convert the method to private.
                        $method->type = $method->type & ~(
                            PhpParser\Node\Stmt\Class_::MODIFIER_PRIVATE |
                            PhpParser\Node\Stmt\Class_::MODIFIER_PROTECTED |
                            PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC
                        );
                        $method->type = $method->type | PhpParser\Node\Stmt\Class_::MODIFIER_PRIVATE;
                    }

                    $methods[] = $builder->getNode();
                }

                // Add all generated methods to the class.
                $node->stmts = array_merge($node->stmts, $methods);
            }

            // Add generated properties.
            foreach ($generatedProperties as $property) {
                $node->stmts[] = $property;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassMethod) {
            // Check if an existing method is duplicated. In that case, remove
            // that method and act if it was generated. It will be handled
            // correctly when all generated methods are implemented.
            if (isset($classMeta["generated"]["methods"][$node->name])) {
                $this->addMethods([$node]);

                return PhpParser\NodeTraverser::REMOVE_NODE;
            }
        }

        return $node;
    }

    /**
     * @param $node
     */
    public function transformClass(&$node)
    {
        $methods = $this->generator->getClassMethods($node->meta);

        if ($methods) {
            $this->addMethods($methods);
        }

        $properties = $this->generator->getProperties($node->meta);

        if ($properties) {
            $this->addProperties($properties);
        }

        $annotations = $this->generator->getClassAnnotations($node->meta);

        if ($annotations) {
            $this->addAnnotations($node, $annotations);
        }
    }

    /**
     * @param $node
     */
    public function transformClassMethod(&$node)
    {
        $annotations = $this->generator->getClassMethodAnnotations($node->meta);

        if ($annotations) {
            $this->addAnnotations($node, $annotations);
        }
    }

    /**
     * @param $node
     */
    public function transformProperty(&$node)
    {
        $annotations = $this->generator->getPropertyAnnotations($node->meta);

        if ($annotations) {
            $this->addAnnotations($node, $annotations);
        }
    }

    /**
     * @param $methods
     */
    private function addMethods($methods)
    {
        $meta = &$this->class->meta;

        foreach ($methods as $method) {
            // Verify if it is a method.
            if (!($method instanceof PhpParser\Node\Stmt\ClassMethod)) {
                throw new Exception("Method is not a class method.");
            }

            // Add method, grouped by name.
            if (!isset($meta["generated"]["methods"][$method->name])) {
                $meta["generated"]["methods"][$method->name] = [];
            }

            $meta["generated"]["methods"][$method->name][] = $method;
        }
    }

    /**
     * @param $properties
     */
    private function addProperties($properties)
    {
        $meta = &$this->class->meta;

        foreach ($properties as $property) {
            // Verify if it is a property.
            if (!($property instanceof PhpParser\Node\Stmt\Property)) {
                throw new Exception("Property is not a class property.");
            }

            // Check if it does not override an existing property.
            if (isset($meta["generated"]["properties"][$property->name])) {
                throw new Exception("Cannot redeclare property '{$property->name}'.");
            }

            $meta["generated"]["properties"][$property->name] = $property;
        }
    }

    /**
     * @param $node
     * @param $annotations
     */
    private function addAnnotations($node, $annotations)
    {
        $meta = &$node->meta;

        foreach ($annotations as $annotation) {
            $meta["generated"]["annotations"][] = $annotation;
        }
    }
}
