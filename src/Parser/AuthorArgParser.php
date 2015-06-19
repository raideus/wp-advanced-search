<?php
namespace WPAS\Parser;
use WPAS\StdObject;

class AuthorArgParser extends StdObject implements InputArgParser {

    protected static $defaults = array(
        'label' => '',
        'format' => 'select',
        'authors' => array()
    );

    public static function parse(array $args) {
        $args = self::parseArgs($args, self::$defaults);
        $author_ids = $args['authors'];

        if (count($author_ids) < 1) { // get all authors
            $args['values'] = self::getAllAuthors();
        } else {
            $args['values'] = self::customAuthorsList($author_ids);
        }

        return $args;
    }

    private static function getAllAuthors() {
        $authors_list = array();
        $authors = get_users(array('who' => 'authors'));
        foreach ($authors as $author) {
            $authors_list[$author->ID] = $author->display_name;
        }
        return $authors_list;
    }

    private static function customAuthorsList($author_ids) {
        $authors_list = array();
        foreach ($author_ids as $author) {
            if (get_userdata($author)) {
                $user = get_userdata($author);
                $authors_list[$author] = $user->display_name;
            }
        }
        return $authors_list;
    }

}