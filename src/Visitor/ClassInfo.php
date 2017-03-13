<?php

namespace BasilFX\ModelGen\Visitor;

use PhpParser;

/**
 *
 */
class ClassInfo extends PhpParser\NodeVisitorAbstract
{
    /**
     * @var mixed
     */
    private $meta = null;

    /**
     * @param PhpParser\Node $node
     * @return mixed
     */
    public function enterNode(PhpParser\Node $node)
    {
        // Not sure why, but a Property has props that have names. I cannot
        // find an example of where that is possible. Therefore add the name
        // property directly to the property so we can ignore the props
        // attribute.
        if ($node instanceof PhpParser\Node\Stmt\Property) {
            $node->name = $node->props[0]->name;
        }

        // Add a meta data property to each node.
        if ($node instanceof PhpParser\Node\Stmt\Class_) {
            $this->meta = [
                "name" => $node->name,
                "methods" => [],
                "properties" => []
            ];
            $node->meta = &$this->meta;
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassMethod) {
            $this->meta["methods"][$node->name] = [
                "name" => $node->name,
                "type" => $node->type,
                "parameters" => count($node->params)
            ];
            $node->meta = &$this->meta["methods"][$node->name];
        } elseif ($node instanceof PhpParser\Node\Stmt\Property) {
            $this->meta["properties"][$node->name] = [
                "name" => $node->name,
                "type" => $node->type
            ];
            $node->meta = &$this->meta["properties"][$node->name];
        }

        return $node;
    }
}
