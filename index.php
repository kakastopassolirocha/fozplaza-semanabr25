<?php
get_header();

if (have_posts()){
    while (have_posts()) : the_post();
        the_content();
    endwhile;
}
else
{
    echo '<h3>404</h3>';
}

get_footer();
?>