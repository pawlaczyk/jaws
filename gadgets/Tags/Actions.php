<?php
/**
 * Tags Actions
 *
 * @category    GadgetActions
 * @package     Tags
 */

/**
 * Index actions
 */
$actions['Similarity'] = array(
    'layout' => true,
    'parametric' => true,
    'file'   => 'Tags',
);
$actions['TagCloud'] = array(
    'normal' => true,
    'layout' => true,
    'parametric' => true,
    'file'   => 'Tags',
    'navigation' => array(
        'order' => 3
    ),
);
$actions['ViewTag'] = array(
    'normal' => true,
    'file'   => 'Tags',
);
$actions['ManageTags'] = array(
    'normal' => true,
    'file'   => 'Manage',
    'navigation' => array(
        'order' => 0
    ),
);
$actions['EditTagUI'] = array(
    'normal' => true,
    'file'   => 'Manage',
);
$actions['UpdateTag'] = array(
    'standalone' => true,
    'file'       => 'Manage',
);
$actions['DeleteTags'] = array(
    'standalone' => true,
    'file'       => 'Manage',
);
$actions['MergeTags'] = array(
    'standalone' => true,
    'file'       => 'Manage',
);

/**
 * Admin actions
 */
$admin_actions['Tags'] = array(
    'normal' => true,
    'file' => 'Tags',
);
$admin_actions['Properties'] = array(
    'normal' => true,
    'file' => 'Settings',
);
$admin_actions['SearchTags'] = array(
    'standalone' => true,
    'file' => 'Ajax',
);
$admin_actions['SizeOfTagsSearch'] = array(
    'standalone' => true,
    'file' => 'Ajax',
);
$admin_actions['GetGadgetActions'] = array(
    'standalone' => true,
    'file' => 'Ajax',
);
$admin_actions['GetTag'] = array(
    'standalone' => true,
    'file' => 'Ajax',
);
$admin_actions['AddTag'] = array(
    'standalone' => true,
    'file' => 'Ajax',
);
$admin_actions['UpdateTag'] = array(
    'standalone' => true,
    'file' => 'Ajax',
);
$admin_actions['DeleteTags'] = array(
    'standalone' => true,
    'file' => 'Ajax',
);
$admin_actions['MergeTags'] = array(
    'standalone' => true,
    'file' => 'Ajax',
);
$admin_actions['SaveSettings'] = array(
    'standalone' => true,
    'file' => 'Ajax',
);
