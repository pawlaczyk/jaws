<?php
/**
 * Users Core Gadget
 *
 * @category    Gadget
 * @package     Users
 */
class Users_Account_WWW_Authenticate extends Users_Account_WWW
{
    /**
     * Authenticate
     *
     * @access  public
     * @return  void
     */
    function Authenticate()
    {
        if (($GLOBALS['app']->Registry->fetch('http_auth', 'Settings') != 'true') ||
            (!isset($_SERVER['PHP_AUTH_USER'])) ||
            (jaws()->request->method() == 'post')
        ) {
            $classname = "Users_Account_Default_Authenticate";
            $objDefaultAccount = new $classname($this->gadget);
            return $objDefaultAccount->Authenticate();
        }

        $httpAuth = new Jaws_HTTPAuth();
        $httpAuth->AssignData();
        jaws()->request->update('username', $httpAuth->getUsername(), 'post');
        jaws()->request->update('password', $httpAuth->getPassword(), 'post');
        jaws()->request->update('usecrypt', 0, 'post');

        $loginData = $this->gadget->request->fetch(
            array('domain', 'username', 'password', 'usecrypt', 'loginkey', 'loginstep', 'remember'),
            'post'
        );

        try {
            // get bad logins count
            $bad_logins = $this->gadget->action->load('Login')->BadLogins($loginData['username'], 0);

            $max_lockedout_login_bad_count = $GLOBALS['app']->Registry->fetch('password_bad_count', 'Policy');
            if ($bad_logins >= $max_lockedout_login_bad_count) {
                // forbidden access event logging
                $GLOBALS['app']->Listener->Shout(
                    'Users',
                    'Log',
                    array('Users', 'Login', JAWS_WARNING, null, 403, $result['id'])
                );
                throw new Exception(_t('GLOBAL_ERROR_LOGIN_LOCKED_OUT'), 403);
            }

            $this->gadget->session->update('temp.login.user', '');
            if ($loginData['username'] === '') {
                throw new Exception(_t('GLOBAL_ERROR_LOGIN_WRONG'), 401);
            }

            if ($loginData['usecrypt']) {
                $JCrypt = Jaws_Crypt::getInstance();
                if (!Jaws_Error::IsError($JCrypt)) {
                    $loginData['password'] = $JCrypt->decrypt($loginData['password']);
                }
            } else {
                $loginData['password'] = Jaws_XSS::defilter($loginData['password']);
            }

            // set default domain if not set
            if (is_null($loginData['domain'])) {
                $loginData['domain'] = (int)$this->gadget->registry->fetch('default_domain');
            }

            // fetch user information from database
            $userModel = $GLOBALS['app']->loadObject('Jaws_User');
            $user = $userModel->VerifyUser($loginData['domain'], $loginData['username'], $loginData['password']);
            if (Jaws_Error::isError($user)) {
                // increase bad logins count
                $this->gadget->action->load('Login')->BadLogins($loginData['username'], 1);
                throw new Exception($user->getMessage(), $user->getCode());
            }

            // fetch user groups
            $groups = $userModel->GetGroupsOfUser($user['id']);
            if (Jaws_Error::IsError($groups)) {
                $groups = array();
            }

            $user['groups'] = $groups;
            $user['avatar'] = $userModel->GetAvatar(
                $user['avatar'],
                $user['email'],
                48,
                $user['last_update']
            );
            $user['internal'] = true;
            $user['remember'] = (bool)$loginData['remember'];

            // check user concurrents logins
            $existSessions = 0;
            if (!empty($user['concurrents'])) {
                $existSessions = $GLOBALS['app']->Session->GetUserSessions($user['id'], true);
            }
            if (!empty($existSessions) && $existSessions >= $user['concurrents']) {
                // login conflict event logging
                $GLOBALS['app']->Listener->Shout(
                    'Session',
                    'Log',
                    array('Users', 'Login', JAWS_WARNING, null, 403, $user['id'])
                );

                throw new Exception(_t('GLOBAL_ERROR_LOGIN_CONCURRENT_REACHED'), 409);
            }

            // remove login key
            $this->gadget->session->delete('loginkey');
            // remove temp user data
            $this->gadget->session->delete('temp.login.user');
            // unset bad login entry
            $this->gadget->action->load('Login')->BadLogins($user['username'], -1);

            return $user;
        } catch (Exception $error) {
            unset($loginData['password']);
            $this->gadget->session->push(
                $error->getMessage(),
                'Login.Response',
                RESPONSE_ERROR,
                $loginData
            );

            return Jaws_Error::raiseError($error->getMessage(), $error->getCode());
        }
    }

    /**
     * Authorize
     *
     * @access  public
     * @return  void
     */
    function Authorize($loginData = null)
    {
        $classname = "Users_Account_Default_Authenticate";
        $objDefaultAccount = new $classname($this->gadget);
        return $objDefaultAccount->Authorize($loginData);
    }

    /**
     * Authenticate Error
     *
     * @access  public
     * @return  string  XHTML content
     */
    function AuthenticateError($error, $authtype, $referrer)
    {
        $classname = "Users_Account_Default_Authenticate";
        $objDefaultAccount = new $classname($this->gadget);
        return $objDefaultAccount->AuthenticateError($error, $authtype, $referrer);
    }

}