<?php
    /**
     * A simple auth plugin for Shibboleth using the Shibboleth Native SP
     *
     * Enable it inc/config.inc.php:
     *    $CONF['AUTH_PLUGIN'] = 'shibboleth';
     *
     * Configure the plugin shibboleth_con.inc.php
     *
     * For details on how to configure Shibboleth visit
     * https://shibboleth.net/
     *
     * @package RPC
     */

    /**
     * Mandatory function for plugin authentication
     * Returns pipe-delimited authentication string in the format:
     *      (OK/FAIL)|username|perms|fail-reason
     *
     * @param boolean $enforce Should login be enforced in this function call?
     *                      When $enforce is TRUE, user will be forced to login, following any necessary redirects.
     *                      When $enforce is FALSE, cookies will be checked for active logins,
     *                      and the user created if they are found. If not found, the user will not be created.
     * @param object $config Global RPC_Config configuration singleton
     * @param object $db MySQLi database connection singleton
     * @access public
     * @return boolean FALSE | string authentication string in the format '(OK/FAIL)|username|email|permissions|fail-reason'
     */
    function rpc_authenticate($enforce = TRUE, $config, $db = NULL)
    {
        // Enforce if active protection
        $enforce = ($config->auth_shib['SHIB_MODE'] === 'active') ? TRUE : $enforce;

        // A Shibboleth session exists, get properties
        if (!empty($_SERVER["Shib-Session-ID"]) && $_SERVER["Shib-Identity-Provider"] == $config->auth_shib['SHIB_ENTITY_IDENTIFIER']) {
            // If these keys could ever be empty, you may need additional logic here to handle it....
            $username = $_SERVER[$config->auth_shib['SHIB_USERNAME_KEY']];
            $email = $_SERVER[$config->auth_shib['SHIB_EMAIL_KEY']];
        }
        // No session exists, If login required, redirect to the Shibboleth session initiator & return to the current URI
        elseif ($enforce) {
            header("Location: https://{$_SERVER['HTTP_HOST']}/Shibboleth.sso/Login?target=" . urlencode("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"));
            exit();
        }
        // Non-secure content allow access
        else {
            return FALSE;
        }

        // Use this condition if you need to check additional Shibboleth attributes before granting access
        // Minnesota uses it to verify an attribute flag indicating good standing with the Libraries...
        //
        // If you don't need this, you can just immediately return the auth string when 'Shib-Session-ID' exists
        // and remove this if/else structure
        //
        // If you need to deny users who were otherwise successful with Shibboleth, you should use this if/else
        if (true) {
            return "OK|$username|$email||";
        }
        else {
            // An error message will show in the RPC application
            $_SESSION['general_error'] = "A message to display in the RPC application...";

            // When denying access, you may need to redirect somewhere else...
            header("Location: https://example.edu/redirect_target");
            exit();
        }
    }

    /**
     * Optional Session clean up function
     * Destroy the current login session
     *
     * @param object $user RPC_User to log out
     * @access public
     * @return void
     */
    function rpc_logout($user)
    {
        // If passive protection then logout via Shibboleth
        if ($user->config->auth_shib['SHIB_MODE'] === 'passive') {
            // Logout via Shibboleth and show the logout screen after successful
            header("Location: https://{$_SERVER['HTTP_HOST']}/Shibboleth.sso/Logout?return=" . urlencode("https://{$_SERVER['HTTP_HOST']}/" . explode("/", $_SERVER['REQUEST_URI'])[1] ."/account?acct=logout"));
            exit();
        }
    }
?>
