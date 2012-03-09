<?php
/**
 * Copyright 2005-2010 MERETHIS
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give MERETHIS
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of MERETHIS choice, provided that
 * MERETHIS also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 * SVN : $URL$
 * SVN : $Id$
 */
require_once "centreonObject.class.php";
require_once "Centreon/Object/Acl/Group.php";
require_once "Centreon/Object/Acl/Menu.php";
require_once "Centreon/Object/Relation/Acl/Group/Menu.php";

/**
 * Class for managing ACL Menu rules
 * @author sylvestre
 *
 */
class CentreonACLMenu extends CentreonObject
{
    const ORDER_UNIQUENAME        = 0;
    const ORDER_ALIAS             = 1;
    const LEVEL_1                 = 0;
    const LEVEL_2                 = 1;
    const LEVEL_3                 = 2;
    const LEVEL_4                 = 3;
    const PARENT_MENU_NOT_DEFINED = "Parent menu must be defined";
    protected $relObject;
    protected $aclGroupObj;

 	/**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->object = new Centreon_Object_Acl_Menu();
        $this->aclGroupObj = new Centreon_Object_Acl_Group();
        $this->relObject = new Centreon_Object_Relation_Acl_Group_Menu();
        $this->params = array('acl_topo_activate' => '1');
        $this->nbOfCompulsoryParams = 2;
        $this->activateField = "acl_topo_activate";
    }

    /**
     * Add action
     *
     * @param string $parameters
     * @return void
     * @throws CentreonClapiException
     */
    public function add($parameters)
    {
        $params = explode($this->delim, $parameters);
        if (count($params) < $this->nbOfCompulsoryParams) {
            throw new CentreonClapiException(self::MISSINGPARAMETER);
        }
        $addParams = array();
        $addParams[$this->object->getUniqueLabelField()] = $params[self::ORDER_UNIQUENAME];
        $addParams['acl_topo_alias'] = $params[self::ORDER_ALIAS];
        $this->params = array_merge($this->params, $addParams);
        $this->checkParameters();
        parent::add();
    }

    /**
     * Set Parameters
     *
     * @param string $parameters
     * @return void
     * @throws Exception
     */
    public function setparam($parameters)
    {
        $params = explode($this->delim, $parameters);
        if (count($params) < self::NB_UPDATE_PARAMS) {
            throw new CentreonClapiException(self::MISSINGPARAMETER);
        }
        if (($objectId = $this->getObjectId($params[self::ORDER_UNIQUENAME])) != 0) {
            if ($params[1] == "comment") {
                $params[1] = "acl_comments";
            } else {
                $params[1] = "acl_topo_".$params[1];
            }
            $updateParams = array($params[1] => $params[2]);
            parent::setparam($objectId, $updateParams);
        } else {
            throw new CentreonClapiException(self::OBJECT_NOT_FOUND);
        }
    }

    /**
     * Show
     *
     * @param string $parameters
     * @return void
     */
    public function show($parameters = null)
    {
        $filters = array();
        if (isset($parameters)) {
            $filters = array($this->object->getUniqueLabelField() => "%".$parameters."%");
        }
        $params = array("acl_topo_id", "acl_topo_name", "acl_topo_alias", "acl_comments", "acl_topo_activate");
        $paramString = str_replace("acl_topo_", "", implode($this->delim, $params));
        $paramString = str_replace("acl_", "", $paramString);
        $paramString = str_replace("comments", "comment", $paramString);
        echo $paramString . "\n";
        $elements = $this->object->getList($params, -1, 0, null, null, $filters);
        foreach ($elements as $tab) {
            $str = "";
            foreach ($tab as $key => $value) {
                $str .= $value . $this->delim;
            }
            $str = trim($str, $this->delim) . "\n";
            echo $str;
        }
    }

    /**
     * Split params
     *
     * @param string $parameters
     * @return array
     * @throws CentreonClapiException
     */
    protected function splitParams($parameters)
    {
        $params = explode($this->delim, $parameters);
        if (count($params) < 2) {
            throw new CentreonClapiException(self::MISSINGPARAMETER);
        }
        $aclMenuId = $this->object->getIdByParameter($this->object->getUniqueLabelField(), array($params[0]));
        if (!count($aclMenuId)) {
            throw new CentreonClapiException(self::OBJECT_NOT_FOUND);
        }
        $levels = array();
        $menus = array();
        $toopologies = array();
        $levels[self::LEVEL_1] = $params[1];
        if (isset($params[2])) {
            $levels[self::LEVEL_2] = $params[2];
        }
        if (isset($params[3])) {
            $levels[self::LEVEL_3] = $params[3];
        }
        if (isset($params[4])) {
            $levels[self::LEVEL_4] = $params[4];
        }
        foreach ($levels as $level => $menu) {
            if ($menu) {
                switch ($level) {
                    case self::LEVEL_1 :
                        $length = 1;
                        break;
                    case self::LEVEL_2 :
                        $length = 3;
                        break;
                    case self::LEVEL_3 :
                        $length = 5;
                        break;
                    case self::LEVEL_4 :
                        $length = 7;
                        break;
                    default: break;
                }
                if (is_numeric($menu)) {
                    $sql = "SELECT topology_id, topology_page
							FROM topology
							WHERE topology_page = ?
							AND LENGTH(topology_page) = ?";
                    $res = $this->db->query($sql, array($menu, $length));
                } else {
                    if ($level == self::LEVEL_1) {
                        $sql = "SELECT topology_id, topology_page
                        		FROM topology
                        		WHERE topology_name = ?
                        		AND LENGTH(topology_page) = ?
                        		AND topology_parent IS NULL";
                        $res = $this->db->query($sql, array($menu, $length));
                    } else {
                        $sql = "SELECT topology_id, topology_page
                        		FROM topology
                        		WHERE topology_name = ?
                        		AND LENGTH(topology_page) = ?
                        		AND topology_parent = ?";
                        $res = $this->db->query($sql, array($menu, $length, $topologies[($level-1)]));
                    }
                }
                $row = $res->fetch();
                if (!isset($row['topology_id'])) {
                    throw new CentreonClapiException(self::OBJECT_NOT_FOUND . ":" . $menu);
                }
                unset($res);
                $menus[$level] = $row['topology_id'];
                $topologies[$level] = $row['topology_page'];
            } else {
                break;
            }
        }
        return array($aclMenuId[0], $menus, $topologies);
    }

    /**
     * Get Acl Group
     *
     * @param string $parameters
     * @return void
     * @throws CentreonClapiException
     */
    public function getaclgroup($aclMenuName)
    {
        if (!isset($aclMenuName) || !$aclMenuName) {
            throw new CentreonClapiException(self::MISSINGPARAMETER);
        }
        $aclMenuId = $this->object->getIdByParameter($this->object->getUniqueLabelField(), array($aclMenuName));
        if (!count($aclMenuId)) {
            throw new CentreonClapiException(self::OBJECT_NOT_FOUND);
        }
        $groupIds = $this->relObject->getacl_group_idFromacl_topology_id($aclMenuId[0]);
        echo "id;name" . "\n";
        if (count($groupIds)) {
            foreach($groupIds as $groupId) {
                $result = $this->aclGroupObj->getParameters($groupId, $this->aclGroupObj->getUniqueLabelField());
                echo $groupId . $this->delim . $result[$this->aclGroupObj->getUniqueLabelField()] . "\n";
            }
        }
    }

    /**
     * Process children of topology
     * Recursive method
     *
     * @param string $action
     * @param int $aclMenuId
     * @param int $parentTopologyId
     * @return void
     */
    protected function processChildrenOf($action = "grant", $aclMenuId, $parentTopologyId)
    {
        $sql = "SELECT topology_id, topology_page FROM topology WHERE topology_parent = ?";
        $res = $this->db->query($sql, array($parentTopologyId));
        $rows = $res->fetchAll();
        foreach ($rows as $row) {
            $this->db->query("DELETE FROM acl_topology_relations WHERE acl_topo_id = ? AND topology_topology_id = ?",
                                array($aclMenuId, $row['topology_id']));
            if ($action == "grant") {
                $this->db->query("INSERT INTO acl_topology_relations (acl_topo_id, topology_topology_id) VALUES (?, ?)",
                                array($aclMenuId, $row['topology_id']));
            }
            $this->processChildrenOf($action, $aclMenuId, $row['topology_page']);
        }
    }

    /**
     * Grant menu
     *
     * @param string $parameters
     * @return void
     */
    public function grant($parameters)
    {
        list($aclMenuId, $menus, $topologies) = $this->splitParams($parameters);
        foreach($menus as $level => $menuId) {
            $this->db->query("DELETE FROM acl_topology_relations WHERE acl_topo_id = ? AND topology_topology_id = ?",
                                array($aclMenuId, $menuId));
            $this->db->query("INSERT INTO acl_topology_relations (acl_topo_id, topology_topology_id) VALUES (?, ?)",
                            array($aclMenuId, $menuId));
            if (!isset($menus[$level + 1]) && $level != self::LEVEL_4) {
                $this->processChildrenOf("grant", $aclMenuId, $topologies[$level]);
            }
        }
    }

    /**
     * Revoke menu
     *
     * @param string $parameters
     * @return void
     */
    public function revoke($parameters)
    {
        list($aclMenuId, $menus, $topologies) = $this->splitParams($parameters);
        foreach($menus as $level => $menuId) {
            if (!isset($menus[$level + 1])) {
                $this->db->query("DELETE FROM acl_topology_relations WHERE acl_topo_id = ? AND topology_topology_id = ?",
                                array($aclMenuId, $menuId));
                $this->processChildrenOf("revoke", $aclMenuId, $topologies[$level]);
            }
        }
    }
}