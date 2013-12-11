<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Category_model extends CI_Model {

    /*
    * SETTINGS
    * TABLE
    *   ->columns
    */

    /*
    * MySQL : 
    *
    *  CREATE TABLE IF NOT EXISTS `categories` (
    *     `id` int(11) NOT NULL AUTO_INCREMENT,
    *     `p_id` int(11) NOT NULL,
    *     `category` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    *     `c_order` int(11) NOT NULL DEFAULT '1',
    *     PRIMARY KEY (`id`)
    *   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
    */


    var $category_table   = 'categories';
    var $category_id      = "id";
    var $category_title   = "category";
    var $category_parent  = "p_id";
    var $category_order   = "c_order";


    function __construct() {
        // Call the Model constructor
        parent::__construct();
        $this->load->helper('url');
    }

    public function getCategory($id) {
        // a classic getter method, returns an object;
        $query = $this->db->get_where($this->category_table, array($this->category_id => $id));
        return $query->row();
    }

    public function getParent($id) {
        $this->db->select('*');
        $this->db->where($this->category_id, $id);
        $query = $this->db->get($this->category_table);
        return $this->getCategory($query->row()->p_id);
    }

    public function addNewCategory($title, $p_id = '0') {
        // adds a new category, sets it's position to 0. 
        // Then updates all the category orders in the same parent stack +1
        // `

        $order = 0;
        $data = array(
           $this->category_title => $title ,
           $this->category_parent => $p_id ,
           $this->category_order => $order
        );

        $this->db->insert($this->category_table, $data);
        $this->db->set($this->category_order, $this->category_order.' + 1', FALSE);
        $this->db->where($this->category_parent, $p_id);
        $this->db->update($this->category_table);

      return TRUE;
    }

    public function deleteCategory($id) {
        //deletes a category. edits category orders.  DELETES CHILDREN
        $query = $this->db->get_where($this->category_table, array($this->category_id => $id));
        $category = $query->row();
        if(@$category->id) {
          $c_order = $category->c_order;
          $p_id = $category->p_id;

          $this->db->delete($this->category_table, array($this->category_id => $id)); 

          $this->db->set($this->category_order, $this->category_order.' - 1', FALSE);
          $this->db->where($this->category_parent, $p_id);
          $this->db->where($this->category_order.' >', $c_order);
          $this->db->update($this->category_table);

          $this->db->delete($this->category_table, array($this->category_parent => $p_id));
      }
      

      return TRUE;
    }

    public function setOrder($order_string) {
        // gets an order string with format : [PID]:id(1),id(2),id(3),id(4),..,id(n)
        $order_bundle = explode(':', $order_string);
        $parent_id = $order_bundle[0];
        $orders_string = $order_bundle[1];
        $orders = explode(',', $orders_string);

        $i = 1;
        foreach ($orders as $order_id) {
          $this->db->set($this->category_order, $i);
          $this->db->where($this->category_id, $order_id);
          $i++;
        }

        return TRUE;
    }

    public function _is_parent($id) {
        //checks a category if it's a parent
        $this->db->select('*');
        $this->db->where($this->category_parent, $id);
        $this->db->from($this->category_table);
        $n = $this->db->count_all_results();

        if($n == 0) {
          return FALSE;
        } else {
          return TRUE;
        }
    }

    public function _is_child($id) {
        //checks a category if it is a child

        if($id === '0') {
          return FALSE;
        }

        $this->db->select('*');
        $this->db->where($this->category_id, $id);
        $query = $this->db->get($this->category_table);
        $category = $query->row();

        if($category->p_id !== '0') {
          return TRUE;
        } else {
          return FALSE;
        }
    }

    public function categoryTree() {
      echo "<ul>";
      $this->printChildren();
      echo "</ul>";
    }


    public function printChildren($p_id = '0') {
        // uses anchor(uri segments, text, attributes)

        $this->db->select('*');
        $this->db->where($this->category_parent,$p_id);
        $this->db->order_by($this->category_order, 'ASC');
        $query = $this->db->get($this->category_table);
        $respond = '';

        foreach ($query->result() as $category) {
          if($this->_is_parent($p_id) && $p_id !== '0') { 
            echo '<ul><li>';
            echo anchor('#',$category->category,'rel= "'.$category->id.'" class="category-menu-link"');
            $this->printChildren($category->id);
            echo '</li></ul>';
          } else {
            echo '<li>';
            echo anchor('#',$category->category,'rel= "'.$category->id.'" class="category-menu-link"');
            $this->printChildren($category->id);
            echo '</li>';
          }
        }
    }
}
