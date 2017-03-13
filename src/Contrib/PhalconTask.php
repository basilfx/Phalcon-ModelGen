<?php

namespace BasilFX\ModelGen\Contrib;

use Phalcon\Annotations\Adapter as AnnotationsAdapter;
use Phalcon\Cli\Task;

use BasilFX\ModelGen\Exception;

/**
 *
 */
class PhalconTask extends Task
{
    /**
     * @return null
     */
    public function mainAction()
    {
        $count = 0;

        // Enforce compile always.
        $this->modelGen->setCompileAlways(true);

        // Get all paths from the loader.
        $directories = array_flatten(array_merge(
            (array) $this->loader->getDirs(),
            (array) $this->loader->getNamespaces()
        ));

        // Scan each directory for files. Ones that start with the prefix are
        // of interest.
        $prefix = $this->modelGen->getPrefix() . "://";

        foreach ($directories as $directory) {
            if (substr_startswith($directory, $prefix)) {
                $directory = substr($directory, strlen($prefix));

                foreach (glob(ensure_slash($directory) . "*.php") as $file) {
                    echo "Compiling $file\n";
                    $count++;

                    try {
                        $this->modelGen->compile($file);
                    } catch (Exception $e) {
                        echo "Error: {$e->getMessage()}";

                        if ($e->getPrevious()) {
                            echo " ({$e->getPrevious()->getMessage()})";
                        }

                        echo "\n";

                        throw $e;
                    }
                }
            }
        }

        echo "Compiled $count files.\n";
    }
}
