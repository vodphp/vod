<?php

namespace Vod\Vod\Types;

use Closure;
use Illuminate\Support\Facades\Validator;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Vod\Vod\Exceptions\VParseException;
use Vod\Vod\Vod;

/**
 * @template T
 * */
abstract class BaseType
{
    protected array $rules = [];

    protected array $nestedRules = [];

    protected array $after = [];

    protected array $issues = [];

    protected ?BaseType $parent = null;

    protected $default = null;

    protected ?string $globalClassReference = null;

    protected ?string $description = null;

    protected bool $isOptional = false;

    abstract protected function parseValueForType(mixed $value, BaseType $context);

    public function empty()
    {
        return is_null($this->default) ? null : $this->parse($this->default);
    }

    /**
     * @param  BaseType[]  $types
     */
    public function or(...$types)
    {
        return new VUnion([$this, ...$types]);
    }

    /**
     * @param  BaseType[]  $types
     */
    public function and(...$types)
    {
        return new VIntersection([$this, ...$types]);
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return T
     */
    public function parse(mixed $value)
    {
        $this->setParentsRecursively();
        if ($this->isOptional() && $value === null) {
            return $this->empty();
        }
        $results = $this->safeParse($value);
        if (! $results['ok']) {
            $message = '';
            foreach ($results['issues'] as $issue) {
                [$code, $source, $msg] = $issue;
                $message .= $msg.PHP_EOL;
            }
            VParseException::throw($message, $this, $value);
        }

        return $results['value'];
    }

    public function default(mixed $value)
    {
        $this->default = $value;

        return $this;
    }

    /**
     * @return VArray<T>
     */
    public function array(): VArray
    {
        return new VArray($this);
    }

    abstract public function toTypeScript(MissingSymbolsCollection $collection): string;

    public function toPhpType(bool $simple = false): string
    {
        return 'mixed';
    }

    public function exportTypeScript(MissingSymbolsCollection $collection): string
    {

        $ref = $this->globalClassReference;

        if ($ref && $this->parent !== null) {
            return str_replace('\\', '.', $ref);
        }

        return $this->toTypeScript($collection);
    }

    /**
     * @return T
     */
    public function safeParse(mixed $value, string $label = 'value')
    {

        $this->issues = [];

        if ($this->isOptional() && $value === null) {
            return [
                'ok' => true,
                'value' => $this->empty(),
                'issues' => [],
            ];
        }
        try {
            if ($value instanceof Vod && ! $this instanceof VVodClass && ! $this instanceof VVod) {
                $value = $value->value();
            }
            $value = $this->parseValueForType($value, $this);
        } catch (VParseException $e) {
            if ($this->isOptional()) {
                return [
                    'ok' => true,
                    'value' => $this->empty(),
                    'issues' => [],
                ];
            }
            $this->addIssue(0, $this, $e->getMessage());
        }
        if ($this->rules || $this->nestedRules) {
            $rules = $this->rules;
            if ($this->isOptional()) {
                $rules[] = 'nullable';
            }

            $preparedRules = [];
            if ($rules) {
                $preparedRules[$label] = $rules;
            }
            if ($this->nestedRules) {
                foreach ($this->nestedRules as $key => $nestedRules) {
                    $preparedRules[$label.'.'.$key] = $nestedRules;
                }
            }

            $validator = Validator::make([$label => $value], $preparedRules);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {

                    $this->addIssue(0, $this, $error);
                }
            }
        }

        foreach ($this->after as $after) {
            [$method, $closure] = $after;
            $value = $closure($value);
        }
        // @phpstan-ignore-next-line
        if ($this->issues) {
            $issues = $this->issues;
            $this->issues = [];

            return [
                'ok' => false,
                'errors' => $this->summarizeIssues($issues),
                'issues' => $issues,
            ];
        }
        if (is_null($value) && $this->isOptional()) {
            return [
                'ok' => true,
                'value' => $this->empty(),
                'issues' => [],
            ];
        }

        return [
            'ok' => true,
            'value' => $value,
        ];
    }

    public function rules($rules)
    {
        // Check if is associative array
        if (is_array($rules) && array_keys($rules) !== range(0, count($rules) - 1)) {
            $this->nestedRules = $rules;

            return $this;
        }
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        // concat all rules
        $this->rules = array_merge($this->rules, $rules);

        return $this;
    }

    public function summarizeIssues(array $issues)
    {
        $summarized = [];
        foreach ($issues as $issue) {
            [$code, $source, $message] = $issue;
            $summarized[] = $message;
        }

        return implode("\n", $summarized);
    }

    public function isOptional(): bool
    {
        return $this->isOptional;
    }

    public function optional()
    {
        $this->isOptional = true;

        return $this;
    }

    public function required()
    {
        $this->isOptional = false;

        return $this;
    }

    /**
     * @param  class-string  $ref
     */
    public function globalReference(string $ref): self
    {
        $this->globalClassReference = $ref;

        return $this;
    }

    public function setParent(?BaseType $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?BaseType
    {
        return $this->parent;
    }

    protected function addIssue(int $issueCode, BaseType $source, string $message)
    {
        $this->issues[] = [
            $issueCode,
            $source,
            $message,
        ];
    }

    public function transform(Closure $transformer)
    {
        $this->after[] = ['transform', $transformer];

        return $this;
    }

    public function toJsonSchema(): array
    {
        $this->setParentsRecursively();
        $schema = $this->generateJsonSchema();

        if ($this->isOptional()) {
            return [
                'oneOf' => [
                    $schema,
                    ['type' => 'null'],
                ],
            ];
        }

        return $schema;
    }

    abstract protected function generateJsonSchema(): array;

    protected function addDescriptionToSchema(array $schema): array
    {
        if ($this->description !== null) {
            $schema['description'] = $this->description;
        }

        return $schema;
    }

    protected function setParentsRecursively()
    {
        // Implement this method in child classes that have nested types
    }
}
