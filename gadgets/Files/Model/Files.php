<?php
/**
 * Files Gadget Admin
 *
 * @category    GadgetModel
 * @package     Files
 */
class Files_Model_Files extends Jaws_Gadget_Model
{
    /**
     * Insert attachments file info in DB
     *
     * @access  public
     * @param   array   $interface  Gadget connection interface
     * @param   array   $files  List of upload files in jaws_utils format
     * @return  bool    True if insert successfully otherwise False
     */
    function insertFiles($interface, $files)
    {
        if (is_array($files)) {
            $data = array(
                'gadget'      => '',
                'action'      => '',
                'reference'   => 0,
                'type'        => 0,
                'title'       => '',
                'description' => '',
                'public'      => true,
            );
            $data = array_merge($data, $interface);
            $data['user'] = $this->app->session->user->id;

            $attachTable = Jaws_ORM::getInstance()->table('files');
            foreach ($files as $fileInfo) {
                $data['title']    = $data['title']?: $fileInfo['user_filename'];
                $data['postname'] = $fileInfo['user_filename'];
                $data['filename'] = $fileInfo['host_filename'];
                $data['filesize'] = $fileInfo['host_filesize'];
                $data['mimetype'] = $fileInfo['host_mimetype'];
                $data['filetype'] = $fileInfo['host_filetype'];
                $data['filetime'] = time();
                $data['filehits'] = 0;
                $data['filekey']  = md5(uniqid('', true));

                $result = $attachTable->insert($data)->exec();                
            }

            return true;
        }

        return false;
    }

    /**
     * Returns array of files details of a reference
     *
     * @access  public
     * @param   array   $interface  Gadget connection interface
     * @param   array   $ids        File(s) ID(s)
     * @return  array   List of files info or Jaws_Error on error
     */
    function getFiles($interface, $ids = array())
    {
        $data = array(
            'gadget'      => '',
            'action'      => '',
            'reference'   => 0,
            'type'        => 0,
            'public'      => true,
        );
        $interface = array_merge($data, $interface);
        $interface['user'] = $this->app->session->user->id;

        return Jaws_ORM::getInstance()
            ->table('files')
            ->select(
                'id:integer', 'type:integer', 'title', 'description', 'public:boolean',
                'postname', 'filename', 'mimetype', 'filetype:integer', 'filesize:integer', 'filetime:integer',
                'filehits:integer', 'filekey'
            )->where('id', (array)$ids, 'in')
            ->and()
            ->where('gadget', $interface['gadget'])
            ->and()
            ->where('action', $interface['action'])
            ->and()
            ->where('reference', $interface['reference'])
            ->and()
            ->where('type', $interface['type'])
            ->and()
            ->where('public', $interface['public'])
            ->fetchAll();
    }

    /**
     * Returns array of files details of a reference
     *
     * @access  public
     * @param   array   $interface  Gadget connection interface
     * @param   array   $ids        File(s) ID(s)
     * @return  array   List of files info or Jaws_Error on error
     */
    function deleteFiles($interface, $ids = array())
    {
        $data = array(
            'gadget'      => '',
            'action'      => '',
            'reference'   => 0,
            'type'        => 0,
            'public'      => true,
        );
        $interface = array_merge($data, $interface);
        $interface['user'] = $this->app->session->user->id;

        // get reference files list from database
        $files = $this->getFiles($interface, $ids);
        if (Jaws_Error::IsError($files)) {
            return $files;
        }

        $result = Jaws_ORM::getInstance()
            ->table('files')
            ->delete()
            ->where('id', (array)$ids, 'in')
            ->and()
            ->where('gadget', $interface['gadget'])
            ->and()
            ->where('action', $interface['action'])
            ->and()
            ->where('reference', $interface['reference'])
            ->and()
            ->where('type', $interface['type'])
            ->and()
            ->where('public', $interface['public'])
            ->exec();
        if (!Jaws_Error::IsError($result)) {
            foreach ($files as $file) {
                if (!empty($file['filename'])) {
                    Jaws_Utils::delete(ROOT_DATA_PATH . 'files/' . $file['filename']);
                }
            }
        }

        return $result;
    }

}