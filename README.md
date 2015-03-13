# WP Advanced Search v1.4 (beta)

[![Build Status](https://travis-ci.org/growthspark/wp-advanced-search.svg?branch=1.4-beta)](https://travis-ci.org/growthspark/wp-advanced-search)

This is a beta version of an upcoming release.  All features may not be stable.
Use at your own risk!

**Requirements:** PHP version 5.3 or higher, WordPress version 4.1 or higher

## Installation

### As a plugin

WP Advanced Search can be configured as a 'must-use' plugin in the 'mu-plugins'
directory.

1. Clone or copy this repository to a folder named 'wp-advanced-search' inside /wp-content/mu-plugins
2. Create a file 'load.php' in 'wp-content/mu-plugins/' and add the following:

`<?php`
`require_once WPMU_PLUGIN_DIR.'/wp-advanced-search/wpas.php';`

### As a theme extension

1. Clone or copy the repository to a folder named 'wp-advanced-search' inside your theme directory.

2. Add the following to your theme's functions.php file:

` require_once('wp-advanced-search/wpas.php'); `

## Usage

See the included demo template under /demo/wp-advanced-search-demo.php for an example of how to create your own forms.

