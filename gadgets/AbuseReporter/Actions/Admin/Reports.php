<?php
/**
 * AbuseReporter Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    AbuseReporter
 */
class AbuseReporter_Actions_Admin_Reports extends AbuseReporter_Actions_Admin_Default
{
    /**
     * Builds Reports UI
     *
     * @access  public
     * @return  string  XHTML UI
     */
    function Reports()
    {
        $this->gadget->CheckPermission('ManageReports');
        $this->AjaxMe('script.js');
        $this->gadget->define('confirmDelete', Jaws::t('CONFIRM_DELETE'));
        $this->gadget->define('lbl_gadget', _t('ABUSEREPORTER_GADGET'));
        $this->gadget->define('lbl_action', _t('ABUSEREPORTER_ACTION'));
        $this->gadget->define('lbl_type', _t('ABUSEREPORTER_TYPE'));
        $this->gadget->define('lbl_priority', _t('ABUSEREPORTER_PRIORITY'));
        $this->gadget->define('lbl_status', Jaws::t('STATUS'));
        $this->gadget->define('lbl_edit', Jaws::t('EDIT'));
        $this->gadget->define('lbl_delete', Jaws::t('DELETE'));
        $this->gadget->define('lbl_editReport', _t('ABUSEREPORTER_REPORT_EDIT'));

        $tpl = $this->gadget->template->loadAdmin('Reports.html');
        $tpl->SetBlock('Reports');

        //Menu bar
        $tpl->SetVariable('menubar', $this->MenuBar('Reports'));

        $tpl->SetVariable('lbl_of', Jaws::t('OF'));
        $tpl->SetVariable('lbl_to', Jaws::t('TO'));
        $tpl->SetVariable('lbl_items', Jaws::t('ITEMS'));
        $tpl->SetVariable('lbl_per_page', Jaws::t('PERPAGE'));
        $tpl->SetVariable('lbl_cancel', Jaws::t('CANCEL'));
        $tpl->SetVariable('lbl_save', Jaws::t('SAVE'));

        $tpl->SetVariable('lbl_url', Jaws::t('URL'));
        $tpl->SetVariable('lbl_gadget', _t('ABUSEREPORTER_GADGET'));
        $tpl->SetVariable('lbl_action', _t('ABUSEREPORTER_ACTION'));
        $tpl->SetVariable('lbl_reference', _t('ABUSEREPORTER_REFERENCE'));
        $tpl->SetVariable('lbl_comment', _t('ABUSEREPORTER_COMMENT'));
        $tpl->SetVariable('lbl_type', _t('ABUSEREPORTER_TYPE'));
        $tpl->SetVariable('lbl_priority', _t('ABUSEREPORTER_PRIORITY'));
        $tpl->SetVariable('lbl_status', Jaws::t('STATUS'));
        $tpl->SetVariable('lbl_response', _t('ABUSEREPORTER_RESPONSE'));
        $tpl->SetVariable('lbl_insert_time', _t('ABUSEREPORTER_INSERT_TIME'));

        // gadgets filter
        $cmpModel = Jaws_Gadget::getInstance('Components')->model->load('Gadgets');
        $gadgetList = $cmpModel->GetGadgetsList();
        if (!Jaws_Error::IsError($gadgetList) && count($gadgetList) > 0) {
            array_unshift($gadgetList, array('name' => -1, 'title' => Jaws::t('ALL')));
            foreach ($gadgetList as $gadget) {
                $tpl->SetBlock('Reports/filter_gadget');
                $tpl->SetVariable('value', $gadget['name']);
                $tpl->SetVariable('title', $gadget['title']);
                $tpl->ParseBlock('Reports/filter_gadget');
            }
            array_shift($gadgetList);
            foreach ($gadgetList as $gadget) {
                $tpl->SetBlock('Reports/gadget');
                $tpl->SetVariable('value', $gadget['name']);
                $tpl->SetVariable('title', $gadget['title']);
                $tpl->ParseBlock('Reports/gadget');
            }
        }

        // priority filter
        $priorities = array(
            -1 => Jaws::t('ALL'),
            0  => _t('ABUSEREPORTER_PRIORITY_0'),
            1  => _t('ABUSEREPORTER_PRIORITY_1'),
            2  => _t('ABUSEREPORTER_PRIORITY_2'),
            3  => _t('ABUSEREPORTER_PRIORITY_3'),
            4  => _t('ABUSEREPORTER_PRIORITY_4'),
        );
        foreach ($priorities as $priority => $title) {
            $tpl->SetBlock('Reports/filter_priority');
            $tpl->SetVariable('value', $priority);
            $tpl->SetVariable('title', $title);
            $tpl->ParseBlock('Reports/filter_priority');
        }
        array_shift($priorities);
        foreach ($priorities as $priority => $title) {
            $tpl->SetBlock('Reports/priority');
            $tpl->SetVariable('value', $priority);
            $tpl->SetVariable('title', $title);
            $tpl->ParseBlock('Reports/priority');
        }

        // status filter
        $statuses = array(
            -1 => Jaws::t('ALL'),
            0  => _t('ABUSEREPORTER_STATUS_0'),
            1  => _t('ABUSEREPORTER_STATUS_1'),
        );
        foreach ($statuses as $status => $title) {
            $tpl->SetBlock('Reports/filter_status');
            $tpl->SetVariable('value', $status);
            $tpl->SetVariable('title', $title);
            $tpl->ParseBlock('Reports/filter_status');
        }
        array_shift($statuses);
        foreach ($statuses as $status => $title) {
            $tpl->SetBlock('Reports/status');
            $tpl->SetVariable('value', $status);
            $tpl->SetVariable('title', $title);
            $tpl->ParseBlock('Reports/status');
        }

        // types
        $types = array(
           -1 => Jaws::t('ALL'),
            0 => _t('ABUSEREPORTER_TYPE_ABUSE_0'),
            1 => _t('ABUSEREPORTER_TYPE_ABUSE_1'),
            2 => _t('ABUSEREPORTER_TYPE_ABUSE_2'),
            3 => _t('ABUSEREPORTER_TYPE_ABUSE_3'),
            4 => _t('ABUSEREPORTER_TYPE_ABUSE_4'),
            5 => _t('ABUSEREPORTER_TYPE_ABUSE_5'),
        );
        array_shift($types);
        foreach ($types as $type => $title) {
            $tpl->SetBlock('Reports/type');
            $tpl->SetVariable('value', $type);
            $tpl->SetVariable('title', $title);
            $tpl->ParseBlock('Reports/type');
        }

        $tpl->ParseBlock('Reports');
        return $tpl->Get();
    }

    /**
     * Get reports list
     *
     * @access  public
     * @return  JSON
     */
    function GetReports()
    {
        $this->gadget->CheckPermission('ManageReports');
        $post = $this->gadget->request->fetch(
            array('offset', 'limit', 'sortDirection', 'sortBy', 'filters:array'),
            'post'
        );

        $orderBy = 'id asc';
        if (isset($post['sortBy'])) {
            $orderBy = trim($post['sortBy'] . ' ' . $post['sortDirection']);
        }

        $model = $this->gadget->model->loadAdmin('Reports');
        $reports = $model->GetReports($post['filters'], $post['limit'], $post['offset'], $orderBy);

        foreach ($reports as $key => &$report) {
            $report['priority'] = _t('ABUSEREPORTER_PRIORITY_'. $report['priority']);
            $report['status'] = _t('ABUSEREPORTER_STATUS_'. $report['status']);
            $report['type'] = _t('ABUSEREPORTER_TYPE_ABUSE_' . $report['type']);
        }

        $reportsCount = $model->GetReportsCount($post['filters']);
        return $this->gadget->session->response(
            '',
            RESPONSE_NOTICE,
            array(
                'total'   => $reportsCount,
                'records' => $reports
            )
        );
    }

    /**
     * Get a report info
     *
     * @access  public
     * @return  JSON
     */
    function GetReport()
    {
        $this->gadget->CheckPermission('ManageReports');
        $id = (int)$this->gadget->request->fetch('id', 'post');
        $reportInfo = $this->gadget->model->loadAdmin('Reports')->GetReport($id);
        if (Jaws_Error::IsError($reportInfo)) {
            return $reportInfo;;
        }
        if (!empty($reportInfo)) {
            $objDate = Jaws_Date::getInstance();
            $reportInfo['insert_time'] = $objDate->Format($reportInfo['insert_time']);
        }
        return $reportInfo;
    }

    /**
     * Update a report
     *
     * @access  public
     * @return  void
     */
    function UpdateReport()
    {
        $this->gadget->CheckPermission('ManageReports');

        $post = $this->gadget->request->fetch(array('id', 'data:array'), 'post');
        $result = $this->gadget->model->loadAdmin('Reports')->UpdateReport($post['id'], $post['data']);
        if (Jaws_Error::isError($result)) {
            return $this->gadget->session->response($result->GetMessage(), RESPONSE_ERROR);
        } else {
            return $this->gadget->session->response(_t('ABUSEREPORTER_REPORT_UPDATED'), RESPONSE_NOTICE);
        }
    }

    /**
     * Delete a report
     *
     * @access  public
     * @return  void
     */
    function DeleteReport()
    {
        $this->gadget->CheckPermission('ManageReports');

        $id = (int)$this->gadget->request->fetch('id', 'post');
        $result =  $this->gadget->model->loadAdmin('Reports')->DeleteReport($id);
        if (Jaws_Error::isError($result)) {
            return $this->gadget->session->response($result->GetMessage(), RESPONSE_ERROR);
        } else {
            return $this->gadget->session->response(_t('ABUSEREPORTER_REPORT_DELETED'), RESPONSE_NOTICE);
        }
    }

}