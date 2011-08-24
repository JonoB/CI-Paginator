<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Paginator Class
 *
 * This class is based heavily on the CI 2.0 core Pagination Class.
 * However, if you pass in the right parameters, it will hopefully work some magic
 * for you and create a table with sortable headers, as well as selectable page sizes
 *
 * Although the core Pagination class works off the offset param, this class uses the the
 * page number and page size, as well as the sort order and sort direction.
 * So, for example, products/index/2/25/code/desc means that I want to show 
 * the 2nd page, 25 records, sorted by code in descending order
 *
 * Note that this class does not work with query strings
 *
 * @license		DBAD (http://philsturgeon.co.uk/code/dbad-license)
 * @author		JonoB
 *
 * @file		Paginator.php
 * @version		0.9
 * @date		15/08/2011
 */

class CI_Paginator 
{
	var $base_url               = ''; // The page we are linking to
	var $prefix                 = ''; // A custom prefix added to the path.
	var $suffix                 = ''; // A custom suffix added to the path.
	var $total_rows             = ''; // Total number of items (database results)
	
	var $num_links              =  2; // Number of "digit" links to show before/after the currently viewed page
	var $cur_page               =  0; // The current page being viewed
	var $first_link             = '&laquo First';
	var $next_link              = 'Next &rsaquo;';
	var $prev_link              = '&lsaquo; Previous';
	var $last_link              = 'Last &raquo;';
	var $full_tag_open          = '<ul>';
	var $full_tag_close         = '</ul>';
	var $first_tag_open         = '<li>';
	var $first_tag_close        = '</li>';
	var $last_tag_open          = '<li>';
	var $last_tag_close         = '</li>';
	var $first_url              = ''; // Alternative URL for the First Page.
	var $cur_tag_open           = '<li class="selected">';
	var $cur_tag_close          = '</li>';
	var $next_tag_open          = '<li>';
	var $next_tag_close         = '</li>';
	var $prev_tag_open          = '<li>';
	var $prev_tag_close         = '</li>';
	var $num_tag_open           = '<li>';
	var $num_tag_close          = '</li>';
    var $page_tag_open          = '<ul>';
    var $page_tag_close         = '</ul>';

	var $page_query_string      = FALSE;
	var $query_string_segment   = 'page_size';
	var $display_pages          = TRUE;
	var $anchor_class           = '';

    var $page_num_segment       = 3;
    var $page_size_segment      = 4;
    var $sort_by_segment        = 5;
    var $sort_order_segment     = 6;

    var $page_sizes             = array(10, 25, 50, 100);
    var $size_tag_open          = '<ul>';
    var $size_tag_close         = '</ul>';

    var $table_columns          = '';
    var $model_name             = '';
    var $count_function         = 'count';
    var $record_function        = '';

    public $table_records       = '';
    public $table_headings      = '';
    
    public $page_size           = 25; // Number of items you want shown per page by default
    public $page_number         = 1;
    public $sort_by             = '';
    public $sort_order          = '';
    public $offset              = 0;

    private $_num_pages         = ''; //total number of pages
    private $CI                 = '';

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	array	initialisation parameters
	 */
	public function __construct($params = array())
	{
		if (count($params) > 0)
		{
			$this->initialize($params);
		}

		if ($this->anchor_class != '')
		{
			$this->anchor_class = 'class="'.$this->anchor_class.'" ';
		}
		
		$this->CI =& get_instance();

        log_message('debug', "Pagination Class Initialized");

	}

	// --------------------------------------------------------------------

	/**
	 * Initialize Preferences
	 *
	 * @access	public
	 * @param	array	initialization parameters
	 * @return	void
	 */
	function initialize($params = array())
	{
		if (count($params) > 0)
		{
			foreach ($params as $key => $val)
			{
				if (isset($this->$key))
				{
                    $this->$key = $val;
				}
			}
		}

        //generate the table data
        $this->_generate_pagination_params();
	}

	// --------------------------------------------------------------------

	/**
	 * Generate the pagination links
	 *
	 * @access	public
	 * @return	string
	 */
	function create_links()
	{
        // If our item count or per-page total is zero there is no need to continue.
		if ($this->total_rows == 0 OR $this->page_size == 0)
		{
            $this->_num_pages = 0;
			return '';
		}

        //set up the number of pages
        //if the page size is greater than the number of items, then just return a single page
        if ($this->page_size > $this->total_rows)
        {
            $this->_num_pages = 1;
        }
        else
        {
            $this->_num_pages = ceil($this->total_rows / $this->page_size);
        }
		
		if ( ! $this->_num_pages)
		{
			$this->_num_pages = 0;
		}
		
		
		if ($this->CI->config->item('enable_query_strings') === TRUE OR $this->page_query_string === TRUE)
		{
			if ($this->CI->input->get($this->query_string_segment) != 0)
			{
				$this->cur_page = $this->CI->input->get($this->query_string_segment);

				// Prep the current page - no funny business!
				$this->cur_page = (int) $this->cur_page;
			}
		}
		else
		{
            if ($this->CI->uri->segment($this->page_num_segment) != 0)
			{
				$this->cur_page = $this->CI->uri->segment($this->page_num_segment);
			}
            else
            {
                $this->cur_page = 1;
            }
            // Prep the current page - no funny business!
            $this->cur_page = (int) $this->cur_page;
		}

        //if there is only 1 page, then current page is first page
        if ($this->_num_pages == 1)
		{
			$this->cur_page = 1;
		}

        //sanity check, cur page cant be less than 1
        //and cant be more than total pages
        $this->cur_page = max($this->cur_page, 1);
        $this->cur_page = min($this->cur_page, $this->_num_pages);

        //number of links shown
		$this->num_links = (int)$this->num_links;
		if ($this->num_links < 1)
		{
			show_error('The number of links must be a positive number.');
		}

        if ($this->_num_pages == 1)
        {
            return '';
        }
        
		// Calculate the start and end numbers. These determine
		// which number to start and end the digit links with
		$start = (($this->cur_page - $this->num_links) > 0) ? $this->cur_page - ($this->num_links - 1) : 1;
		$end   = (($this->cur_page + $this->num_links) < $this->_num_pages) ? $this->cur_page + $this->num_links : $this->_num_pages;

		// Is pagination being used over GET or POST?  If get, add a page_size query
		// string. If post, add a trailing slash to the base URL if needed
		if ($this->CI->config->item('enable_query_strings') === TRUE OR $this->page_query_string === TRUE)
		{
			$this->base_url = rtrim($this->base_url).'&amp;'.$this->query_string_segment.'=';
		}
		else
		{
			$this->base_url = rtrim($this->base_url, '/') .'/';
		}
                        

		// And here we go...
		$output = '';

		// Render the "First" link
		if  ($this->first_link !== FALSE AND $this->cur_page > ($this->num_links + 1))
		{
            $first_url = $this->base_url.'1/'.$this->page_size.'/'.$this->sort_by.'/'.$this->sort_order;
			$output .= $this->first_tag_open.'<a '.$this->anchor_class.'href="'.$first_url.'">'.$this->first_link.'</a>'.$this->first_tag_close;
		}

		// Render the "previous" link
		if  ($this->prev_link !== FALSE AND $this->cur_page != 1)
		{
			$i = $this->cur_page - 1;

			if ($i == 0 && $this->first_url != '')
			{
				$output .= $this->prev_tag_open.'<a '.$this->anchor_class.'href="'.$this->first_url.'">'.$this->prev_link.'</a>'.$this->prev_tag_close;
			}
			else
			{
				$i = ($i == 0) ? '' : $this->prefix.$i.'/'.$this->page_size.$this->suffix.'/'.$this->sort_by.'/'.$this->sort_order;
				$output .= $this->prev_tag_open.'<a '.$this->anchor_class.'href="'.$this->base_url.$i.'">'.$this->prev_link.'</a>'.$this->prev_tag_close;
			}

		}

		// Render the pages
		if ($this->display_pages !== FALSE)
		{
			// Write the digit links
			for ($loop = $start -1; $loop <= $end; $loop++)
			{
				$i = ($loop * $this->page_size) - $this->page_size;

				if ($i >= 0)
				{
					if ($this->cur_page == $loop)
					{
                        $output .= $this->cur_tag_open.$loop.$this->cur_tag_close; // Current page
					}
					else
					{
						$n = ($i == 0) ? '' : $i;
						if ($n == '' && $this->first_url != '')
						{							
                            $output .= $this->num_tag_open.'<a '.$this->anchor_class.'href="'.$this->first_url.'">'.$loop.'</a>'.$this->num_tag_close;                            
                        }
						else
						{
							$n = ($n == '') ? '' : $this->prefix.$n.$this->suffix;
                            $uri = $this->base_url.$loop."/".$this->page_size.'/'.$this->sort_by.'/'.$this->sort_order;
							$output .= $this->num_tag_open.'<a '.$this->anchor_class.'href="'.$uri.'">'.$loop.'</a>'.$this->num_tag_close;
                        }
					}
				}
			}
		}

		// Render the "next" link
		if ($this->next_link !== FALSE AND $this->cur_page < $this->_num_pages)
		{
            $i = $this->prefix.($this->cur_page + 1).'/'.$this->page_size.$this->suffix.'/'.$this->sort_by.'/'.$this->sort_order;
            $output .= $this->next_tag_open.'<a '.$this->anchor_class.'href="'.$this->base_url.$i.'">'.$this->next_link.'</a>'.$this->next_tag_close;
		}

		// Render the "Last" link
		if ($this->last_link !== FALSE AND ($this->cur_page + $this->num_links) < $this->_num_pages)
		{
			$i = $this->prefix.($this->_num_pages).'/'.$this->page_size.$this->suffix.'/'.$this->sort_by.'/'.$this->sort_order;
			$output .= $this->last_tag_open.'<a '.$this->anchor_class.'href="'.$this->base_url.$i.'">'.$this->last_link.'</a>'.$this->last_tag_close;
		}

		// Kill double slashes.  Note: Sometimes we can end up with a double slash
		// in the penultimate link so we'll kill all double slashes.
		$output = preg_replace("#([^:])//+#", "\\1/", $output);

		// Add the wrapper HTML if exists
		return $this->full_tag_open.$output.$this->full_tag_close;

	}
	
	// --------------------------------------------------------------------

    /**
	 * Generate the table data and headings. In order for this function to work,
     * both the table_columns and table_records must be set correctly.
	 *
	 * @access	public
	 * @return	string
	 */
    function create_table()
    {
        //check that the correct data is available
        if ( ! is_array($this->table_columns) OR ! is_array($this->table_records))
        {
            return '';
        }

        $this->CI->load->helper('html');
        $this->CI->load->library('table');
        $this->CI->table->set_empty("&nbsp;");

        if ($this->table_headings == '')
        {
            $this->create_table_headings();
        }

        $this->CI->table->set_heading($this->table_headings);

        foreach ($this->table_records as $record)
        {
            $row = array();
            foreach ($this->table_columns as $column)
            {               
                if (isset($column['img']))
                {
                    if ($record->$column['database'] == $column['img_condition'])
                    {
                        $text = img($column['img']);
                    }
                    else
                    {
                        $text = '';
                    }
                    
                }
                else if(isset($column['text']))
                {
                    $text = $column['text'];
                }
                else
                {
                    $text = $record->$column['database'];
                }

                if (isset($column['anchor_link']) AND isset($column['anchor_field']) AND ( ! empty ($record->$column['anchor_field'])))
                {
                    $link = $column['anchor_link'] . $record->$column['anchor_field'];
                    $extra = (isset($column['extra'])) ? $column['extra'] : array();
                    $row[] = anchor($link, $text, $extra);
                }
                else
                {
                    $row[] = $text;
                }               
            }
            $this->CI->table->add_row($row);
        }
        return $this->CI->table->generate();
    }
	
	// --------------------------------------------------------------------

    /**
	 * Generate the table headings
	 *
	 * @return	array
	 */
    function create_table_headings()
    {
        $headings = array();
        foreach ($this->table_columns as $col)
        {
            $link = '';
            $class = '';
            $header = '';

            if (isset($col['database']))
            {
                if ($this->sort_by == $col['database'])
                {
                    $class = ($this->sort_order == 'asc') ? array('class' => 'asc') : array('class' => 'desc');
                }                
            }

            //create a sortable column
            if (isset($col['sortable']) && isset($col['database']))
            {
                if ($col['sortable'] == TRUE)
                {
                    $link = rtrim($this->base_url, '/') .'/';
                    $link .= $this->cur_page . '/' . $this->page_size . '/';
                    $link .= $col['database'] . '/';
                    $link .= ($this->sort_order == 'asc' && $this->sort_by == $col['database'] ) ? 'desc/' : 'asc/';

                    //if the header text has not been set, then use the database field
                    $text = (isset($col['header'])) ? $col['header'] : $col['database'];
                    $header = anchor($link, $text, $class);
                }
            }
            //create a non sortable column
            elseif (isset($col['header']))
            {
                $header = $col['header'];
            }
            $headings[] = $header;
        }
        $this->table_headings = $headings;
        return $this->table_headings;
    }
	
	// --------------------------------------------------------------------

    /**
	 * Generates an array summary of the current recordset
     *
	 * @access	public
	 * @return	array
	 */
    function create_summary()
    {
        $output = array();

        $output['page']     = $this->cur_page;
        $output['pages']    = $this->_num_pages;
        $output['start']    = ($this->cur_page * $this->page_size) - $this->page_size + 1;
        $output['end']      = min($this->total_rows, $this->cur_page * $this->page_size);
        $output['count']    = $this->total_rows;
        $output['current']  = count($this->table_records);
        return $output;
    }
	
	// --------------------------------------------------------------------

    /**
	 * Generate the page size links
	 *
	 * @access	public
	 * @return	string
	 */
    function create_page_sizes()
    {
        $output = '';
        $sort_by = ($this->sort_by == '') ? '' : '/' . $this->sort_by;
        $sort_order = ($this->sort_order == '') ? '' : '/' . $this->sort_order;

        foreach($this->page_sizes as $value)
        {
            //current page
            if ($value == $this->page_size)
            {
                $output .= $this->cur_tag_open.$value.$this->cur_tag_close;
            }
            else
            {
                $uri = $this->base_url . '/' . $this->cur_page . '/' . $value . $sort_by . $sort_order;
                $output .= $this->num_tag_open . '<a ' . $this->anchor_class . 'href="' . $uri . '">' . $value . '</a>' . $this->num_tag_close;
            }
        }
        $output = $this->size_tag_open . $output . $this->size_tag_close;
        $output = preg_replace("#([^:])//+#", "\\1/", $output);
        return $output;
    }
	
	// --------------------------------------------------------------------

    /**
	 * <p>Generates the record count if the model_name and count_function names have been passed in.<br>
     * If these params have not been passed in, then the total_rows must be passed in initialisation.</p>
     * <p>Generates the table records if the model_name and record_function names have been passed in.<br>
     * Note that the record_function must be set in your model, with the parameters<br>
     * in this order: $sort_by, $sort_order, $offset, $limit</p>
     *
	 * @access	private
	 * @return	void
	 */
    function _generate_pagination_params()
    {
        //get the actual recordset if the model name and function name
        //have been passed as a config param
        if ($this->model_name != '' && $this->count_function != '')
        {
            //load the table library
            $this->CI->load->library('table');

            //load up the model
            $model = $this->model_name;
            $this->CI->load->model($model);

            //load the count function        
            $function = $this->count_function;
            $this->total_rows = $this->CI->$model->$function();       
        }

        //now that we have the total rows, calculate the paginations params
        $this->page_size    = $this->_calculate_page_size();
        $this->page_number  = $this->_calculate_page_number();
        $this->sort_by      = $this->_calculate_sort_by();
        $this->sort_order   = $this->_calculate_sort_order();
        $this->offset       = $this->_calculate_offset();
        
        //get the actual recordset if the model name and function name
        //have been passed as a config param
        if ($this->model_name != '' && $this->record_function != '')
        {
            $model = $this->model_name;
            $record_function = $this->record_function;

            $this->table_records = $this->CI->$model->$record_function($this->sort_by, $this->sort_order, $this->offset, $this->page_size);
        }
    }
	
	// --------------------------------------------------------------------

    /**
     * Calculate the offset, which cannot be larger than the total rows
     * @return int
     */
    function _calculate_offset()
    {
        $offset = ($this->page_number * $this->page_size) - $this->page_size;
        return ($offset > $this->total_rows) ? $this->total_rows - $this->page_size : $offset;
    }
	
	// --------------------------------------------------------------------

    /**
	 * Sets the page_size of the data based on the specified uri segment
     *
	 * The value cannot be less than the minimum element, or greater
     * than the maximum element specified in the page_sizes var
     *
	 * @access	private
	 * @return	int
	 */
    function _calculate_page_size()
    {
		$size = $this->page_size;
        if ($this->CI->uri->segment($this->page_size_segment))
        {
            $size = $this->CI->uri->segment($this->page_size_segment);
        }
        elseif(($this->CI->session->userdata('page_size')))
        {
            $size = $this->CI->session->userdata('page_size');
        }

        if ($size > max($this->page_sizes))
        {
            $size = max($this->page_sizes);
        }
        elseif ($size < min($this->page_sizes))
        {
            $size = min($this->page_sizes);
        }
        
        $this->CI->session->set_userdata(array('page_size' => $size));
        return $size;
    }
	
	// --------------------------------------------------------------------

    /**
	 * Sets the current page_num
     * 
	 * @access	private
	 * @return	int
	 */
    function _calculate_page_number()
    {
        $page = max(1, $this->CI->uri->segment($this->page_num_segment));
        return (($this->page_size > $this->total_rows) ? 1 : $page);
    }
	
	// --------------------------------------------------------------------
	
    /**
	 * <p>Sets the sort order of the data based on the specified uri segment</p>
     * <p>A table_columns array must be passed to this class to prevent
     * users from sorting on incorrect/unavailable database columns
     * by directly changing the url segment</p>
     * <p>If a uri segment is not available, or if a default sort
     * has not been specified, then the first column will be used</p>
	 *
	 * @access	private
	 * @return	string
	 */
    function _calculate_sort_by()
    {
        if ($this->table_columns == '') return;

        $sort_options = array();
        $i = 0;
        foreach($this->table_columns as $column)
        {
            //set up the first column incase a default sort has not been specified
            if ($i == 0 && isset($column['database']))
            {
                $first_column = $column['database'];
            }

            //create an array of all columns that could possibly be sortable
            if (isset($column['sortable']) && $column['sortable'] == TRUE)
            {
                $sort_options[] = $column['database'];
            }
            
            //check if a default sort has been created
            if (isset($column['default_sort']))
            {
                $default_sort = $column['database'];
            }
            $i++;
        }
        $sort_by = $this->CI->uri->segment($this->sort_by_segment);

        //if the uri segement valid, then use it
        if (in_array($sort_by, $sort_options))
        {
            return $sort_by;
        }

        //if the default sort is valid, then use it
        else if (isset($default_sort) && in_array($default_sort, $sort_options))
        {
            return $default_sort;
        }

        //last resort, just use the first column
        else
        {
            return $first_column;
        }
    }
	
	// --------------------------------------------------------------------

	/**
	 * Sets the sort order of the data based on the 6th uri segment
	 * Defaults to 'asc'
     *
	 * @access	private
	 * @return	string
	 */
    function _calculate_sort_order()
    {
        if ($this->table_columns == '') return;

        //if the uri segment is set, then return that as the sort order
        $sort_order = $this->CI->uri->segment($this->sort_order_segment);
        if (strtolower(trim($sort_order)) == 'desc' || strtolower(trim($sort_order)) == 'asc')
        {
            return $sort_order;
        }

        //if the uri segment is not set, then check if there is a column set as default
        foreach($this->table_columns as $column)
        {
            if (isset($column['default_sort']))
            {
                $default_sort_order = $column['default_sort'];
                return (strtolower(trim($default_sort_order)) == 'desc') ? 'desc' : 'asc';
            }
        }

        //last resort if there is no segment and a default sort has not been specified
        return 'asc';
    }
}
// END Paginator Class

/* End of file Paginator.php */
/* Location: ./application/libraries/Paginator.php */