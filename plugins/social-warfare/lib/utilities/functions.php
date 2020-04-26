<?php

/**
 * This file serves to hold global functions made available to developers
 * by Social Warfare.
 *
 * Any actual plugin functionality needs to exist as a class method somewhere else.
 *
 * @since  3.3.0 | 14 AUG 2018 | Created file.
 *
 */


/**
 * This is the primary function that people can use to add buttons into their
 * themes and whatnot. It is always encouraged to make it pluggable by adding
 * wrapping it in checks for existence.
 *
 * @param  array  $args See SWP_Buttons_Panel.
 * @return void   It echoes the results to the screen.
 */
function social_warfare( $args = array() ) {
    $buttons_panel = new SWP_Buttons_Panel( $args, true );
    echo $buttons_panel->render_html();
}


/**
 * This is the old camelCased version of the above function. We're leaving it here
 * as a passthrough to support folks who installed this into their themes ages
 * ago to make sure that it will still work for them.
 *
 */
function socialWarfare( $content = false, $where = 'default', $echo = true ) {
    social_warfare( array( 'content' => $content, 'where' => $where, 'echo' => $echo ) );
}


/**
 * A wrapper function used for formatting numbers.
 *
 * @param  integer $number A number to be formatted.
 * @return string          The formatted number.
 */
function swp_kilomega( $number ) {
    return SWP_Utiltiy::kilomega( $number );
}
