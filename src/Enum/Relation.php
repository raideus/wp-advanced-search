<?php
namespace WPAS\Enum;

class Relation extends BasicEnum {
    const _AND = 'AND';
    const _OR = 'OR';
    const _default = self::_AND;
}