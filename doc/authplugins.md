## Research Project Calculator Authentication Plugins
By default, the RPC ships with a "native" authentication plugin. You may create a custom plugin to interface with your institution's user systems.  We would welcome contributions for LDAP or other common systems.

**Note:** This API isn't all that well tested yet.  Minitex will be happy to assist with your implementations if you run into problems. Just [[https://www.minitex.umn.edu/Contact/|contact us]], attn: Michael Berkowski.

### API
#### Required Function
There's a simple API for passing authentication into the RPC.

The only requirement is that you create the following function.
It should be responsible for handling ALL authentication processes,
including redirects, form display if necessary, network communication, etc.

```php
/**
 * @param boolean $enforce Should login be enforced?  If FALSE,
 *    all login redirects should be handled in
 *    this function call.  If FALSE, existing session
 *    or cookies may be used to create a user, but login
 *    is not enforced. This us useful for pages allowing
 *    guest access
 * @param object $config Global RPC_Config configuration singleton
 * @param object $config Global MySQLi database connection singleton
 * @return string Pipe-delimited string in the format:
 *                (OK/FAIL)|username|email|permissions|fail-reason
 *
 *                Optional fields may be empty, but pipe delimiters must not be omitted
 *
 *                OK/FAIL:       Status of the authentication attempt
 *                username:      Unique username
 *                email:         <optional> Email address
 *                permissions:   <optional> Integer representation of authlevel bits defined in RPC_User
 *                fail-reason:   <optional> A human-readable string describing reason for auth failure
 *
 *                ** ALTERNATIVE RETURN OBJECT **
 *                Alternatively, you may return a valid and complete RPC_User object
 *                from rpc_authenticate().  If you choose to return a valid RPC_User,
 *                the local users table WILL NOT be checked, and it will be assumed
 *                that your user already exists locally.  Therefore you should only
 *                return an object if you are certain the user exists.
 */
function rpc_authenticate($enforce=TRUE, $config, $db) {
    // Function definition
    return $access_string;
}
```

The username returned by your authentication plugin will be
checked against the RPC's local users table.  If the user does
not exist locally, it will be created immediately.

Note:  The local users table is only checked if you return a
**PIPE-DELIMITED STRING**.  Returning an `RPC_User` object will assume
the user is already a valid local user.

####Optional Functions

```php
/**
 * Logout the active user, calling your own necessary logout hooks and procedures
 *
 * @param object $user RPC_User (or derivative) to logout
 * @return void
 */
function rpc_logout($user) {
    // Function definition
}
```

### Files & Naming
The application expects plugins to look like this, assuming your auth plugin is called `yourplugin`:
```
plugins/auth/
         |
         |->yourplugin/
                |
                |->yourplugin.inc.php
```

### Configuration
 - Set the `AUTH_PLUGIN` config directive to the name of your plugin.
