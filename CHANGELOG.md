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

[0.0.5]

##Add
- Added filter display, working for taxonomies, search and meta_key fields. Also added functionality to delete each filter and run an updated AJAX request.

##Edit
- Edited display post results within each taxonomy input to include meta fields if exist.
- Edited display post results within each taxonomy input to include additional tax_queries if defined on the search form.

###Modified files
- lib.php
- wpas.php -> Added get_args() function so we could retrieve the args set on the form and include tax_queries if they exist
- js/scripts.js


[0.0.6]

##Add
- Added functionality to make meta queries on several meta keys.
- Added option to include a custom img to close the filters boxes. If none is defined, a svg formatted cross will appear.
- Added option to hide taxonomy elements if count is 0

##Fix 
- Fixed some checkboxes unchecking when closing filters. (If there were two or more checkboxes with the same value, they would all uncheck).

###Modified files
- js/scripts.js
- src/Field.php
- src/MetaQuery.php
- src/Form.php
- src/AjaxConfig.php
- src/Input.php
