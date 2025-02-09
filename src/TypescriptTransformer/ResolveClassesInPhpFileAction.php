<?php

namespace Vod\Vod\TypescriptTransformer;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\SplFileInfo;

class ResolveClassesInPhpFileAction
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    public function execute(SplFileInfo $file): array
    {
        $contents = $file->getContents();
        // Big speed improvement - only parse files that contain the term Vod
        if (! str($contents)->contains(['Vod'])) {
            return [];
        }
        $statements = $this->parser->parse($contents);

        $nodeFinder = new NodeFinder;

        $namespace = $nodeFinder->findFirst(
            $statements,
            fn ($node) => $node instanceof Namespace_
        );

        $classes = $nodeFinder->find(
            $statements,
            fn ($node) => $node instanceof Class_ || $node instanceof Interface_ || $node instanceof Trait_ || $node instanceof Enum_
        );

        return array_map(function (Class_|Interface_|Trait_|Enum_ $item) use ($namespace) {
            $className = $namespace instanceof Namespace_
                ? "{$namespace->name}\\{$item->name}"
                : $item->name;

            return preg_replace('/^\\\*/', '', (string) $className);
        }, $classes);
    }
}
