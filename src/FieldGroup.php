<?php
namespace WPAS;
use WPAS\Enum\Relation;

class FieldGroup extends StdObject {
    private $fields;
    private $relation;

    protected static $defaults = array(
        'fields' => array(),
        'relation' => Relation::_AND
    );

    protected static $rules = array(
        'fields' => 'array<Field>',
        'relation' => 'Relation'
    );

    public function __contruct(array $args = array()) {
        $args = self::validate($args);
        $this->fields = $args['fields'];
        $this->relation = $args['relation'];
    }

    public function addField(Field $field) {
        $this->fields[] = $field;
    }

    public function setRelation($relation) {
        $this->relation = $relation;
    }

    public function getFields() {
        return $this->fields;
    }

    public function getRelation() {
        return $this->relation;
    }

}