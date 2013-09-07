/**
 * Directory Javascript actions
 *
 * @category    Ajax
 * @package     Directory
 * @author      Mohsen Khahani <mkhahani@gmail.com>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
/**
 * Use async mode, create Callback
 */
var DirectoryCallback = {
    CreateDirectory: function(response) {
        if (response.css === 'notice-message') {
            cancel();
            fillFilesCombo(currentDir);
        }
        $('simple_response').set('html', response.message);
    },

    UpdateDirectory: function(response) {
        if (response.css === 'notice-message') {
            cancel();
            fillFilesCombo(currentDir);
        }
        $('simple_response').set('html', response.message);
    },

    DeleteDirectory: function(response) {
        if (response.css === 'notice-message') {
            cancel();
            fillFilesCombo(currentDir);
        }
        $('simple_response').set('html', response.message);
    },

    CreateFile: function(response) {
        if (response.css === 'notice-message') {
            cancel();
            fillFilesCombo(currentDir);
        }
        $('simple_response').set('html', response.message);
    },

    UpdateFile: function(response) {
        if (response.css === 'notice-message') {
            cancel();
            fillFilesCombo(currentDir);
        }
        $('simple_response').set('html', response.message);
    },

    DeleteFile: function(response) {
        if (response.css === 'notice-message') {
            cancel();
            fillFilesCombo(currentDir);
        }
        $('simple_response').set('html', response.message);
    }
}

/**
 * Initiates Directory
 */
function initDirectory()
{
    DirectoryAjax.backwardSupport();
    comboFiles = $('files');
    currentDir = Number(DirectoryStorage.fetch('current_dir'));
    changeDirectory(currentDir);
    imgDeleteFile = new Element('img', {src:imgDeleteFile});
    imgDeleteFile.addEvent('click', removeFile);
}

/**
 * Fills files combobox
 */
function fillFilesCombo(parent)
{
    var files = DirectoryAjax.callSync('GetFiles', {'parent':parent});
    comboFiles.options.length = 0;
    files.each(function (file) {
        fileById[file.id] = file;
        comboFiles.options[comboFiles.options.length] = new Option(file.title, file.id);
    });
}

/**
 * Sets selectedId to file/directory ID
 */
function select()
{
    selectedId = comboFiles.value;
    $('form').set('html', '');
}

/**
 * Deselects file and hides the form
 */
function cancel()
{
    selectedId = null;
    $('form').set('html', '');
    comboFiles.selectedIndex = -1;
}

/**
 * Opens directory
 */
function openDirectory()
{
    if (fileById[comboFiles.value].is_dir) {
        changeDirectory(comboFiles.value);
    }
}

/**
 * Changes current path to the given directory
 */
function changeDirectory(id)
{
    selectedId = null;
    currentDir = id;
    DirectoryStorage.update('current_dir', id)
    fillFilesCombo(currentDir);
    updatePath();
}

/**
 * Builds the directory path
 */
function updatePath()
{
    var pathArr = DirectoryAjax.callSync('GetPath', {'id':currentDir}),
        path = $('path').set('html', ''),
        link = new Element('span', {'html':'Root'});
    link.addEvent('click', changeDirectory.pass(0));
    path.grab(link);
    pathArr.reverse().each(function (dir, i) {
        path.appendText(' > ');
        if (i === pathArr.length - 1) {
            path.appendText(dir.title);
        } else {
            link = new Element('span');
            link.set('html', dir.title);
            link.addEvent('click', changeDirectory.pass(dir.id));
            path.grab(link);
        }
    });
}

/**
 * Goes to edit file or directory
 */
function edit()
{
    if (selectedId === null) return;
    var data = DirectoryAjax.callSync('GetFile', {'id':selectedId});
    switch (data.is_dir) {
        case true:
            editDirectory(data);
            break;
        case false:
            editFile(data);
            break;
    }
}

/**
 * Deletes selected directory/file
 */
function _delete()
{
    if (selectedId === null) return;
    var form = $('frm_files');
    if (fileById[comboFiles.value].is_dir) {
        if (confirm('Are you sure you want to delete directory?')) {
            DirectoryAjax.callAsync('DeleteDirectory', {'id':selectedId});
        }
    } else {
        if (confirm('Are you sure you want to delete file?')) {
            DirectoryAjax.callAsync('DeleteFile', {'id':selectedId});
        }
    }
}

/**
 * Brings the directory creation UI up
 */
function newDirectory()
{
    if (cachedDirectoryForm === null) {
        cachedDirectoryForm = DirectoryAjax.callSync('DirectoryForm');
    }
    $('form').set('html', cachedDirectoryForm);
    comboFiles.selectedIndex = -1;
    $('frm_dir').title.focus();
    $('frm_dir').parent.value = currentDir;
}

/**
 * Brings the edit directory UI up
 */
function editDirectory(data)
{
    if (cachedDirectoryForm === null) {
        cachedDirectoryForm = DirectoryAjax.callSync('DirectoryForm');
    }
    $('form').set('html', cachedDirectoryForm);
    var form = $('frm_dir');
    form.id.value = selectedId;
    form.title.value = data.title;
    form.description.value = data.description;
    form.parent.value = data.parent;
}

/**
 * Brings the file creation UI up
 */
function newFile()
{
    if (cachedFileForm === null) {
        cachedFileForm = DirectoryAjax.callSync('FileForm');
    }
    $('form').set('html', cachedFileForm);
    $('tr_file').hide();
    $('frm_upload').show();
    $('frm_file').parent.value = currentDir;
    $('frm_file').title.focus();
    comboFiles.selectedIndex = -1;
}

/**
 * Brings the edit file UI up
 */
function editFile(data)
{
    if (cachedFileForm === null) {
        cachedFileForm = DirectoryAjax.callSync('FileForm');
    }
    $('form').set('html', cachedFileForm);
    var form = $('frm_file');
    form.action.value = 'UpdateFile';
    form.id.value = selectedId;
    form.title.value = data.title;
    form.description.value = data.description;
    form.url.value = data.url;
    form.parent.value = data.parent;
    if (data.filename) {
        setFile(data.filename, data_url);
    } else {
        $('tr_file').hide();
        $('frm_upload').show();
    }
}

/**
 * Uploads file on the server
 */
function uploadFile() {
    var iframe = new Element('iframe', {id:'ifrm_upload'});
    $('frm_files').grab(iframe);
    $('frm_upload').submit();
}

/**
 * Applies uploaded file into the form
 */
function onUpload(response) {
    if (response.type === 'error') {
        alert(response.message);
        $('frm_upload').reset();
    } else {
        var filename = encodeURIComponent(response.message);
        setFile(filename, '');
    }
    $('ifrm_upload').destroy();
}

/**
 * Creates a link to the attached file
 */
function setFile(filename, url)
{
    var link = new Element('a', {'html':filename, 'target':'_blank'});
    if (url !== '') {
        link.href = url + '/' + filename;
    }
    $('filelink').grab(link);
    $('filelink').grab(imgDeleteFile);
    $('tr_file').show();
    $('frm_upload').hide();
    $('filename').value = filename;
}

/**
 * Removes the attached file
 */
function removeFile()
{
    $('filename').value = '';
    $('filelink').set('html', '');
    $('frm_upload').reset();
    $('tr_file').hide();
    $('frm_upload').show();
}

/**
 * Submits directory data to create or update
 */
function submitDirectory()
{
    var action = (selectedId === null)? 'CreateDirectory' : 'UpdateDirectory';
    DirectoryAjax.callAsync(action, $('frm_dir').toQueryString().parseQueryString());
}

/**
 * Submits file data to create or update
 */
function submitFile()
{
    var action = (selectedId === null)? 'CreateFile' : 'UpdateFile';
    DirectoryAjax.callAsync(action, $('frm_file').toQueryString().parseQueryString());
}

/**
 * Brings the share UI up
 */
function share()
{
    if (cachedShareForm === null) {
        cachedShareForm = DirectoryAjax.callSync('GetShareForm', {'fid':selectedId});
    }
    $('form').set('html', cachedShareForm);
    $('groups').selectedIndex = -1;
    //var form = DirectoryAjax.callSync('GetShareForm', {'fid':selectedId});
    //console.log(groups);
}

/**
 * Fetches and displays users of selected group
 */
function toggleUsers(gid)
{
    var container = $('users').empty();
    if (usersByGroup[gid] === undefined) {
        usersByGroup[gid] = DirectoryAjax.callSync('GetUsers', {'gid':gid});
    }
    usersByGroup[gid].each(function (user) {
        var div = new Element('div'),
            input = new Element('input', {type:'checkbox', id:'chk_'+user.id, value:user.id}),
            label = new Element('label', {'for':'chk_'+user.id});
        input.set('checked', (sharedFileUsers[user.id] !== undefined));
        input.addEvent('click', selectUser)
        label.set('html', user.nickname);
        div.adopt(input, label);
        container.grab(div);
    });
}

/**
 * Adds/removes user to/from shares
 */
function selectUser()
{
    if (this.checked) {
        sharedFileUsers[this.value] = this.getNext('label').get('html');
    } else {
        delete sharedFileUsers[this.value];
    }
    updateShareUsers();
}

/**
 * Updates list of users with sharing changes
 */
function updateShareUsers()
{
    var users = $('file_users').empty();
    Object.each(sharedFileUsers, function(name, id) {
        var div = new Element('div', {'id':'usr_'+id});
        div.set('html', name);
        users.grab(div);
    });
}

var DirectoryAjax = new JawsAjax('Directory', DirectoryCallback),
    DirectoryStorage = new JawsStorage('Directory'),
    fileById = {},
    usersByGroup = {},
    sharedFileUsers = {},
    cachedDirectoryForm = null,
    cachedFileForm = null,
    cachedShareForm = null,
    currentDir = 0,
    comboFiles,
    selectedId;
