<?php
/**
 *  Abstract Enum class
 *
 *  @author Brian Cline
 *  @link http://stackoverflow.com/a/254543
 */

namespace WPAS;
require_once('BasicEnum.php');

class FormMethod extends BasicEnum {
    const POST = 'POST';
    const GET = 'GET';
}
