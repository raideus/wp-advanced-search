WP Advanced Search
==================

[0.0.1]

##Initial commit

[0.0.2]

##Add 
- Added function _the_form_shortcodes()_ to make the form work properly within shortcodes.

###Modified files
* wpas.php

## Add
- Implemented functionality to show the number of results for each search via AJAX. (Needs an element with id wpas-no-of-results).

###Modified files
- lib.php
- js/scripts.js

[0.0.3]

## Add
- Added span to display the number of results for each input.

###Modified files
- src/InputMarkup.php

[0.0.4]

##Add
- Added function to display post count on each taxonomy input.
- Added parameter show_results to display the number of results within each input label

###Modified files
- src/Input.php
- src/InputMarkup.php
- src/Field.php
- lib.php
- wpas.php
- js/scripts.js

