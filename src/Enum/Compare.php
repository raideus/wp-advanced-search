<?php
namespace WPAS\Enum;

class Compare extends BasicEnum {
    const equal = '=';
    const notequal = '!=';
    const greater = '>';
    const greq = '>=';
    const less = '<';
    const leq ='<=';
    const like = 'LIKE';
    const notlike = 'NOT LIKE';
    const in = 'IN';
    const notin = 'NOT IN';
    const between = 'BETWEEN';
    const notbetween = 'NOT BETWEEN';
    const exists = 'EXISTS';
    const notexists = 'NOT EXISTS';
    const _default = self::equal;
}