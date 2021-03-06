<?php
/**
 * Users Core Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Users
 */
class Users_Actions_Admin_Users extends Users_Actions_Admin_Default
{
    /**
     * Builds user administration UI
     *
     * @access  public
     * @return  string  XHTML form
     */
    function Users()
    {
        $this->AjaxMe('script.js');
        $this->gadget->define('confirmDelete', Jaws::t('CONFIRM_DELETE'));
        $this->gadget->define('datagridNoItems', Jaws::t('NOTFOUND'));
        $this->gadget->define('LANGUAGE', array(
            'nickname'=> $this::t('USERS_NICKNAME'),
            'username'=> $this::t('USERS_USERNAME'),
            'mobile'=> $this::t('CONTACTS_MOBILE_NUMBER'),
            'email'=> Jaws::t('EMAIL'),
            'status'=> Jaws::t('STATUS'),
            'view'=> Jaws::t('VIEW'),
            'edit'=> Jaws::t('EDIT'),
            'acl'=> $this::t('ACLS'),
            'users_groups'=> $this::t('USERS_GROUPS'),
            'personal'=> $this::t('PERSONAL'),
            'delete'=> Jaws::t('DELETE'),
            'incompleteUserFields'=> $this::t('MYACCOUNT_INCOMPLETE_FIELDS'),
        ));

        $statusItems = array(
            0 => $this::t('USERS_STATUS_0'),
            1 => $this::t('USERS_STATUS_1'),
            2 => $this::t('USERS_STATUS_2'),
        );
        $this->gadget->define('statusItems', $statusItems);

        $assigns = array();
        $assigns['menubar'] =  empty($menubar)? $this->MenuBar('Users') : $menubar;
        $assigns['statusItems'] = $statusItems;
        $assigns['types'] = array(
            0 => $this::t('USERS_TYPE_NORMAL'),
            1 => $this::t('USERS_TYPE_SUPERADMIN'),
        );
        $assigns['expiry_date'] = $this->gadget->action->load('DatePicker')->xcalendar(array('name' => 'expiry_date'));
        $assigns['dob'] = $this->gadget->action->load('DatePicker')->xcalendar(array('name' => 'dob'));

        if ($this->gadget->registry->fetch('multi_domain') == 'true') {
            $assigns['domains'] = $this->gadget->model->load('Domains')->getDomains();
        }
        $assigns['components'] = Jaws_Gadget::getInstance('Components')->model->load('Gadgets')
            ->GetGadgetsList(null, true, true);

        return $this->gadget->template->xLoadAdmin('Users.html')->render($assigns);
    }

    /**
     * Prepares list of users for datagrid
     *
     * @access  public
     * @return  array  Grid data
     */
    function GetUsers()
    {
        $post = $this->gadget->request->fetch(
            array('offset', 'limit', 'sortDirection', 'sortBy', 'filters:array'),
            'post'
        );
        $filters = array();
        $filters['term'] = $post['filters']['filter_term'];
        if ((int)$post['filters']['filter_type'] === 0) {
            $filters['superadmin'] = false;
        } elseif ((int)$post['filters']['filter_type'] === 1) {
            $filters['superadmin'] = true;
        }
        if ((int)$post['filters']['filter_status'] >= 0) {
            $filters['status'] = (int)$post['filters']['filter_status'];
        }

        $uModel = $this->gadget->model->load('Users');
        $users = $uModel->getUsers(
            $post['filters']['filter_domain'],
            (int)$post['filters']['filter_group'],
            $filters,
            array(),
            $post['sortBy'],
            $post['limit'],
            $post['offset']
        );
        if (Jaws_Error::IsError($users)) {
            return $this->gadget->session->response(
                $users->GetMessage(),
                RESPONSE_ERROR
            );
        }

        $usersCount = $uModel->getUsersCount(
            $post['filters']['filter_domain'],
            (int)$post['filters']['filter_group'],
            $filters,
        );
        if (Jaws_Error::IsError($usersCount)) {
            return $this->gadget->session->response(
                $usersCount->GetMessage(),
                RESPONSE_ERROR
            );
        }

        return $this->gadget->session->response(
            '',
            RESPONSE_NOTICE,
            array(
                'total' => $usersCount,
                'records' => $users
            )
        );
    }
    /**
     *
     * Get an user info
     *
     * @access  public
     * @return  void
     */
    function GetUser()
    {
        $post = $this->gadget->request->fetch(array('id:integer', 'account:bool', 'personal:bool'), 'post');
        $userInfo = $this->app->users->GetUserNew(
            (int)$post['id'],
            array('account' => (bool)$post['account'], 'personal' => (bool)$post['personal'])
        );
        if (Jaws_Error::IsError($userInfo)) {
            return $this->gadget->session->response(
                $userInfo->getMessage(),
                RESPONSE_ERROR
            );
        }

        $objDate = Jaws_Date::getInstance();
        if (isset($userInfo['dob']) && !empty($userInfo['dob'])) {
            $userInfo['dob'] = $objDate->Format($userInfo['dob'], 'Y/m/d');
        }

        if (!isset($userInfo['avatar']) && empty($userInfo['avatar'])) {
            $userInfo['avatar'] = $this->app->getSiteURL('/gadgets/Users/Resources/images/photo128px.png');
        } else {
            $userInfo['avatar'] = $this->app->getDataURL(). 'avatar/'. $userInfo['avatar'];
        }

        return $this->gadget->session->response(
            '',
            RESPONSE_NOTICE,
            $userInfo
        );
    }

    /**
     * Adds a new user
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function AddUser()
    {
        $this->gadget->CheckPermission('ManageUsers');
        $uData = $this->gadget->request->fetch('data:array', 'post');
        $uData['concurrents'] = (int)$uData['concurrents'];
        $uData['superadmin'] = $uData['superadmin'] == 1;

        $JCrypt = Jaws_Crypt::getInstance();
        if (!Jaws_Error::IsError($JCrypt)) {
            $uData['password'] = $JCrypt->decrypt($uData['password']);
        }

        $uData['status'] = (int)$uData['status'];
        $uData['superadmin'] = $this->app->session->user->superadmin? (bool)$uData['superadmin'] : false;
        $res = $this->app->users->AddUser($uData);
        if (Jaws_Error::isError($res)) {
            return $this->gadget->session->response(
                $res->getMessage(),
                RESPONSE_ERROR
            );
        }

        $guid = $this->gadget->registry->fetch('anon_group');
        if (!empty($guid)) {
            $this->app->users->AddUserToGroup($res, (int)$guid);
        }
        return $this->gadget->session->response(
            $this::t('USERS_CREATED', $uData['username']),
            RESPONSE_NOTICE
        );
    }

    /**
     * Update user account information
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function UpdateUser()
    {
        $this->gadget->CheckPermission('ManageUsers');
        $post = $this->gadget->request->fetch(array('id', 'data:array'), 'post');
        $uData = $post['data'];

        $JCrypt = Jaws_Crypt::getInstance();
        if (!Jaws_Error::IsError($JCrypt)) {
            $uData['password'] = $JCrypt->decrypt($uData['password']);
        }

        if ((int)$post['id'] == $this->app->session->user->id) {
            unset($uData['status'], $uData['superadmin'], $uData['expiry_date']);
        } else {
            $uData['status'] = (int)$uData['status'];
        }

        $res = $this->app->users->UpdateUser((int)$post['id'], $uData);
        if (Jaws_Error::isError($res)) {
            return $this->gadget->session->response(
                $res->getMessage(),
                RESPONSE_ERROR
            );
        } else {
            // send activate notification
            if ($uData['prev_status'] == 2 && $uData['status'] == 1) {
                $uRegistration = $this->gadget->action->load('Registration');
                $uRegistration->ActivateNotification($uData, $this->gadget->registry->fetch('anon_activation'));
            }
            return $this->gadget->session->response(
                $this::t('USERS_UPDATED', $uData['username']),
                RESPONSE_NOTICE
            );
        }
    }

    /**
     * Update user's personal info
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function UpdatePersonal()
    {
        $this->gadget->CheckPermission('ManageUsers');
        $post = $this->gadget->request->fetch(array('id', 'data:array'), 'post');
        $pData = $post['data'];

        $pData['dob'] = empty($pData['dob'])? null : $pData['dob'];
        if (!empty($pData['dob'])) {
            $objDate = Jaws_Date::getInstance();
            $pData['dob'] = $objDate->ToBaseDate(preg_split('/[- :]/', $pData['dob']), 'Y-m-d H:i:s');
            $pData['dob'] = $this->app->UserTime2UTC($pData['dob'], 'Y-m-d H:i:s');
        }

        // don't touch user's avatar
        if ($pData['avatar'] == 'false') {
            unset($pData['avatar']);
        }

        $res = $this->app->users->UpdatePersonal((int)$post['id'], $pData);
        if ($res === false || Jaws_Error::IsError($res)) {
            return $this->gadget->session->response(
                $this::t('USERS_PERSONALINFO_NOT_UPDATED'),
                RESPONSE_ERROR
            );
        }

        return $this->gadget->session->response(
            $this::t('USERS_PERSONALINFO_UPDATED'),
            RESPONSE_NOTICE
        );
    }

    /**
     * Logout user
     *
     * @access  public
     * @return  void
     */
    function Logout()
    {
        $this->app->session->logout();
        $admin_script = $this->gadget->registry->fetch('admin_script', 'Settings');
        return Jaws_Header::Location($admin_script?: 'admin.php');
    }

}