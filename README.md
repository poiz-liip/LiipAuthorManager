# LiipAuthorManager

A plugin that enables Wordpress Site Administrators or Editors to create Virtual Authors (without a need for Author E-Mail). 

This Solution aims to be as simple yet as functional as possible.
At its best, the Plugin simply exposes a Custom Post Type (herein: *managed_authors*).

This Plugin uses only one Field: Wordpress' *post_title* Field.
This Field is used to gather the first & last Names of the newly added Author with space as the delimiter. 
During the saving of the Post, the Author is automatically saved as a normal **Wordpress User (with the Role of an Author)**.

This makes it easy to create an Author in a matter of seconds and have it available within the Users List
and ready to be referenced from any Blog. Simple, huh?
