<?php

function esraw_get_user_roles () {
    global $wp_roles;
    $roles = $wp_roles->get_names();
    $roles ['not_login']= 'Guest';
    return $roles;
}