<?php
namespace WPAS;
require_once('BasicEnum.php');

class Operator extends BasicEnum {
    const _AND = 'AND';
    const _IN = 'IN';
    const _NOTIN = 'NOT IN';
    const _default = self::_AND;
}