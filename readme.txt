1. Getting started:

1.1 Drop this file into your application/libraries/ folder
1.2 Initialise it in a controller by using $this->load->library('paginator');
1.3 Pass in some parameters (see below)
1.4 There are 4 sections to output:

1.4.1 To create your standard pagination links ala the CI Core Pagination Lib.
$this->paginator->create_links();

Important: the create_links() function must be called before any of the following
functions, otherwise they will not work

1.4.2 To create the actual table with all your records, and sortable headers:
$this->paginator->create_table();

1.4.3 To create selectable page sizes.
$this->paginator->create_page_sizes();
This is based on the $page_sizes var. By default, this is set to (10, 25, 50, 100)
but you can pass in a config array of any values that you want.

1.4.4 To create a summary
$this->paginator->create_summary();
This outputs an array, so that you can create some verbose output on the current recordset, like:
Page 1 of 2, showing 20 records out of 27 total, starting on record 1, ending on 20


// --------------------------------------------------------------------

2. Parameters
I have tried to make it as easy to use for my scenario, which means
that a lot of the params have default values, and I pass in only the minimum number required

In order for all of the above to work properly, at a minimum the the following parameters must
be available.

2.1 table_columns
This is an array of data that you want shown in the table, and it also gives
instructions on how you want those columns treated. See the example controller
section below for more information.

2.2 model_name
You must pass in a valid model name if you want the automagic from 2.3.1 or 2.4.1 below to work
Example: Products_model

2.3 Total number of records
In order to generate the pagination, the lib needs to know the total
number of records in the recordset. There are two ways to do this:

2.3.1 By passing in a count_function config var - this is the function name in 
the above model (see 2.2 above) that counts all your records. Dont include the 
opening and closing brackets after the function name
Example: count

2.3.2 Alternatively, you can just pass in the count through $total_rows param

2.4 You will also need to pass in the actual records to populate your table. This must
be an array of objects, so make sure to return the model records ->result() and not
->result_array().
Again, there are two options:

2.4.1 By passing in a record_function config var - this is the function name in
the above model (see 2.2 above) that returns your records. Dont include the 
opening and closing brackets after the function name
Example: get_products
Important: The parameters for this function MUST be in this order: $sort_by, $sort_order, $offset, $limit
Important: This function must return (at a minimum) all columns that are identified as 'sortable' in $config['table_columns'] (see 2.1 above)

2.4.2 Alternatively, you can just pass in the records via through the $table_records config var

2.5 base_url
This is usually the controller/function that you are working from. 
Example: site_url('products/index/');

2.6 By default, the lib expects the the uri segments to be in the following order:
/controller/method/page_num/page_size/sort_by/sort_order
You can, however, specify different segments by changing the following config vars:
$config['page_num_segment']       = 3;
$config['page_size_segment']      = 4;
$config['sort_by_segment']        = 5;
$config['sort_order_segment']     = 6;

// --------------------------------------------------------------------

3. Example Controller:

$this->load->library('paginator');

$columns = array(
    array(
        'database' => 'code', //field name in the database
        'header' => 'Code', //text for the table heading
        'sortable' => TRUE, //Allow sorting on this column?
        'default_sort' => TRUE, //is this the default sort column? One column must have this property set to true
        'anchor_link' => 'products/edit/', //if set, then the data in the rows will link to this controller/function
        'anchor_field' => 'id' //and it will link to this field in the database
    ),
    array(
        'database' => 'description',
        'header' => 'Description',
        'sortable' => TRUE
    ),
    array(
        'header' => 'Actions',
        'sortable' => FALSE, //note that this column is not sortable
        'text' => 'Delete',
        'anchor_field' => 'id',
        'anchor_link' => 'products/delete/',
        'extra' => array(
                'class' => 'delete',
                'onclick' => "return confirm('Are you sure want to delete this product?')")
    )
 );

//pagination
$config['table_columns']    = $columns;
$config['base_url']         = site_url('products/index/');
$config['model_name']       = 'Product_model';
//$config['count_function'] = 'count'; //note that I dont set this param, because the default
//param for this variable in the class is 'count', and I make sure that all the relevant models have this function name
//alternatively, I could just pass in $config['total_rows'] = x

$config['record_function']  = 'get_products';
//Alternatively, you can just set the records manually using the $table_records param

$this->paginator->initialize($config);

$data['pagination'] 	= $this->paginator->create_links(); //note that this must be called before any of the following
$data['table_records'] 	= $this->paginator->create_table();
$data['page_sizes'] 	= $this->paginator->create_page_sizes();
$data['summary'] 		= $this->paginator->create_summary();

$this->load->view('products/index', $data);