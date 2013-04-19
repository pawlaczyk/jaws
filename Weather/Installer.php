<?php
/**
 * Weather Installer
 *
 * @category    GadgetModel
 * @package     Weather
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2012-2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Weather_Installer extends Jaws_Gadget_Installer
{
    /**
     * Installs the gadget
     *
     * @access  public
     * @return  mixed   True on successful installation, Jaws_Error otherwise
     */
    function Install()
    {
        if (!Jaws_Utils::is_writable(JAWS_DATA)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', JAWS_DATA), _t('WEATHER_NAME'));
        }

        $new_dir = JAWS_DATA . 'weather' . DIRECTORY_SEPARATOR;
        if (!Jaws_Utils::mkdir($new_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir), _t('WEATHER_NAME'));
        }

        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        // Registry keys
        $this->gadget->registry->add('unit', 'metric');
        $this->gadget->registry->add('date_format', 'DN d MN');
        $this->gadget->registry->add('update_period', '3600');
        $this->gadget->registry->add('api_key', '');

        return true;
    }

    /**
     * Uninstalls the gadget
     *
     * @access  public
     * @return  mixed   True on success, Jaws_Error otherwise
     */
    function Uninstall()
    {
        $result = $GLOBALS['db']->dropTable('weather');
        if (Jaws_Error::IsError($result)) {
            $gName  = _t('WEATHER_NAME');
            $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
            $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
            return new Jaws_Error($errMsg, $gName);
        }

        // Registry keys
        $this->gadget->registry->del('unit');
        $this->gadget->registry->del('date_format');
        $this->gadget->registry->del('update_period');
        $this->gadget->registry->del('api_key');

        return true;
    }

    /**
     * Upgrades the gadget
     *
     * @access  public
     * @param   string  $old    Current version (in registry)
     * @param   string  $new    New version (in the $gadgetInfo file)
     * @return  mixed   True on success, Jaws_Error otherwise
     */
    function Upgrade($old, $new)
    {
        if (version_compare($old, '0.8.0', '<')) {
            $result = $this->installSchema('schema.xml');
            if (Jaws_Error::IsError($result)) {
                return $result;
            }

            // Remove from layout
            $layoutModel = $GLOBALS['app']->loadGadget('Layout', 'AdminModel');
            if (!Jaws_Error::isError($layoutModel)) {
                $layoutModel->DeleteGadgetElements('Weather');
            }

            // ACL keys
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Weather/ManageRegions', 'true');
            $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/Weather/AddCity');
            $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/Weather/EditCity');
            $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/Weather/DeleteCity');

            // Registry keys
            $this->gadget->registry->add('unit', 'metric');
            $this->gadget->registry->add('date_format', 'DN d MN');
            $this->gadget->registry->add('update_period', '3600');
            $this->gadget->registry->del('refresh');
            $this->gadget->registry->del('cities');
            $this->gadget->registry->del('units');
            $this->gadget->registry->del('forecast');
            $this->gadget->registry->del('partner_id');
            $this->gadget->registry->del('license_key');
        }

        // Registry keys
        $this->gadget->registry->add('api_key', '');

        return true;
    }

}