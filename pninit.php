<?php
/**
 * locations
 *
 * @copyright (c) 2008,2010, Locations Development Team
 * @link http://code.zikula.org/locations
 * @author Steffen Voß
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package locations
 */

// preload common used classes
Loader::requireOnce('modules/Locations/common.php');

/**
 * initialise the locations module
 *
 * This function is only ever called once during the lifetime of a particular
 * module instance
 * This function MUST exist in the pninit file for a module
 *
 * @author       Steffen Voß
 * @return       bool       true on success, false otherwise
 */
function locations_init()
{
    $dom = ZLanguage::getModuleDomain('locations');

    // create the location table
    if (!DBUtil::createTable('locations_location')) {
        return false;
    }

    // set up all our module vars with initial values
    $sessionValue = SessionUtil::getVar('locations_GoogleMapsAPIKey');
    pnModSetVar('locations', 'GoogleMapsAPIKey', (($sessionValue <> false) ? $sessionValue : ''));
    SessionUtil::delVar('locations_GoogleMapsAPIKey');
    $sessionValue = SessionUtil::getVar('locations_DefaultCity');
    pnModSetVar('locations', 'DefaultCity', (($sessionValue <> false) ? $sessionValue : 'Kiel'));
    SessionUtil::delVar('locations_DefaultCity');
    $sessionValue = SessionUtil::getVar('locations_DefaultState');
    pnModSetVar('locations', 'DefaultState', (($sessionValue <> false) ? $sessionValue : 'Schleswig-Holstein'));
    SessionUtil::delVar('locations_DefaultState');
    $sessionValue = SessionUtil::getVar('locations_DefaultCountry');
    pnModSetVar('locations', 'DefaultCountry', (($sessionValue <> false) ? $sessionValue : 'Deutschland'));
    SessionUtil::delVar('locations_DefaultCountry');
    pnModSetVar('locations', 'pagesize', 25);
    pnModSetVar('locations', 'mapWidth', '100%');
    pnModSetVar('locations', 'mapHeight', '500px');
    pnModSetVar('locations', 'mapDistanceZip', 10);
    pnModSetVar('locations', 'mapZoomDisplay', 16);
    pnModSetVar('locations', 'mapDistanceDisplay', 0.5);
    pnModSetVar('locations', 'enablecategorization', true);
    pnModSetVar('locations', 'permalinkformat', '%city%/%urltitle%');

    // create the default data for locations
    if (!locations_defaultdata()) {
        LogUtil::registerError(__('Error! Could not set up the default module vars.', $dom));
    }

    // create main category
    if (!_locations_createDefaultCategory()) {
        LogUtil::registerError(__('Error! Could not create the default categories', $dom));
    }

    // Initialisation successful
    return true;
}

/**
 * upgrade the locations module from an old version
 *
 * This function can be called multiple times
 * This function MUST exist in the pninit file for a module
 *
 * @author       Steffen Voß
 * @param       int        $oldversion version to upgrade from
 * @return      bool       true on success, false otherwise
 */
function locations_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion){
        case '1.0':
            return locations_upgrade('1.1.0');
        case '1.1.0':
        case '1.2.0':
            if (!DBUtil::dropTable('locations_description')) {
                return false;
            }
            if (!DBUtil::dropTable('locations_image')) {
                return false;
            }
            if (!DBUtil::changeTable('locations_location')) {
                return false;
            }
            // set new ModVar
            pnModSetVar('locations', 'pagesize', 25);
            pnModSetVar('locations', 'mapWidth', '100%');
            pnModSetVar('locations', 'mapHeight', '500px');
            pnModSetVar('locations', 'mapDistanceZip', 10);
            pnModSetVar('locations', 'mapZoomDisplay', 16);
            pnModSetVar('locations', 'mapDistanceDisplay', 0.5);
            pnModSetVar('locations', 'enablecategorization', true);
            pnModSetVar('locations', 'permalinkformat', '%city%/%urltitle%');

            // create main category
            _locations_createDefaultCategory();
            // populate permalinks for existing content
            _locations_createPermalinks();
        case '2.0.0':
    }


    // Update successful
    return true;
}

/**
 * delete the locations module
 * This function is only ever called once during the lifetime of a particular
 * module instance
 * This function MUST exist in the pninit file for a module
 *
 * @author       Steffen Voß
 * @return       bool       true on success, false otherwise
 */
function locations_delete()
{
    if (!DBUtil::dropTable('locations_location')) {
        return false;
    }

    // remove all module vars
    pnModDelVar('locations');

    // Deletion successful
    return true;
}

/**
 * create the default data for locations
 *
 * This function is only ever called once during the lifetime of a particular
 * module instance
 *
 * @author       Steffen Voß
 * @return       bool       true on success, false otherwise
 */
function locations_defaultdata()
{
    $dom = ZLanguage::getModuleDomain('locations');

    // ensure that tables are cleared
    if (!DBUtil::deleteWhere('locations_location', '1=1')) {
        return false;
    }

    // define default data for location table
    $records = array(
    array(
            'locationid' => '1',
            'name' => 'Schaubude',
            'urltitle' => 'Schaubude',
            'street' => 'Legienstraße 40',
            'zip' => '24103',
            'city' => 'Kiel',
            'phone' => '',
            'fax' => '',
            'url' => 'http://www.kieler-schaubude.de',
            'email' => '',
            'country' => 'Deutschland',
            'state' => 'Schleswig-Holstein',
            'latlng' => '54.327828,10.129710',
            'description' => '',
            'logo' => ''),
    array(
            'locationid' => '2',
            'name' => 'weltruf',
            'urltitle' => 'weltruf',
            'street' => 'Lange Reihe 21',
            'zip' => '24103',
            'city' => 'Kiel',
            'phone' => '',
            'fax' => '',
            'url' => 'http://www.weltruf-kiel.de',
            'email' => '',
            'country' => 'Deutschland',
            'state' => 'Schleswig-Holstein',
            'latlng' => '54.319375,10.132035',
            'description' => '',
            'logo' => ''),
    array(
            'locationid' => '3',
            'name' => 'Metro Kino',
            'urltitle' => 'Metro-Kino',
            'street' => 'Holtenauerstraße 162',
            'zip' => '24105',
            'city' => 'Kiel',
            'phone' => '',
            'fax' => '',
            'url' => 'http://www.metrokino-kiel.de',
            'email' => '',
            'country' => 'Deutschland',
            'state' => 'Schleswig-Holstein',
            'latlng' => '54.339814,10.133697',
            'description' => 'metro-Kino im Schloßhof Kiel',
            'logo' => ''),
    array(
            'locationid' => '4',
            'name' => 'Lunatique',
            'urltitle' => 'Lunatique',
            'street' => 'Ziegelteich 10',
            'zip' => '24103',
            'city' => 'Kiel',
            'phone' => '',
            'fax' => '',
            'url' => '',
            'email' => '',
            'country' => 'Deutschland',
            'state' => 'Schleswig-Holstein',
            'latlng' => '54.318646,10.132500',
            'description' => '',
            'logo' => ''),
    array(
            'locationid' => '5',
            'name' => 'Prinz Willy',
            'urltitle' => 'Prinz-Willy',
            'street' => 'Lutherstraße 9',
            'zip' => '24114',
            'city' => 'Kiel',
            'phone' => '',
            'fax' => '',
            'url' => 'http://www.prinzwilly.de',
            'email' => '',
            'country' => 'Deutschland',
            'state' => 'Schleswig-Holstein',
            'latlng' => '54.315341,10.117084',
            'description' => 'Live Musik Club Kiel',
            'logo' => ''),
    array(
            'locationid' => '6',
            'name' => 'Volksbank Gronau-Ahaus eG',
            'urltitle' => 'Volksbank-Gronau-Ahaus',
            'street' => 'Neustraße 42-46',
            'zip' => '48599',
            'city' => 'Gronau',
            'phone' => '',
            'fax' => '',
            'url' => 'http://www.vbga.de',
            'email' => '',
            'country' => 'Deutschland',
            'state' => 'Nordrhein-Westfalen',
            'latlng' => '52.210751,7.026376',
            'description' => '<p>Die Volksbank Gronau-Ahaus eG ist eine Genossenschaftsbank mit Sitz in Gronau. Ihr Geschäftsgebiet umfasst den Großteil des Kreises Borken. <em>(Quelle: http://de.wikipedia.org/wiki/Volksbank_Gronau-Ahaus)</em></p>',
            'logo' => ''),
    array(
            'locationid' => '7',
            'name' => 'rock-n-popmuseum',
            'urltitle' => 'rock-n-popmuseum',
            'street' => 'Udo-Lindenberg-Platz 1',
            'zip' => '48599',
            'city' => 'Gronau',
            'phone' => '',
            'fax' => '',
            'url' => 'http://www.rock-popmuseum.de',
            'email' => '',
            'country' => 'Deutschland',
            'state' => 'Nordrhein-Westfalen',
            'latlng' => '52.21504,7.02474',
            'description' => '<p> Das Museum erzählt die Kulturgeschichte der Popularmusik im 20. Jahrhundert, unterstützt durch modernste Medientechnologie.</p>',
            'logo' => ''),
    array(
            'locationid' => '8',
            'name' => 'Jugend- & Kulturcafé LOGO',
            'urltitle' => 'Logo-Ahaus',
            'street' => 'Parallelstraße 1',
            'zip' => '48683',
            'city' => 'Ahaus',
            'phone' => '',
            'fax' => '',
            'url' => 'http://www.logo-ahaus.de',
            'email' => '',
            'country' => 'Deutschland',
            'state' => 'Nordrhein-Westfalen',
            'latlng' => '52.082069,7.015918',
            'description' => '<p>Das Logo in Ahaus hat sich in den vergangenen 15 Jahren als feste Größe im Veranstaltungsbereich im Münsterland etabliert. Seit seiner Eröffnung im Jahre 1994 fanden mehrere hundert Veranstaltungen vor mehreren 10.000 Zuschauern statt.</p>',
            'logo' => ''),
    array(
            'locationid' => '9',
            'name' => 'Schloss Ahaus',
            'urltitle' => 'Schloss-Ahaus',
            'street' => 'Sümmermannplatz',
            'zip' => '48683',
            'city' => 'Ahaus',
            'phone' => '',
            'fax' => '',
            'url' => 'http://www.ahaus.de/schloss_ahaus.0.html',
            'email' => '',
            'country' => 'Deutschland',
            'state' => 'Nordrhein-Westfalen',
            'latlng' => '52.075139,7.009319',
            'description' => '<p>Schloss Ahaus ist ein ehemaliges Residenzschloss der Fürstbischöfe von Münster in Ahaus.</p>',
            'logo' => ''),
    array(
            'locationid' => '10',
            'name' => 'Stadt- und Tierpark',
            'urltitle' => 'Stadt-und-Tierpark',
            'street' => 'Brändströmstraße 25',
            'zip' => '48599',
            'city' => 'Gronau',
            'phone' => '',
            'fax' => '',
            'url' => 'http://www.gronau.de/index.phtml?NavID=1486.204&La=1',
            'email' => '',
            'country' => 'Deutschland',
            'state' => 'Nordrhein-Westfalen',
            'latlng' => '52.207070,7.02274',
            'description' => '<p>Idyllisch gelegen und mitten im Herzen der Stadt befindet sich der weitläufige Stadt- und Tierpark. Er ist die grüne Lunge von Gronau und im Sommer Treffpunkt von Jung und Alt.</p>',
            'logo' => ''),
    array(
            'locationid' => '11',
            'name' => 'Dreiländersee',
            'urltitle' => 'Dreilaendersee',
            'street' => 'Brechter Weg 15',
            'zip' => '48599',
            'city' => 'Gronau',
            'phone' => '',
            'fax' => '',
            'url' => 'http://www.driland-see.de/',
            'email' => '',
            'country' => 'Deutschland',
            'state' => 'Nordrhein-Westfalen',
            'latlng' => '52.24004,7.07689',
            'description' => '<p>Der Dreiländersee ist ein Mekka für Wassersportler. Hier sind – vom Nichtstun und Sonne tanken über Schwimmen bis zum Surfen und Segeln – eigentlich alle Aktivitäten möglich.</p>',
            'logo' => '')
    );
    // insert it into the location table
    DBUtil::insertObjectArray($records, 'locations_location', 'locationid', true);
    // insertion successful
    return true;
}

/**
 * interactive installation procedure
 *
 * @author       Steffen Voß
 * @return       pnRender output
 */
function locations_init_interactiveinit()
{
    if (!SecurityUtil::checkPermission('::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $render = pnRender::getInstance('locations', false);
    return $render->fetch('locations_init_interactive.htm');
}

/**
 * interactive installation procedure step 2
 *
 * @author       Steffen Voß
 * @return       pnRender output
 */
function locations_init_interactiveinitstep2()
{
    if (!SecurityUtil::checkPermission('::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $submit = FormUtil::getPassedValue('submit', null, 'POST');
    if (!$submit) {
        $render = pnRender::getInstance('locations', false);
        $render->assign('authid', SecurityUtil::generateAuthKey('locations'));

        return $render->fetch('locations_init_step2.htm');
    }

    if (!SecurityUtil::confirmAuthKey()) {
        LogUtil::registerAuthidError();
        return pnRedirect(pnModURL('Modules', 'admin', 'view'));
    }

    $formValue = FormUtil::getPassedValue('GoogleMapsAPIKey', '', 'POST');
    SessionUtil::setVar('locations_GoogleMapsAPIKey', $formValue);

    $formValue = FormUtil::getPassedValue('DefaultCity', '', 'POST');
    SessionUtil::setVar('locations_DefaultCity', $formValue);

    $formValue = FormUtil::getPassedValue('DefaultState', '', 'POST');
    SessionUtil::setVar('locations_DefaultState', $formValue);

    $formValue = FormUtil::getPassedValue('DefaultCountry', '', 'POST');
    SessionUtil::setVar('locations_DefaultCountry', $formValue);


    $activate = (bool) FormUtil::getPassedValue('activate', false, 'POST');
    $activate = (!empty($activate)) ? true : false;

    return pnRedirect(pnModURL('locations', 'init', 'interactiveinitstep3', array('activate' => $activate)));
}


/**
 * interactive installation procedure step 3
 *
 * @author       Steffen Voß
 * @return       pnRender output
 */
function locations_init_interactiveinitstep3()
{
    if (!SecurityUtil::checkPermission('::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    $activate = (bool) FormUtil::getPassedValue('activate', false, 'POST');

    $render = pnRender::getInstance('locations', false);
    $render->assign('csrftoken', SecurityUtil::generateCsrfToken());
    $render->assign('activate', $activate);
    return $render->fetch('locations_init_step3.htm');
}

function _locations_createDefaultCategory($regpath = '/__SYSTEM__/Modules')
{
    $dom = ZLanguage::getModuleDomain('locations');

    // load necessary classes
    Loader :: loadClass('CategoryUtil');
    Loader :: loadClassFromModule('Categories', 'Category');
    Loader :: loadClassFromModule('Categories', 'CategoryRegistry');

    // get the language file
    $lang = ZLanguage::getLanguageCode();

    // get the category path for which we're going to insert our place holder category
    $rootcat = CategoryUtil :: getCategoryByPath($regpath);

    // create placeholder for all our migrated categories
    $cat = new PNCategory();
    $cat->setDataField('parent_id', $rootcat['id']);
    $cat->setDataField('name', 'locations');
    $cat->setDataField('value', '-1');
    $cat->setDataField('display_name', array ($lang => __('Locations', $dom)));
    $cat->setDataField('display_desc', array ($lang => __('Database for all kinds of locations', $dom)));
    $cat->setDataField('security_domain', $rootcat['security_domain']);

    if (!$cat->validate('admin')) {
        return false;
    }
    $cat->insert();
    $cat->update();

    // get the category path for which we're going to insert our upgraded categories
    $rootcat = CategoryUtil :: getCategoryByPath('/__SYSTEM__/Modules/Locations');

    // create an entry in the categories registry
    $registry = new PNCategoryRegistry();
    $registry->setDataField('modname', 'locations');
    $registry->setDataField('table', 'locations_location');
    $registry->setDataField('property', 'Type');
    $registry->setDataField('category_id', $rootcat['id']);
    $registry->insert();

    return true;
}

function _locations_createPermalinks()
{
    // get all the ID and permalink of the table
    $data = DBUtil::selectObjectArray('locations_location', '', '', -1, -1, 'locationid', null, null, array('locationid', 'name', 'urltitle'));

    // loop the data searching for non equal permalinks
    $perma = '';
    foreach (array_keys($data) as $locationid) {
        $perma = DataUtil::formatPermalink($data[$locationid]['name']);
        if ($data[$locationid]['urltitle'] != $perma) {
            $data[$locationid]['urltitle'] = $perma;
        } else {
            unset($data[$locationid]);
        }
    }

    if (empty($data)) {
        return true;
        // store the modified permalinks
    } elseif (DBUtil::updateObjectArray($data, 'locations_location', 'locationid')) {
        // let the calling process know that we have finished successfully
        return true;
    } else {
        return false;
    }
}

