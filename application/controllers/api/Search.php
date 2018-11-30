<?php
use Restserver\Libraries\REST_Controller;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception;
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

        //Optional - Not enabled
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php

        //vendor
        $this->methods['vendor_get']['limit'] = 500; // 500 requests per hour per vendor/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per vendor/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per vendor/key

        //users
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key

    }


    public function vendor_get(){



        //I did not used array to show how to build from scratch for few parameters. The ideal way to used array, I used it for additional ones.

        // Get GET parameters & default values for:
        //per page value
        $per_page_value = (($this->get(config_item('per_page'))!=null && $this->get(config_item('per_page'))!='')?$this->get(config_item('per_page')):config_item('per_page_default'));
        //per page parameter
        $per_page = ((config_item('per_page')!=null && config_item('per_page_default')!=null && config_item('per_page')!='' && config_item('per_page_default')!='')?config_item('per_page').'='.$per_page_value:null);

        //page_value
        $page_value = (($this->get(config_item('page'))!=null && $this->get(config_item('page'))!='')?$this->get(config_item('page')):config_item('page_default'));
        //page parameter
        $page = ((config_item('page')!=null && config_item('page_default')!=null && config_item('page')!='' && config_item('page_default')!='')?config_item('page').'='.$page_value:null);

        //query value
        $query_value = (($this->get(config_item('query'))!=null && $this->get(config_item('query'))!='')?$this->get(config_item('query')):config_item('query_default'));
        //query parameter
        $query = ((config_item('query')!=null && config_item('query')!='')?config_item('query').'='.$query_value:null);

        //sort value
        $sort_value = (($this->get(config_item('sort'))!=null && $this->get(config_item('sort'))!='')?$this->get(config_item('sort')):config_item('sort_default'));
        //sort parameter
        $sort = ((config_item('sort')!=null && config_item('sort_default')!=null && config_item('sort')!='' && config_item('sort_default')!='')?config_item('sort').'='.$sort_value:null);

        //order value
        $order_value = (($this->get(config_item('order'))!=null && $this->get(config_item('order'))!='')?$this->get(config_item('order')):config_item('order_default'));
        //order parameter
        $order = ((config_item('order')!=null && config_item('order_default')!=null && config_item('order')!='' && config_item('order_default')!='')?config_item('order').'='.$order_value:null);


        //Building the url from config -

       $url = config_item('search_code_url').'?'.
            (($per_page!=null && ($per_page_value != '' && $per_page_value != null ))?'&'.$per_page:'').
            (($page!=null && ($page_value != '' && $page_value != null ))?'&'.$page:'').
            (($query!=null && ($query_value != '' && $query_value != null ))?'&'.$query:'').
            (($sort!=null  && ($sort_value != '' && $sort_value != null ))?'&'.$sort:'').
            (($order!=null && ($order_value != '' && $order_value != null ))?'&'.$order:'');




        $items = null;
        $results = [
            config_item('total_count') => 0,
            config_item('per_page')  => $per_page_value,
            config_item('page') => $page_value,
            config_item('items') => null,
            config_item('messages') => []
        ];

        try {

            $client = new \GuzzleHttp\Client;

            // Send an asynchronous request.
            $request = new \GuzzleHttp\Psr7\Request('GET', $url, config_item('headers'));
            $promise = $client->sendAsync($request)->then(function ($response) {
                $this->data = $response->getBody()->getContents();
            });
            $promise->wait(); // wait till promise fully executed.


            //Getting Total Results.
            $jq = new \Nahid\JsonQ\Jsonq(); // Magic JsonQ
            $jq->json($this->data);
            $total_count = $jq->from(config_item('total_count'))->get();
            $results[config_item('total_count')] = $total_count;

            $jq2 = new \Nahid\JsonQ\Jsonq(); // Magic JsonQ
            $jq2->json($this->data);


            //Is it a node within root or not ?
            $items = (config_item('items') != null && config_item('items') !='')  ?  $jq2->from(config_item('items')):$jq2->select();

            $items =  $items->transform(function ($arr) {

                // If the owner's info should be get from URL
                if(config_item('owner_info_fetched_from_url') == TRUE &&
                    (config_item('repository') != null && config_item('repository') != '' && config_item('owner') != null &&
                        config_item('owner')!= '' &&  config_item('owner_url') != null && config_item('owner_url') != '')){

                    $url = ($arr[config_item('repository')][config_item('owner')][config_item('owner_url')] != null && $arr[config_item('repository')][config_item('owner')][config_item('owner_url')]!='')?
                        $arr[config_item('repository')][config_item('owner')][config_item('owner_url')]:''; // check if url available within the node
                    if($url != null && $url !='') {
                        $client2 = new \GuzzleHttp\Client;
                        $request2 = new \GuzzleHttp\Psr7\Request('GET', $url, config_item('headers'));
                        $promise2 = $client2->sendasync($request2)->then(function ($response) {
                            $this->owner_temp = '';
                            $this->owner_temp = $response->getBody()->getContents(); //get the json from body
                        });
                        $promise2->wait(); //wait while execute the request

                        $ownerjq = new \Nahid\JsonQ\Jsonq(); //define new json query object
                        $ownerjq->json($this->owner_temp); // collect json
                        $owner = $ownerjq->from(config_item('owner_name'))->get(); //get owner's name from url
                        return [
                            'owner_name' => $owner,
                            'repository_name' => $arr[config_item('repository')][config_item('repo_name')],
                            'file_name' => $arr[config_item('filename')]
                        ];
                    } else {
                        return [
                            'owner_name' => $arr[config_item('owner')][config_item('owner_name')],
                            'repository_name' => $arr[config_item('repository')][config_item('repo_name')],
                            'file_name' => $arr[config_item('filename')]
                        ];
                    }
                }
            });

            $results[config_item('items')] = $items;
            $this->response($results, REST_Controller::HTTP_OK);
        }catch (\GuzzleHttp\Exception\ClientException $e) {
            array_push($results[config_item('messages')],\GuzzleHttp\Psr7\str($e->getRequest()));
            array_push($results[config_item('messages')],\GuzzleHttp\Psr7\str($e->getResponse()));
            $this->response($results,REST_Controller::HTTP_BAD_REQUEST);
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
            array_push($results[config_item('messages')],\GuzzleHttp\Psr7\str($e->getRequest()));
            if($e->getResponse())
            array_push($results[config_item('messages')],\GuzzleHttp\Psr7\str($e->getResponse()));
            $this->response($results,REST_Controller::HTTP_BAD_REQUEST);
        }catch (\GuzzleHttp\Exception\BadResponseException $e) {
            array_push($results[config_item('messages')],$e->getMessage());
            $this->response($results,REST_Controller::HTTP_BAD_REQUEST);
        }
       catch (\Nahid\JsonQ\Exceptions\ConditionNotAllowedException $e) {
            array_push($results[config_item('messages')],$e->getMessage());
            $this->response($results,REST_Controller::HTTP_BAD_REQUEST);
        } catch (\Nahid\JsonQ\Exceptions\NullValueException $e) {
            array_push($results[config_item('messages')],$e->getMessage());
            $this->response($results,REST_Controller::HTTP_BAD_REQUEST);
        } catch (Exception $e){
            array_push($results[config_item('messages')],'Unknown error. Please contact developer');
            $this->response($results,REST_Controller::HTTP_BAD_REQUEST);
        }


    }
    //Included as Example from REST Server Base Controller
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
