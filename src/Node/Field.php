<?php

namespace Gdbots\QueryParser\Node;

use Gdbots\QueryParser\Builder\QueryBuilder;
use Gdbots\QueryParser\Enum\BoolOperator;

final class Field extends Node
{
    const NODE_TYPE = 'field';
    const COMPOUND_NODE = true;

    /**
     * Associative array of ['aliased_field_name' => 'real_field_name'].
     * For example: plays:>100 should actually be: plays_count:>100.
     *
     * @var array
     */
    public static $aliases = [];

    /** @var Node */
    private $node;

    /**
     * Field constructor.
     *
     * @param string $fieldName
     * @param Node $node
     * @param BoolOperator|null $boolOperator
     * @param bool $useBoost
     * @param float|mixed $boost
     *
     * @throws \LogicException
     */
    public function __construct(
        $fieldName,
        Node $node,
        BoolOperator $boolOperator = null,
        $useBoost = false,
        $boost = self::DEFAULT_BOOST
    ) {
        if (isset(self::$aliases[$fieldName])) {
            $fieldName = self::$aliases[$fieldName];
        }

        parent::__construct($fieldName, $boolOperator, $useBoost, $boost);
        $this->node = $node;

        if ($this->node instanceof Field) {
            throw new \LogicException('A Field cannot contain another field.');
        }
    }

    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data = [])
    {
        $value    = isset($data['value']) ? $data['value'] : null;
        $useBoost = isset($data['use_boost']) ? (bool)$data['use_boost'] : false;
        $boost    = isset($data['boost']) ? (float)$data['boost'] : self::DEFAULT_BOOST;

        try {
            $boolOperator = isset($data['bool_operator']) ? BoolOperator::create($data['bool_operator']) : null;
        } catch (\Exception $e) {
            $boolOperator = null;
        }

        /** @var Node $node */
        $node = isset($data['node']) ? self::factory($data['node']) : null;

        return new self($value, $node, $boolOperator, $useBoost, $boost);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();
        $array['node'] = $this->node->toArray();
        return $array;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getValue();
    }

    /**
     * @return Node
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @return bool
     */
    public function hasCompoundNode()
    {
        return $this->node->isCompoundNode();
    }

    /**
     * @param QueryBuilder $builder
     */
    public function acceptBuilder(QueryBuilder $builder)
    {
        $builder->addField($this);
    }
}
