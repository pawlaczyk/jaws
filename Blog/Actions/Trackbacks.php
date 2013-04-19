<?php
/**
 * Blog Gadget
 *
 * @category   Gadget
 * @package    Blog
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Blog_Actions_Trackbacks extends Blog_HTML
{
    /**
     * Saves a new trackback if all is ok and sends response
     * The function other people send to so our blog gadget
     * gets trackbacks
     *
     * @access  public
     * @return  string  trackback xml response
     */
    function Trackback()
    {
        // Based on Wordpress trackback implementation
        $tb_msg_error = '<?xml version="1.0" encoding="iso-8859-1"?><response><error>1</error><message>#MESSAGE#</message></response>';
        $tb_msg_ok = '<?xml version="1.0" encoding="iso-8859-1"?><response><error>0</error></response>';

        $sender = Jaws_Utils::GetRemoteAddress();
        $ip = $sender['proxy'] . (!empty($sender['proxy'])? '-' : '') . $sender['client'];

        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('title', 'url', 'blog_name', 'excerpt'), 'post');

        if (is_null($post['title']) || is_null($post['url']) ||
            is_null($post['blog_name']) || is_null($post['excerpt'])) {
            Jaws_Header::Location('');
        }

        $request =& Jaws_Request::getInstance();
        $id = $request->get('id', 'get');

        if (is_null($id)) {
            $id = $request->get('id', 'post');
            if (is_null($id)) {
                $id = '';
            }
        }

        $title    = urldecode($post['title']);
        $url      = urldecode($post['url']);
        $blogname = urldecode($post['blog_name']);
        $excerpt  = urldecode($post['excerpt']);

        if (trim($id) == '') {
            Jaws_Header::Location('');
        } elseif (empty($title) && empty($url) && empty($blogname)) {
            $url = $this->gadget->GetURLFor('SingleView', array('id' => $id), true);
            Jaws_Header::Location($url);
        } elseif ($this->gadget->registry->get('trackback') == 'true') {
            header('Content-Type: text/xml');
            $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
            $trackback = $model->NewTrackback($id, $url, $title, $excerpt, $blogname, $ip);
            if (Jaws_Error::IsError($trackback)) {
                return str_replace('#MESSAGE#', $trackback->GetMessage(), $tb_msg_error);
            }
            return $tb_msg_ok;
        } else {
            header('Content-Type: text/xml');
            return str_replace('#MESSAGE#', _t('BLOG_TRACKBACK_DISABLED'), $tb_msg_error);
        }
    }

    /**
     * Shows existing trackbacks for a given entry
     *
     * @access  public
     * @param   int     $id     entry id
     * @return  string  XHTML template content
     */
    function ShowTrackbacks($id)
    {
        if ($this->gadget->registry->get('trackback') == 'true') {
            $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
            $trackbacks = $model->GetTrackbacks($id);
            $tpl = new Jaws_Template('gadgets/Blog/templates/');
            $tpl->Load('Trackbacks.html');
            $tpl->SetBlock('trackbacks');
            $tburi = $this->gadget->GetURLFor('Trackback', array('id' => $id), true);
            $tpl->SetVariable('TrackbackURI', $tburi);
            if (!Jaws_Error::IsError($trackbacks)) {
                $date = $GLOBALS['app']->loadDate();
                foreach ($trackbacks as $tb) {
                    $tpl->SetBlock('trackbacks/item');
                    $tpl->SetVariablesArray($tb);
                    $tpl->SetVariable('createtime-iso',       $tb['createtime']);
                    $tpl->SetVariable('createtime',           $date->Format($tb['createtime']));
                    $tpl->SetVariable('createtime-monthname', $date->Format($tb['createtime'], 'MN'));
                    $tpl->SetVariable('createtime-monthabbr', $date->Format($tb['createtime'], 'M'));
                    $tpl->SetVariable('createtime-month',     $date->Format($tb['createtime'], 'm'));
                    $tpl->SetVariable('createtime-dayname',   $date->Format($tb['createtime'], 'DN'));
                    $tpl->SetVariable('createtime-dayabbr',   $date->Format($tb['createtime'], 'D'));
                    $tpl->SetVariable('createtime-day',       $date->Format($tb['createtime'], 'd'));
                    $tpl->SetVariable('createtime-year',      $date->Format($tb['createtime'], 'Y'));
                    $tpl->SetVariable('createtime-time',      $date->Format($tb['createtime'], 'g:ia'));
                    $tpl->ParseBlock('trackbacks/item');
                }
            }
            $tpl->ParseBlock('trackbacks');

            return $tpl->Get();
        }
    }

}