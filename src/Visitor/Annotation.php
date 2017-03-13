<?php

namespace BasilFX\ModelGen\Visitor;

use BasilFX\ModelGen\Exception;
use BasilFX\ModelGen\AbstractGenerator;
use Phalcon\Annotations\Collection as AnnotationsCollection;
use Phalcon\Annotations\Reader as AnnotationsReader;
use PhpParser;

/**
 *
 */
class Annotation extends PhpParser\NodeVisitorAbstract
{

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
     */
    public function enterNode(PhpParser\Node $node)
    {
        try {
            if ($node instanceof PhpParser\Node\Stmt\Class_) {
                $this->parseClass($node);
            } elseif ($node instanceof PhpParser\Node\Stmt\ClassMethod) {
                $this->parseClassMethod($node);
            } elseif ($node instanceof PhpParser\Node\Stmt\Property) {
                $this->parseProperty($node);
            }
        } catch (Exception $e) {
            throw new Exception("Error while parsing annotations of line {$node->getLine()}", null, $e);
        }

        return $node;
    }

    /**
     * @param $node
     * @return mixed
     */
    public function parseAnnotations(&$node)
    {
        // Retrieve comment from node.
        $comment = $node->getDocComment();

        if (!$comment) {
            return [];
        }

        // Convert comment to array of annotations.
        $annotations = AnnotationsReader::parseDocBlock($comment->getText());

        if (!$annotations) {
            return [];
        }

        // Parse annotations from array.
        $annotations = new AnnotationsCollection($annotations);

        if (!$annotations) {
            return [];
        }

        return $annotations;
    }

    /**
     * Copy new contents if meta has changed. We do this to not break the
     * reference to the meta property by the class.
     */
    public function mergeMeta(PhpParser\Node $node, array $meta)
    {
        foreach ($meta as $key => $value) {
            $node->meta[$key] = $value;
        }
    }

    /**
     * @param PhpParser\Node\Stmt\Class_ $node
     * @return null
     */
    public function parseClass(PhpParser\Node\Stmt\Class_ &$node)
    {
        $annotations = $this->parseAnnotations($node);

        $meta = $this->generator->parseClassAnnotations(
            $annotations,
            $node->meta
        );

        if ($meta) {
            $this->mergeMeta($node, $meta);
        }
    }

    /**
     * @param PhpParser\Node\Stmt\ClassMethod $node
     */
    public function parseClassMethod(PhpParser\Node\Stmt\ClassMethod &$node)
    {
        $annotations = $this->parseAnnotations($node);

        $meta = $this->generator->parseClassMethodAnnotations(
            $annotations,
            $node->meta
        );

        if ($meta) {
            $this->mergeMeta($node, $meta);
        }
    }

    /**
     * @param PhpParser\Node\Stmt\Property $node
     * @return null
     */
    public function parseProperty(PhpParser\Node\Stmt\Property &$node)
    {
        $annotations = $this->parseAnnotations($node);

        $meta = $this->generator->parsePropertyAnnotations(
            $annotations,
            $node->meta
        );

        if ($meta) {
            $this->mergeMeta($node, $meta);
        }
    }
}
