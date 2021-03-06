<?php

namespace Enjoin;

use Enjoin\Mixin\DataTypes;
use Enjoin\Model\Model;
use Doctrine\Common\Inflector\Inflector;
use stdClass, Closure, PdoDebugger;

class Enjoin
{

    use DataTypes;

    # BITWISE:
    const SQL = 1; // TODO: drop it...
    const WITH_CACHE = 2;
    const NO_CACHE = 4;

    private static $debug = false;

    /**
     * @param string $modelName
     * @return \Enjoin\Model\Model
     */
    public static function get($modelName)
    {
        $Factory = Factory::getInstance();
        $definitionClass = static::getModelDefinitionClass($modelName);
        if (isset($Factory->models[$definitionClass])) {
            return $Factory->models[$definitionClass];
        }

        # Register model:
        $Definition = new $definitionClass;
        return $Factory->models[$definitionClass] = $Definition->expanseModel
            ? new $Definition->expanseModel($Definition, $modelName)
            : new Model($Definition, $modelName);
    }

    /**
     * @param string $modelName
     * @return string
     */
    public static function getModelDefinitionClass($modelName)
    {
        return Factory::getConfig()['enjoin']['models_namespace'] .
        '\\' . str_replace('.', '\\', $modelName);
    }

    /**
     * @param null|bool $bool
     * @return bool
     */
    public static function debug($bool = null)
    {
        return is_bool($bool)
            ? static::$debug = $bool
            : static::$debug;
    }

    /**
     * @param \Enjoin\Model\Model $Model
     * @param array $options
     * @return array
     */
    public static function belongsTo(Model $Model, array $options = [])
    {
        return static::performRelation(Extras::BELONGS_TO, $Model, $options);
    }

    /**
     * @param \Enjoin\Model\Model $Model
     * @param array $options
     * @return array
     */
    public static function hasOne(Model $Model, array $options = [])
    {
        return static::performRelation(Extras::HAS_ONE, $Model, $options);
    }

    /**
     * @param \Enjoin\Model\Model $Model
     * @param array $options
     * @return array
     */
    public static function hasMany(Model $Model, array $options = [])
    {
        return static::performRelation(Extras::HAS_MANY, $Model, $options);
    }

    /**
     * @param string $type
     * @param Model $Model
     * @param array $options
     * @return stdClass
     */
    private static function performRelation($type, $Model, array $options = [])
    {
        $as = isset($options['as']) ? $options['as'] : null;

        if (array_key_exists('foreignKey', $options)) {
            $foreignKey = $options['foreignKey'];
        } else {
            $className = get_class($Model->getDefinition());
            if ($pos = strrpos($className, '\\')) {
                $className = substr($className, $pos + 1);
            }
            $foreignKey = Inflector::tableize($className) . '_id';
        }

        $relatedKey = $Model->getUnique();
        !$as ?: $relatedKey .= Extras::GLUE_CHAR . $as;

        $relation = new stdClass;
        $relation->Model = $Model; // required for cache
        $relation->type = $type;
        $relation->as = $as;
        $relation->foreignKey = $foreignKey;
        $relation->relatedKey = $relatedKey;
        return $relation;
    }

    /**
     * @deprecated use `'and' => [...]` instead.
     * @return array
     */
    public static function sqlAnd()
    {
        return ['and' => func_get_args()];
    }

    /**
     * @deprecated use `'or' => [...]` instead.
     * @return array
     */
    public static function sqlOr()
    {
        return ['or' => func_get_args()];
    }

    /**
     * Enable query log for connection.
     */
    public static function enableQueryLog()
    {
        Factory::getConnection()->enableQueryLog();
    }

    /**
     * Disable query log for connection.
     */
    public static function disableQueryLog()
    {
        Factory::getConnection()->disableQueryLog();
    }

    /**
     * Flush query log for connection.
     */
    public static function flushQueryLog()
    {
        Factory::getConnection()->flushQueryLog();
    }

    /**
     * @return array
     */
    public static function getQueryLog()
    {
        return Factory::getConnection()->getQueryLog();
    }

    /**
     * @param Closure $fn
     * @return array
     */
    public static function logify(Closure $fn)
    {
        static::flushQueryLog();
        static::enableQueryLog();
        $fn();
        static::disableQueryLog();
        $log = static::getQueryLog();
        $out = [];
        foreach ($log as $it) {
            $out [] = PdoDebugger::show($it['query'], $it['bindings']);
        }
        return $out;
    }

}
