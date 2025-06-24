#!/usr/bin/env php
<?php
/**
 * Docblock Injector (PHP 8.2 / nikic/php‑parser ≥ 5.0)
 * ---------------------------------------------------
 * Recursively adds PSR‑19‑style docblocks to PHP source files that lack them.
 *
 *  Quick‑start
 *  -----------
 *  composer require --dev nikic/php-parser:^5.0
 *  chmod +x docblock_injector.php
 *  ./docblock_injector.php [PATH] [--dry-run]
 *
 *  PATH     Directory or single file (default: current working dir)
 *  --dry-run  Show would‑be changes without writing files
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PhpParser\{Comment, Node, NodeTraverser, NodeVisitorAbstract, ParserFactory, PrettyPrinter};
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use ArrayIterator;

// --------------------------------------------------
// CLI ARGUMENTS
// --------------------------------------------------
array_shift($argv); // drop script name

$dryRun     = false;
$targetPath = getcwd();

foreach ($argv as $arg) {
    match ($arg) {
        '--dry-run'     => $dryRun = true,
        '-h', '--help'  => helpAndExit(),
        default         => $targetPath = $arg,
    };
}

if (!file_exists($targetPath)) {
    fwrite(STDERR, "❌  Target not found: {$targetPath}\n");
    exit(1);
}

// --------------------------------------------------
// INITIALISE PARSER & PRINTER (php‑parser 5 / PHP 8.2)
// --------------------------------------------------
$parser = (new ParserFactory())->createForHostVersion();
$prettyPrinter = new PrettyPrinter\Standard();

$traverser = new NodeTraverser();
$traverser->addVisitor(new class extends NodeVisitorAbstract {
    public function enterNode(Node $node): null|Node|int
    {
        if ($node instanceof Node\Stmt\Class_        ||
            $node instanceof Node\Stmt\Property      ||
            $node instanceof Node\Stmt\Function_     ||
            $node instanceof Node\Stmt\ClassMethod) {

            // Already documented? Skip.
            if ($node->getDocComment() !== null) {
                return null;
            }

            $node->setDocComment(new Comment\Doc($this->generateDocFor($node)));
        }
        return null;
    }

    // --------------------------------------------------
    // DOCBLOCK GENERATION
    // --------------------------------------------------
    private function generateDocFor(Node $node): string
    {
        $lines = ["/**"];

        if ($node instanceof Node\Stmt\Class_) {
            $lines[] = " * Class {$node->name}";
        } elseif ($node instanceof Node\Stmt\Property) {
            $type = $node->type ? $this->typeToString($node->type) : 'mixed';
            foreach ($node->props as $prop) {
                $lines[] = " * @var {$type} \${$prop->name}";
            }
        } elseif ($node instanceof Node\FunctionLike) {
            foreach ($node->getParams() as $p) {
                $type     = $p->type ? $this->typeToString($p->type) : 'mixed';
                $byRef    = $p->byRef ? '&' : '';
                $variadic = $p->variadic ? '...' : '';
                $lines[]  = sprintf(' * @param %s %s%s$%s', $type, $byRef, $variadic, $p->var->name);
            }
            $return = $node->getReturnType() ? $this->typeToString($node->getReturnType()) : 'void';
            $lines[] = " * @return {$return}";
        }

        $lines[] = " */";
        return implode("\n", $lines);
    }

    // --------------------------------------------------
    // TYPE → STRING (handles union, nullable, intersection)
    // --------------------------------------------------
    private function typeToString(Node\Identifier|Node\Name|Node\ComplexType $type): string
    {
        return match (true) {
            $type instanceof Node\NullableType     => $this->typeToString($type->type) . '|null',
            $type instanceof Node\UnionType        => implode('|', array_map(fn($t) => $this->typeToString($t), $type->types)),
            $type instanceof Node\IntersectionType => implode('&', array_map(fn($t) => $this->typeToString($t), $type->types)),
            $type instanceof Node\Identifier       => $type->toString(),
            $type instanceof Node\Name            => '\\' . $type->toString(),
            default                                => 'mixed',
        };
    }
});

// --------------------------------------------------
// FILE ITERATION
// --------------------------------------------------
$iterator = is_dir($targetPath)
    ? new RegexIterator(
        new RecursiveIteratorIterator(new RecursiveDirectoryIterator($targetPath)),
        '/^.+\.php$/i',
        RegexIterator::GET_MATCH
      )
    : new ArrayIterator([$targetPath]);

foreach ($iterator as $item) {
    $file = is_array($item) ? $item[0] : $item;
    $code = file_get_contents($file);

    try {
        $ast    = $parser->parse($code);
        $newAst = $traverser->traverse($ast);
        // Token‑preserving pretty print (still available in v5)
        $updated = $prettyPrinter->printFormatPreserving(
            $newAst,
            $ast,
            $parser->getTokens()
        );

        if ($updated !== $code) {
            if ($dryRun) {
                echo "[DRY‑RUN] Would update: {$file}\n";
            } else {
                file_put_contents($file, $updated);
                echo "✅  Updated: {$file}\n";
            }
        }
    } catch (Throwable $e) {
        fprintf(STDERR, "⚠️  Error processing %s: %s\n", $file, $e->getMessage());
    }
}

// --------------------------------------------------
// Helpers
// --------------------------------------------------
function helpAndExit(): void
{
    echo <<<'HELP'
Docblock Injector — PHP 8.2 / nikic/php‑parser 5
------------------------------------------------
Adds PSR‑19 docblocks where missing.

Usage:
  ./docblock_injector.php [PATH] [--dry-run]

Options:
  PATH       Directory or file (default: cwd)
  --dry-run  Show changes, don't write files
  -h, --help Print this help
HELP;
    exit(0);
}
