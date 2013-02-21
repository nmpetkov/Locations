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

/**
 * Define Class path
 */
define('LOCATIONS_FILTERUTIL_CLASS_PATH', 'modules/Locations/classes/FilterUtil');

Loader::loadClass('locationsFilterUtil_Plugin', LOCATIONS_FILTERUTIL_CLASS_PATH);
Loader::loadClass('locationsFilterUtil_Common', LOCATIONS_FILTERUTIL_CLASS_PATH);

/**
 * This class adds a Pagesetter like filter feature to a particular module
 */
class locationsFilterUtil extends locationsFilterUtil_Common {

    /**
     * The Input variable name
     */
    private $varname;

    /**
     * Plugin object
     */
    private $plugin;

    /**
     * Filter object holder
     */
    private $obj;

    /**
     * Filter string holder
     */
    private $filter;

    /**
     * Filter SQL holder
     */
    private $sql;

    /**
     * Constructor
     *
     * @access public
     * @param string $args['varname'] Name of filter's request variable (DEFAULT: filter)
     * @param array $args['plugins'] An array with plugin informations
     * @return object locationsFilterUtil object
     */
    public function __construct($args = array())
    {
        $this->setVarName('filter');
        parent::__construct($args);
        $this->plugin = new locationsFilterUtil_Plugin($this->addCommon(), array('Default' => array()));

        if (isset($args['plugins'])) {
            $this->plugin->loadPlugins($args['plugins']);
        } if (isset($args['varname'])) {
            $this->setVarName($args['varname']);
        }

        return $this;
    }

    /**
     * Set name of input variable of filter
     *
     * @access public
     * @param string $name name of input variable
     * @return bool true on success, false otherwise
     */
    public function setVarName($name)
    {
        if (!is_string($name))
        return false;

        $this->varname = $name;
    }

    //++++++++++++++++ Object handling +++++++++++++++++++

    /**
     * strip brackets around a filterstring
     *
     * @access private
     * @param string $filter Filterstring
     * @return string edited filterstring
     */
    private function stripBrackets($filter)
    {
        if (substr($filter, 0, 1) == '(' && substr($filter, -1) == ')') {
            return substr($filter, 1, -1);
        }

        return $filter;
    }

    /**
     * Create a condition object out of a string
     *
     * @access private
     * @param string $filter Condition string
     * @return array condition object
     */
    private function makeCondition($filter)
    {
        if (strpos($filter, ':'))
        $parts = explode(':', $filter, 3);
        elseif (strpos($filter, '^'))
        $parts = explode('^', $filter, 3);

        if (isset($parts[2]) && substr($parts[2], 0, 1) == '$' && ($value = pnVarCleanFromInput(substr($parts[2], 1))) != NULL && !empty($value)) {
            $obj['value'] = $value;
        } else {
            $obj['value'] = $parts[2];
        }

        $obj['field'] = $parts[0];
        $obj['op'] = $parts[1];

        $obj = $this->plugin->replace($obj['field'], $obj['op'], $obj['value']);

        return $obj;
    }

    /**
     * Help function to generate an object out of a string
     *
     * @access private
     * @param string $filter    Filterstring
     */
    private function GenObjectRecursive($filter)
    {
        $obj = array();
        $regex = '~^([,\*])?(\(.*\)|[^\(\),\*]+)(?:[,\*](?:\(.*\)|[^\(\),\*]+))*$~U';
        $cycle = 0;
        while (!empty($filter)) {
            preg_match($regex, $filter, $match);
            $op = str_replace(array(',', '*'),array('and', 'or'), $match['1']);
            if (!$op) $op = 0;
            else $op .= $cycle++;
            $string = $this->stripBrackets($match[2]);
            if (strpos($string, ',') || strpos($string, '*')) {
                $sub = $this->GenObjectRecursive($string);
                if (!empty($sub) && is_array($sub)) {
                    $obj[$op] = $sub;
                }
            } elseif (($cond = $this->makeCondition($string)) !== false) {
                $obj[$op] = $cond;
            }
            $filter = substr($filter, strlen($match[2])+strlen($match[1]));
        }

        return $obj;
    }

    /**
     * Generate the filter object from a string
     *
     * @access public
     */
    public function GenObject()
    {
        $this->obj = $this->GenObjectRecursive($this->getFilter());
    }

    /**
     * Get the filter object
     *
     * @access public
     * @return array filter object
     */
    public function GetObject()
    {
        if (!isset($this->obj) || !is_array($this->obj)) {
            $this->GenObject();
        }
        return $this->obj;
    }

    //---------------- Object handling ---------------------
    //++++++++++++++++ Filter handling +++++++++++++++++++++
    /**
     * Get all filters from Input
     *
     * @return array Array of filters
     */
    public function GetFiltersFromInput ()
    {
        $i = 1;
        $filter = array();

        // Get unnumbered filter string
        $filterStr = pnVarCleanFromInput($this->varname);
        if (!empty($filterStr))
        {
            $filter[] = $filterStr;
        }

        // Get filter1 ... filterN
        while (true)
        {
            $filterURLName = $this->varname . "$i";
            $filterStr     = pnVarCleanFromInput($filterURLName);

            if (empty($filterStr))
            break;

            $filter[] = $filterStr;
            ++$i;
        }

        return $filter;
    }


    /**
     * Get filterstring
     *
     * @access public
     * @return string $filter Filterstring
     */
    public function getFilter()
    {
        if (!isset($this->filter) || empty($this->filter)) {
            $filter = $this->GetFiltersFromInput();
            if (is_array($filter) && count($filter) > 0) {
                $this->filter = "(".implode(')*(', $filter).")";
            }
        }

        if ($this->filter == '()')
        $this->filter = '';

        return $this->filter;
    }

    /**
     * Set filterstring
     *
     * @access public
     * @param mixed $filter Filter string or array
     */
    public function setFilter($filter)
    {
        if (is_array($filter)) {
            $this->filter = "(".implode(')*(', $filter).")";
        } else {
            $this->filter = $filter;
        }
        $this->obj = false;
        $this->sql = false;

    }

    //--------------- Filter handling ----------------------
    //+++++++++++++++ SQL Handling +++++++++++++++++++++++++

    /**
     * Help function for generate the filter SQL from a Filter-object
     *
     * @access private
     * @param array $obj Object array
     * @return array Where and Join sql
     */
    private function GenSqlRecursive($obj)
    {
        if (!is_array($obj) || count($obj) == 0) {
            return '';
        }
        if (isset($obj['field']) && !empty($obj['field'])) {
            $obj['value'] = pnVarPrepForStore($obj['value']);
            return $this->plugin->getSQL($obj['field'], $obj['op'], $obj['value']);
        } else {
            $join = '';
            if (isset($obj[0]) && is_array($obj[0])) {
                $sub = $this->GenSqlRecursive($obj[0]);
                if (!empty($sub['where']))
                $where .= $sub['where'];
                if (!empty($sub['join']))
                $join .= $sub['join'];
                unset($obj[0]);
            }
            foreach ($obj as $op => $tmp) {
                $op = strtoupper(substr($op, 0, 3)) == 'AND' ? 'AND' : 'OR';
                if (strtoupper($op) == 'AND' || strtoupper($op) == 'OR') {
                    $sub = $this->GenSqlRecursive($tmp);
                    if (!empty($sub['where']))
                    $where .= ' ' . strtoupper($op) . ' ' . $sub['where'];
                    if (!empty($sub['join']))
                    $join .= $sub['join'];
                }
            }
        }
        return array('where' => (empty($where)?'':"(\n $where \n)"),
                     'join'  => $join);
    }

    /**
     * Generate where/join SQL
     *
     * access public
     */
    public function GenSql()
    {
        $object = $this->GetObject();
        $this->sql = $this->GenSqlRecursive($object);
    }

    /**
     * Get where/join SQL
     *
     * @access public
     * @return array Array with where and join
     */
    public function GetSQL()
    {
        if (!isset($this->sql) || !is_array($this->sql)) {
            $this->GenSQL();
        }
        return $this->sql;
    }
}
