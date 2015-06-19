<?php
namespace WPAS;
use WPAS\Enum\FieldType;
use WPAS\Enum\InputFormat;
require_once(dirname(__DIR__) . '/wpas.php');

class TestInputBuilder extends \WP_UnitTestCase {

    function setUp() {
        parent::setUp();
	    _clean_term_filters();
	    wp_cache_delete( 'last_changed', 'terms' );
    }

    public function testCanBuildSearch() {
        $args = array('field_type' => 'search',
                        'label' => 'Search',
                        'id' => 'search-id',
                        'format' => 'text',
                        'class' => array('testclass'),
                        'name' => 'my_search',
                        'attributes' => array('data-src' => 12345,
                                              'data-color' => 'red',
                                              'min' => 0,
                                              'max' => 100),
                        'default' => 'something');

        $input = InputBuilder::make('search_query', FieldType::search, $args);
        $this->assertTrue($input instanceof Input);

        $args = array('field_type' => 'search');
        $input = InputBuilder::make('search_query', FieldType::search, $args);
        $this->assertTrue($input instanceof Input);
        $this->assertTrue($input->getFormat() == InputFormat::text);
    }

    public function testCanBuildSubmit() {
        $args = array('field_type' => 'submit');
        $input = InputBuilder::make('submit', FieldType::submit, $args);
        $this->assertTrue($input instanceof Input);
        $this->assertTrue($input->getFormat() == InputFormat::submit);

        $args = array('field_type' => 'submit', 'values' => array("Go!"));
        $input = InputBuilder::make('submit', FieldType::submit,  $args);
        $this->assertTrue($input instanceof Input);
        $values = $input->getValues();
        $this->assertTrue(is_array($values) && $values[0] == "Go!");
    }

    public function testCanBuildTaxonomy() {
        $args = array(
                    'field_type' => 'taxonomy',
                    'taxonomy' => 'category',
                    'format' => 'select'
                );
        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args);
        $this->assertTrue($input instanceof Input);
        $this->assertFalse($input->isNested());

        $args = array(
            'field_type' => 'taxonomy',
            'taxonomy' => 'category',
            'format' => 'select',
            'nested' => true
        );
        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args);
        $this->assertTrue($input->isNested());
    }

    public function testTaxonomyAutoGenTerms() {

        $t = array();
        $t[] = $this->factory->category->create_and_get(array('name' =>  "Category One"));
        $t[] = $this->factory->category->create_and_get(array('name' =>  "Category Two"));
        $t[] = $this->factory->category->create_and_get(array('name' =>  "Z Category"));

        $args = array(
            'field_type' => 'taxonomy',
            'taxonomy' => 'category',
            'format' => 'select',
            'term_format' => 'slug'
        );
        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args);

        $values = $input->getValues();
        $this->assertTrue(count($values) == 4);

        // Test term_format = 'slug'
        $first_label = reset($values);
        $first_value = key($values);
        $this->assertTrue($first_label == $t[0]->name);
        $this->assertTrue($first_value == $t[0]->slug);

        // Test term_format = 'id'
        $args['term_format'] = 'id';
        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args);
        $values = $input->getValues();
        $first_value = key($values);
        $this->assertTrue(key($input->getValues()) == $t[0]->term_id);

        // Test term_format = 'name'
        $args['term_format'] = 'name';
        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args);
        $values = $input->getValues();
        $first_value = key($values);
        $this->assertTrue(key($input->getValues()) == $t[0]->name);

        // Test term_args
        $args['term_args'] = array('orderby' => 'name', 'order' => 'DESC');
        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args);
        $values = $input->getValues();
        $this->assertTrue(key($input->getValues()) == $t[2]->name);

        // Test exclude
        $args['exclude'] = array('category-one');
        $args['term_format'] = 'slug';
        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args);
        $values = $input->getValues();
        $this->assertTrue(count($values) == 3);
        $this->assertFalse(isset($values[$args['exclude'][0]]));
    }

    public function testTaxonomyNestedTerms() {
        $t = array();
        $t[] = $this->factory->category->create_and_get(array('name' =>  "Category One"));
        $t[] = $this->factory->category->create_and_get(array('name' => "Category Two", 'parent' => $t[0]->term_id));
        $t[] = $this->factory->category->create_and_get(array('name' =>  "Z Category", 'parent' => $t[0]->term_id));

        $args = array(
            'field_type' => 'taxonomy',
            'taxonomy' => 'category',
            'format' => 'select',
            'nested' => 'true',
            'term_format' => 'slug'
        );

        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args);
        $values = $input->getValues();
        $value = reset($values);
        $this->assertTrue(count($value['children']) == 2);

        $args['format'] = 'multi-select';
        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args);
        $values = $input->getValues();
        $value = reset($values);
        $this->assertTrue(count($value['children']) == 2);
        $input->toHTML();

        $args['format'] = 'radio';
        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args);
        $values = $input->getValues();
        $value = reset($values);
        $this->assertTrue(count($value['children']) == 2);
        $input->toHTML();

        $args['format'] = 'checkbox';
        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args);
        $values = $input->getValues();
        $value = reset($values);
        $this->assertTrue(count($value['children']) == 2);
        $input->toHTML();

    }

    public function testExcludeWithNestedTaxonomy() {

        $t = array();
        $t[] = $this->factory->category->create_and_get(array('name' =>  "Category One"));
        $t[] = $this->factory->category->create_and_get(array('name' => "Category Two", 'parent' => $t[0]->term_id));
        $t[] = $this->factory->category->create_and_get(array('name' =>  "Z Category", 'parent' => $t[0]->term_id));

        $args = array(
            'field_type' => 'taxonomy',
            'taxonomy' => 'category',
            'format' => 'select',
            'nested' => 'true',
            'exclude' => array('category-one'), // By excluding category-one, its two
                                                // children should also be excluded
            'term_format' => 'slug'
        );

        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args);
        $values = $input->getValues();
        $this->assertTrue(count($values) == 1); // count = 1 due to "Uncategorized"
    }

    public function testTaxonomyWithManualTermsList() {
        $t = array();
        $t[] = $this->factory->category->create_and_get(array('name' =>  "Category One"));
        $t[] = $this->factory->category->create_and_get(array('name' => "Category Two", 'parent' => $t[0]->term_id));
        $t[] = $this->factory->category->create_and_get(array('name' =>  "Z Category", 'parent' => $t[0]->term_id));
        $t[] = $this->factory->category->create_and_get(array('name' =>  "Y Category", 'parent' => $t[0]->term_id));

        $args = array(
            'field_type' => 'taxonomy',
            'taxonomy' => 'category',
            'format' => 'select',
            'terms' => array($t[0]->term_id, $t[1]->term_id, $t[2]->term_id),
            'term_format' => 'ID'
        );

        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args);
        $values = $input->getValues();

        $this->assertTrue(count($values) == 3);
    }


    public function testSetSelectedValues() {
        $t = array();
        $t[] = $this->factory->category->create_and_get(array('name' =>  "Category One"));
        $t[] = $this->factory->category->create_and_get(array('name' =>  "Category Two"));
        $t[] = $this->factory->category->create_and_get(array('name' =>  "Z Category"));

        $request = new HttpRequest(array('tax_category' => array('category-two')));
        $args = array(
            'field_type' => 'taxonomy',
            'taxonomy' => 'category',
            'format' => 'select',
            'term_format' => 'slug'
        );
        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args, $request);

        // Selected value is present
        $selected = $input->getSelected();
        $this->assertTrue(count($selected) == 1);
        $this->assertTrue($selected[0] == 'category-two');

        // Default should be overridden by request value
        $args['default'] = 'category-one';
        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args,
            $request);
        $selected = $input->getSelected();
        $this->assertTrue(count($selected) == 1);
        $this->assertTrue($selected[0] == 'category-two');

        // Default should be selected with empty request
        $request = array();
        $args['default'] = 'category-one';
        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args,
                                    $request);
        $selected = $input->getSelected();
        $this->assertTrue(count($selected) == 1);
        $this->assertTrue($selected[0] == 'category-one');


        // Check default_all
        $args['default_all'] = true;
        $args['format'] = 'multi-select';
        $input = InputBuilder::make('tax_category', FieldType::taxonomy, $args,
                                    $request);
        $selected = $input->getSelected();
        $this->assertTrue(count($selected) == 4);
    }

    public function testGenericFieldSelected() {
        $inputname = 'myfield';
        $request = new HttpRequest(array($inputname => array('blue','green')));

        $args = array(
            'field_type' => 'generic',
            'format' => 'checkbox',
            'values' => array('red','blue','green')
        );
        $input = InputBuilder::make($inputname, FieldType::generic, $args,
                                    $request);
        $this->assertTrue(count($input->getSelected()) == 2);
    }

    public function testAuthor() {
        $queried = array(2,4);
        $request = new HttpRequest(array('a' => array(2,4)));

        $users = array();
        $users[] = $this->factory->user->create_and_get(array('display_name'=>'Sean', 'role' => 'administrator')); // id=2
        $users[] = $this->factory->user->create_and_get(array('display_name'=>'Dave', 'role' => 'editor')); // id=3
        $users[] = $this->factory->user->create_and_get(array('display_name'=>'Alex', 'role' => 'author')); // id=4
        $users[] = $this->factory->user->create_and_get(array('display_name'=>'Jim', 'role' => 'subscriber')); // id=5
        $users[] = $this->factory->user->create_and_get(array('display_name'=>'Rob', 'role' => 'contributor')); // id=6

        $values = array();
        foreach($users as $user) {
            $values[] = $user->user_id;
        }

        $args = array(
            'field_type' => 'author',
            'format' => 'checkbox',
            'values' => $values
        );

        $input = InputBuilder::make('a', FieldType::author, $args, $request);
        $values = $input->getValues();
        $selected = $input->getSelected();

        $this->assertTrue(count($values) == 5); // 5 authors including "admin"
        $this->assertFalse(isset($values[5])); // User 'Jim' should not be included

        $this->assertTrue(count($selected) == 2);
        $this->assertContains(2, $selected);
        $this->assertContains(4,$selected);
    }




}