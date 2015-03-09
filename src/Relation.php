<?php
namespace WPAS;
require_once('BasicEnum.php');

class Relation extends BasicEnum {
    const _AND = 'AND';
    const _OR = 'OR';
    const _default = self::_AND;
}