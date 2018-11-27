<?php
error_reporting(E_ALL);


use Restserver\Libraries\REST_Controller;
use GuzzleHttp\Promise;
defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
//To Solve File REST_Controller not found
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

/**
 *
 * Search REST Class
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Base created by by Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Search extends REST_Controller {

    protected $data = [];
    protected $owner_temp = null;
    function __construct()
    {

        // Construct the parent class
        parent::__construct();
        $this->load->library('guzzle');
        $this->load->library('jsonq');
        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }


    public function vendor_get(){

        $client = new \GuzzleHttp\Client;

        //Get GET parameters
        $per_page_value = (($this->get(config_item('per_page'))!=null && $this->get(config_item('per_page'))!='')?$this->get(config_item('per_page')):config_item('per_page_default'));
        $per_page = ((config_item('per_page')!=null && config_item('per_page_default')!=null && config_item('per_page')!='' && config_item('per_page_default')!='')?config_item('per_page').'='.$per_page_value:null);

        $page_value = (($this->get(config_item('page'))!=null && $this->get(config_item('page'))!='')?$this->get(config_item('page')):config_item('page_default'));
        $page = ((config_item('page')!=null && config_item('page_default')!=null && config_item('page')!='' && config_item('page_default')!='')?config_item('page').'='.$page_value:null);

        $query_value = (($this->get(config_item('query'))!=null && $this->get(config_item('query'))!='')?$this->get(config_item('query')):'');
        $query = ((config_item('query')!=null && config_item('query')!='')?config_item('query').'='.$query_value:null);

        $sort_value = (($this->get(config_item('sort'))!=null && $this->get(config_item('sort'))!='')?$this->get(config_item('sort')):config_item('sort_default'));
        $sort = ((config_item('sort')!=null && config_item('sort_default')!=null && config_item('sort')!='' && config_item('sort_default')!='')?config_item('sort').'='.$sort_value:null);

        $order_value = (($this->get(config_item('order'))!=null && $this->get(config_item('order'))!='')?$this->get(config_item('order')):config_item('order_default'));
        $order = ((config_item('order')!=null && config_item('order_default')!=null && config_item('order')!='' && config_item('order_default')!='')?config_item('order').'='.$order_value:null);



        //Building the url from config - or we can use http_build_query()

       $url = config_item('search_code_url').'?'.
            (($per_page!=null)?'&'.$per_page:'').
            (($page!=null)?'&'.$page:'').
            (($query!=null)?'&'.$query:'').
            (($sort!=null)?'&'.$sort:'').
            (($order!=null)?'&'.$order:'');

        // Send an asynchronous request.
        $request = new \GuzzleHttp\Psr7\Request('GET',$url,config_item('headers'));
        $promise = $client->sendAsync($request)->then(function ($response) {
            $this->data =  $response->getBody()->getContents();
        });
        $promise->wait(); // wait till promise fully executed.

        $items = null;
        $results = [
          config_item('total_count') => 0,
          config_item('per_page')  => $per_page_value,
          config_item('page') => $page_value,
          config_item('items') => null
        ];

        try {
            //Getting Total Results.
            $jq = new \Nahid\JsonQ\Jsonq(); // Magic JsonQ
            $jq->json($this->data);
            $total_count = $jq->from(config_item('total_count'))->get();
            $results[config_item('total_count')] = $total_count;

            $jq2 = new \Nahid\JsonQ\Jsonq(); // Magic JsonQ
            $jq2->json($this->data);

            if($sort_value != null && $order_value != null && config_item('deep_sort') === TRUE) {

              $items = $jq2->from('items')->sortBy($sort_value, $order_value);
              $results[config_item('items')] = $items;
            }
            else {


                    $items = $jq2->from(config_item('items'))->transform(function ($arr){
                    $client2 = new \GuzzleHttp\Client;
                    // Send an asynchronous request.
                    $request2 = new \GuzzleHttp\Psr7\Request('GET',$arr[config_item('repository')][config_item('owner')][config_item('owner_url')],config_item('headers'));
                    $promise2 = $client2->sendAsync($request2)->then(function ($response) {
                        $this->owner_temp = $response->getBody()->getContents();
                    });
                    $promise2->wait();
                    $ownerjq = new \Nahid\JsonQ\Jsonq();
                    $ownerjq->json($this->owner_temp);

                    $owner = $ownerjq->from(config_item('owner_name'))->get();
                    return [
                        'owner_name'=> $owner,
                        'repository_name' =>$arr[config_item('repository')][config_item('repo_name')],
                        'file_name'=>$arr[config_item('filename')]
                    ];
                });
              $results[config_item('items')] = $items;
            }

         // Deep sort
         $this->response($results,REST_Controller::HTTP_OK);
        } catch (\Nahid\JsonQ\Exceptions\ConditionNotAllowedException $e) {
            echo $e->getMessage();
        } catch (\Nahid\JsonQ\Exceptions\NullValueException $e) {
            echo $e->getMessage();
        }


    }

    public function users_get()
    {
        // Users from a data store e.g. database
        $users = [
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com', 'fact' => 'Loves coding'],
            ['id' => 2, 'name' => 'Jim', 'email' => 'jim@example.com', 'fact' => 'Developed on CodeIgniter'],
            ['id' => 3, 'name' => 'Jane', 'email' => 'jane@example.com', 'fact' => 'Lives in the USA', ['hobbies' => ['guitar', 'cycling']]],
        ];

        $id = $this->get('id');

        // If the id parameter doesn't exist return all the users

        if ($id === NULL)
        {
            // Check if the users data store contains users (in case the database result returns NULL)
            if ($users)
            {
                // Set the response and exit
                $this->response($users, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
            }
            else
            {
                // Set the response and exit
                $this->response([
                    'status' => FALSE,
                    'message' => 'No users were found'
                ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
            }
        }

        // Find and return a single record for a particular user.

        $id = (int) $id;

        // Validate the id.
        if ($id <= 0)
        {
            // Invalid id, set the response and exit.
            $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        // Get the user from the array, using the id as key for retrieval.
        // Usually a model is to be used for this.

        $user = NULL;

        if (!empty($users))
        {
            foreach ($users as $key => $value)
            {
                if (isset($value['id']) && $value['id'] === $id)
                {
                    $user = $value;
                }
            }
        }

        if (!empty($user))
        {
            $this->set_response($user, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }
        else
        {
            $this->set_response([
                'status' => FALSE,
                'message' => 'User could not be found'
            ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }
}
